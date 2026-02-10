import { expect } from '@playwright/test';
import type { Browser, Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../../playwright/config';
import { getInvitationAcceptUrl } from './mailpit';
import type { TestContext } from './api';

/**
 * Register a new user in a fresh browser context and return the page + context.
 */
export async function registerUser(
    browser: Browser,
    name: string,
    email: string
): Promise<{ page: Page; close: () => Promise<void> }> {
    const context = await browser.newContext();
    const page = await context.newPage();

    await page.goto(PLAYWRIGHT_BASE_URL + '/register');
    await page.getByLabel('Name').fill(name);
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Password', { exact: true }).fill('amazingpassword123');
    await page.getByLabel('Confirm Password').fill('amazingpassword123');
    await page.getByLabel('I agree to the Terms of').click();
    await page.getByRole('button', { name: 'Register' }).click();
    await page.waitForURL(PLAYWRIGHT_BASE_URL + '/dashboard');

    return { page, close: () => context.close() };
}

/**
 * Invite a user by email from the members page and accept the invitation
 * through a second browser session, returning the accepted member to the
 * members table as a real (non-placeholder) member.
 *
 * @param ownerPage   – The page of the organization owner who sends the invite
 * @param browser     – Browser instance used to create a second context
 * @param memberName  – Display name for the new user
 * @param memberEmail – Email address (must not be registered yet)
 * @param role        – Role button label: 'Employee' | 'Manager' | 'Administrator'
 */
export async function inviteAndAcceptMember(
    ownerPage: Page,
    browser: Browser,
    memberName: string,
    memberEmail: string,
    role: 'Employee' | 'Manager' | 'Administrator'
): Promise<void> {
    // 1. Register the second user
    const secondUser = await registerUser(browser, memberName, memberEmail);

    // 2. Send invitation from the owner
    await ownerPage.goto(PLAYWRIGHT_BASE_URL + '/members');
    await ownerPage.getByRole('button', { name: 'Invite Member' }).click();
    await expect(ownerPage.getByPlaceholder('Member Email')).toBeVisible();
    await ownerPage.getByLabel('Email').fill(memberEmail);
    await ownerPage.getByRole('button', { name: role }).click();
    await Promise.all([
        ownerPage.getByRole('button', { name: 'Invite Member', exact: true }).click(),
        expect(ownerPage.getByRole('main')).toContainText(memberEmail),
    ]);

    // 3. Retrieve the acceptance link from Mailpit and accept
    const acceptUrl = await getInvitationAcceptUrl(secondUser.page.request, memberEmail);
    await secondUser.page.goto(acceptUrl);
    await secondUser.page.waitForURL(/dashboard/);

    // 4. Clean up
    await secondUser.close();
}

/**
 * Set up an employee member in the owner's organization.
 * Returns the employee's page, their member ID, and a cleanup function.
 *
 * The owner page (from the fixture) is used to invite the employee.
 * Test data should be created via the owner's ctx.
 *
 * IMPORTANT: Projects must be created with is_public: true for the employee to see them,
 * or the employee must be added as a project member via createProjectMemberViaApi.
 * Clients are only visible to employees if they have at least one visible project.
 * Tags are visible to all org members with tags:view permission.
 */
export async function setupEmployeeUser(
    ownerPage: Page,
    ownerCtx: TestContext,
    browser: Browser
): Promise<{
    employeePage: Page;
    employeeMemberId: string;
    closeEmployee: () => Promise<void>;
}> {
    const memberId = Math.floor(Math.random() * 100000);
    const memberEmail = `employee+${memberId}@emp-perms.test`;
    const memberName = 'Emp ' + memberId;

    // Register the employee user first
    const employee = await registerUser(browser, memberName, memberEmail);

    // Send invitation from the owner
    await ownerPage.goto(PLAYWRIGHT_BASE_URL + '/members');
    await ownerPage.getByRole('button', { name: 'Invite Member' }).click();
    await expect(ownerPage.getByPlaceholder('Member Email')).toBeVisible();
    await ownerPage.getByPlaceholder('Member Email').fill(memberEmail);
    await ownerPage.getByRole('button', { name: 'Employee' }).click();
    await Promise.all([
        ownerPage.waitForResponse(
            (response) =>
                response.url().includes('/invitations') &&
                response.request().method() === 'POST' &&
                response.status() === 204
        ),
        ownerPage.getByRole('button', { name: 'Invite Member', exact: true }).click(),
    ]);

    // Accept the invitation
    const acceptUrl = await getInvitationAcceptUrl(employee.page.request, memberEmail);
    await employee.page.goto(acceptUrl);
    await employee.page.waitForURL(/dashboard/);

    // Navigate to dashboard explicitly and wait for it to load to ensure the correct org context.
    await employee.page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
    await expect(employee.page.getByTestId('dashboard_view')).toBeVisible({ timeout: 15000 });

    // Verify we're on the correct organization (John's Organization).
    const orgSwitcherText = await employee.page
        .getByTestId('organization_switcher')
        .first()
        .textContent();
    if (!orgSwitcherText?.includes("John's Organization")) {
        // Switch to the owner's org using the PUT /current-team endpoint
        const cookies = await employee.page.context().cookies();
        const xsrfCookie = cookies.find((c) => c.name === 'XSRF-TOKEN');
        const xsrfToken = xsrfCookie ? decodeURIComponent(xsrfCookie.value) : '';

        await employee.page.request.put(`${PLAYWRIGHT_BASE_URL}/current-team`, {
            headers: {
                'X-XSRF-TOKEN': xsrfToken,
                Accept: 'text/html',
            },
            data: { team_id: ownerCtx.orgId },
        });

        // Reload to pick up the new org
        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
        await expect(employee.page.getByTestId('dashboard_view')).toBeVisible({ timeout: 15000 });
    }

    // Find the employee's member ID in the owner's organization
    const membersResponse = await ownerCtx.request.get(
        `${PLAYWRIGHT_BASE_URL}/api/v1/organizations/${ownerCtx.orgId}/members`
    );
    expect(membersResponse.status()).toBe(200);
    const membersBody = await membersResponse.json();
    const employeeMember = membersBody.data.find(
        (m: { role: string; name: string }) => m.role === 'employee' && m.name === memberName
    );
    expect(employeeMember).toBeTruthy();

    return {
        employeePage: employee.page,
        employeeMemberId: employeeMember.id,
        closeEmployee: employee.close,
    };
}
