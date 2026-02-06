import { expect } from '@playwright/test';
import type { Browser, Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../../playwright/config';
import { getInvitationAcceptUrl } from './mailpit';

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
