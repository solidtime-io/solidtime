// TODO: Edit Billable Rate
// TODO: Resend Email Invitation
// TODO: Remove Invitation
import { expect, test } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import type { Page } from '@playwright/test';
import path from 'path';
import fs from 'fs';
import os from 'os';
import { inviteAndAcceptMember } from './utils/members';

// Tests that invite + accept members need more time
test.describe.configure({ timeout: 60000 });

async function goToMembersPage(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/members');
}

async function openInviteMemberModal(page: Page) {
    await Promise.all([
        page.getByRole('button', { name: 'Invite Member' }).click(),
        expect(page.getByPlaceholder('Member Email')).toBeVisible(),
    ]);
}

test('test that new manager can be invited and accepted', async ({ page, browser }) => {
    const memberId = Math.round(Math.random() * 100000);
    const memberEmail = `manager+${memberId}@invite.test`;

    await inviteAndAcceptMember(page, browser, 'Invited Mgr', memberEmail, 'Manager');

    // Verify the member appears in the members table with the correct role
    await goToMembersPage(page);
    const memberRow = page.getByRole('row').filter({ hasText: 'Invited Mgr' });
    await expect(memberRow).toBeVisible();
    await expect(memberRow.getByText('Manager', { exact: true })).toBeVisible();
});

test('test that new employee can be invited and accepted', async ({ page, browser }) => {
    const memberId = Math.round(Math.random() * 100000);
    const memberEmail = `employee+${memberId}@invite.test`;

    await inviteAndAcceptMember(page, browser, 'Invited Emp', memberEmail, 'Employee');

    // Verify the member appears in the members table with the correct role
    await goToMembersPage(page);
    const memberRow = page.getByRole('row').filter({ hasText: 'Invited Emp' });
    await expect(memberRow).toBeVisible();
    await expect(memberRow.getByText('Employee', { exact: true })).toBeVisible();
});

test('test that new admin can be invited and accepted', async ({ page, browser }) => {
    const memberId = Math.round(Math.random() * 100000);
    const memberEmail = `admin+${memberId}@invite.test`;

    await inviteAndAcceptMember(page, browser, 'Invited Adm', memberEmail, 'Administrator');

    // Verify the member appears in the members table with the correct role
    await goToMembersPage(page);
    const memberRow = page.getByRole('row').filter({ hasText: 'Invited Adm' });
    await expect(memberRow).toBeVisible();
    await expect(memberRow.getByText('Admin', { exact: true })).toBeVisible();
});

test('test that error shows if no role is selected', async ({ page }) => {
    await goToMembersPage(page);
    await openInviteMemberModal(page);
    const noRoleId = Math.round(Math.random() * 10000);

    await page.getByLabel('Email').fill(`new+${noRoleId}@norole.test`);
    await Promise.all([
        page.getByRole('button', { name: 'Invite Member', exact: true }).click(),
        expect(page.getByText('Please select a role')).toBeVisible(),
    ]);
});

test('test that organization billable rate can be updated with all existing time entries', async ({
    page,
}) => {
    await goToMembersPage(page);
    const newBillableRate = Math.round(Math.random() * 10000);
    await page.getByRole('row').first().getByRole('button').click();
    await page.getByRole('menuitem').getByText('Edit').click();
    await page.getByText('Organization Default Rate').click();
    await page.getByText('Custom Rate').click();
    await page.getByPlaceholder('Billable Rate').fill(newBillableRate.toString());
    await page.getByRole('button', { name: 'Update Member' }).click();

    await Promise.all([
        page.getByRole('button', { name: 'Yes, update existing time' }).click(),
        page.waitForRequest(
            async (request) =>
                request.url().includes('/members/') &&
                request.method() === 'PUT' &&
                request.postDataJSON().billable_rate === newBillableRate * 100
        ),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/organizations/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200 &&
                (await response.json()).data.billable_rate === newBillableRate * 100
        ),
    ]);
});

async function createPlaceholderMemberViaImport(page: Page, placeholderName: string) {
    const placeholderEmail = `placeholder+${Math.floor(Math.random() * 100000)}@solidtime-import.test`;
    const csvContent = [
        'User,Email,Client,Project,Task,Description,Billable,Start date,Start time,End date,End time,Tags',
        `${placeholderName},${placeholderEmail},,,,Imported entry,No,2024-01-01,09:00:00,2024-01-01,10:00:00,`,
    ].join('\n');

    // Write CSV to a temp file for upload
    const tmpDir = os.tmpdir();
    const tmpFile = path.join(tmpDir, `import-${Date.now()}.csv`);
    fs.writeFileSync(tmpFile, csvContent);

    await page.goto(PLAYWRIGHT_BASE_URL + '/import');

    // Select "Toggl Time Entries" import type
    await page.locator('select#importType').selectOption({ label: 'Toggl Time Entries' });

    // Upload the CSV file
    await page.locator('input[type="file"]').setInputFiles(tmpFile);

    // Click Import and wait for success
    await Promise.all([
        page.getByRole('button', { name: 'Import Data' }).click(),
        page.waitForResponse(
            (response) => response.url().includes('/import') && response.status() === 200
        ),
    ]);

    // Close the result modal
    await page.getByRole('button', { name: 'Close' }).click();

    // Clean up temp file
    fs.unlinkSync(tmpFile);
}

test('test that changing role of placeholder member is rejected', async ({ page }) => {
    const placeholderName = 'RoleChange ' + Math.floor(Math.random() * 10000);

    // Create a placeholder member via import
    await createPlaceholderMemberViaImport(page, placeholderName);

    // Go to members page and verify placeholder exists with role "Placeholder"
    await goToMembersPage(page);
    const memberRow = page.getByRole('row').filter({ hasText: placeholderName });
    await expect(memberRow).toBeVisible();
    await expect(memberRow.getByText('Placeholder', { exact: true })).toBeVisible();

    // Open the edit modal for the placeholder member
    await memberRow.getByRole('button').click();
    await page.getByRole('menuitem').getByText('Edit').click();
    await expect(page.getByRole('dialog')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Update Member' })).toBeVisible();

    // Change role to Employee
    const roleSelect = page.getByRole('dialog').getByRole('combobox').first();
    await roleSelect.click();
    await expect(page.getByRole('option', { name: 'Employee' })).toBeVisible();
    await page.getByRole('option', { name: 'Employee' }).click();
    await expect(roleSelect).toContainText('Employee');

    // Submit the change - the API should reject it with 400
    await Promise.all([
        page.getByRole('button', { name: 'Update Member' }).click(),
        page.waitForResponse(
            (response) =>
                response.url().includes('/members/') &&
                response.request().method() === 'PUT' &&
                response.status() === 400
        ),
    ]);

    // Verify error notification is shown
    await expect(page.getByText('Failed to update member')).toBeVisible();
});

test('test that changing member role updates the role in the member table', async ({
    page,
    browser,
}) => {
    const memberId = Math.floor(Math.random() * 100000);
    const memberEmail = `member+${memberId}@rolechange.test`;

    // Invite and accept a new Employee member
    await inviteAndAcceptMember(page, browser, 'Jane Smith', memberEmail, 'Employee');

    // Verify the new member appears with the Employee role
    await goToMembersPage(page);
    const memberRow = page.getByRole('row').filter({ hasText: 'Jane Smith' });
    await expect(memberRow).toBeVisible();
    await expect(memberRow.getByText('Employee', { exact: true })).toBeVisible();

    // Open the edit modal
    await memberRow.getByRole('button').click();
    await page.getByRole('menuitem').getByText('Edit').click();
    await expect(page.getByRole('dialog')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Update Member' })).toBeVisible();

    // Change role to Manager
    const roleSelect = page.getByRole('dialog').getByRole('combobox').first();
    await roleSelect.click();
    await expect(page.getByRole('option', { name: 'Manager' })).toBeVisible();
    await page.getByRole('option', { name: 'Manager' }).click();
    await expect(roleSelect).toContainText('Manager');

    // Submit the change and verify the API call succeeds
    await Promise.all([
        page.getByRole('button', { name: 'Update Member' }).click(),
        page.waitForResponse(
            (response) =>
                response.url().includes('/members/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
    ]);

    // Verify dialog closed
    await expect(page.getByRole('dialog')).not.toBeVisible();

    // Verify the role updated in the table
    await expect(memberRow.getByText('Manager', { exact: true })).toBeVisible();
});

test('test that merging a placeholder member works', async ({ page }) => {
    const placeholderName = 'Merge Target ' + Math.floor(Math.random() * 10000);

    // Create a placeholder member via import
    await createPlaceholderMemberViaImport(page, placeholderName);

    // Go to members page
    await goToMembersPage(page);
    await expect(page.getByText(placeholderName)).toBeVisible();

    // Find the placeholder member row and open actions menu
    const placeholderRow = page.getByRole('row').filter({ hasText: placeholderName });
    await placeholderRow.getByRole('button').click();

    // Click Merge
    await page.getByTestId('member_merge').click();
    await expect(page.getByRole('dialog')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Merge Member' })).toBeVisible();

    // Select the current user (the owner) as merge target via MemberCombobox
    // The MemberCombobox renders a Button as trigger; clicking it opens the popover with the combobox input
    await page.getByRole('dialog').getByRole('button', { name: 'Select a member...' }).click();

    // Wait for dropdown options to load
    const firstOption = page.getByRole('option').first();
    await expect(firstOption).toBeVisible({ timeout: 10000 });
    await firstOption.click();

    // Submit merge
    await Promise.all([
        page.getByRole('button', { name: 'Merge Member' }).click(),
        page.waitForResponse(
            (response) =>
                response.url().includes('/member/') &&
                response.url().includes('/merge-into') &&
                response.ok()
        ),
    ]);

    // Wait for dialog to close after successful merge
    await expect(page.getByRole('dialog')).not.toBeVisible();

    // Verify placeholder member is no longer in the members table
    await expect(page.getByRole('main').getByText(placeholderName)).not.toBeVisible();
});
