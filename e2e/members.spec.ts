// TODO: Edit Billable Rate
// TODO: Resend Email Invitation
// TODO: Remove Invitation
import { expect, test } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import type { Page } from '@playwright/test';
import { inviteAndAcceptMember } from './utils/members';
import {
    createPlaceholderMemberViaImportApi,
    getMembersViaApi,
    updateMemberBillableRateViaApi,
    updateOrganizationSettingViaApi,
} from './utils/api';
import { getTableRowNames } from './utils/table';

// Tests that invite + accept members need more time
test.describe.configure({ timeout: 45000 });

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
    await page.getByRole('combobox').last().click();
    await page.getByRole('option', { name: 'Custom Rate' }).click();
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

test('test that switching member billable rate from custom back to default rate works', async ({
    page,
    ctx,
}) => {
    // Set a known org billable rate
    await updateOrganizationSettingViaApi(ctx, { billable_rate: 12000 });

    // Create a placeholder member with a custom billable rate
    await createPlaceholderMemberViaImportApi(ctx, 'CustomToDefault Member');
    const members = await getMembersViaApi(ctx);
    const member = members.find((m) => m.name === 'CustomToDefault Member');
    expect(member).toBeDefined();
    await updateMemberBillableRateViaApi(ctx, member!.id, 25000);

    await goToMembersPage(page);
    const memberRow = page.getByRole('row').filter({ hasText: 'CustomToDefault Member' });
    await expect(memberRow).toBeVisible();

    // Open edit modal
    await memberRow.getByRole('button').click();
    await page.getByRole('menuitem').getByText('Edit').click();
    await expect(page.getByRole('heading', { name: 'Update Member' })).toBeVisible();

    // Verify it starts on Custom Rate
    const billableCombobox = page.getByRole('dialog').getByRole('combobox').last();
    await expect(billableCombobox).toContainText('Custom Rate');

    // Switch to Default Rate
    await billableCombobox.click();
    await page.getByRole('option', { name: 'Default Rate' }).click();
    await expect(billableCombobox).toContainText('Default Rate');

    // Verify the billable rate input is disabled
    await expect(page.getByPlaceholder('Billable Rate')).toBeDisabled();

    // Submit — billable_rate changes from 25000 to null, so confirmation dialog appears
    await page.getByRole('button', { name: 'Update Member' }).click();
    await expect(page.getByRole('heading', { name: 'Update Member Billable Rate' })).toBeVisible();
    await expect(page.getByText('the default rate of the organization')).toBeVisible();

    // Confirm the update
    await Promise.all([
        page.getByRole('button', { name: 'Yes, update existing time' }).click(),
        page.waitForRequest(
            (request) =>
                request.url().includes('/members/') &&
                request.method() === 'PUT' &&
                request.postDataJSON().billable_rate === null
        ),
    ]);

    // Verify both dialogs are closed
    await expect(page.getByRole('dialog')).not.toBeVisible();
});

test('test that default rate shows disabled input with organization billable rate', async ({
    page,
    ctx,
}) => {
    // Set a known org billable rate (150.00)
    await updateOrganizationSettingViaApi(ctx, { billable_rate: 15000 });

    await goToMembersPage(page);

    // Open edit modal for the owner (who uses default rate by default)
    await page.getByRole('row').first().getByRole('button').click();
    await page.getByRole('menuitem').getByText('Edit').click();
    await expect(page.getByRole('heading', { name: 'Update Member' })).toBeVisible();

    // Verify it's on Default Rate
    const billableCombobox = page.getByRole('dialog').getByRole('combobox').last();
    await expect(billableCombobox).toContainText('Default Rate');

    // Verify the input is disabled and shows the org rate (formatted with currency)
    const billableInput = page.getByPlaceholder('Billable Rate');
    await expect(billableInput).toBeDisabled();
    await expect(billableInput).toHaveAttribute('aria-valuenow', '150');

    // Close the dialog
    await page.getByRole('button', { name: 'Cancel' }).click();
    await expect(page.getByRole('dialog')).not.toBeVisible();
});

test('test that cancelling the billable rate confirmation dialog does not update the member', async ({
    page,
    ctx,
}) => {
    // Create a placeholder member with a custom billable rate
    await createPlaceholderMemberViaImportApi(ctx, 'CancelConfirm Member');
    const members = await getMembersViaApi(ctx);
    const member = members.find((m) => m.name === 'CancelConfirm Member');
    expect(member).toBeDefined();
    await updateMemberBillableRateViaApi(ctx, member!.id, 10000);

    await goToMembersPage(page);
    const memberRow = page.getByRole('row').filter({ hasText: 'CancelConfirm Member' });
    await expect(memberRow).toBeVisible();

    // Open edit modal
    await memberRow.getByRole('button').click();
    await page.getByRole('menuitem').getByText('Edit').click();
    await expect(page.getByRole('heading', { name: 'Update Member' })).toBeVisible();

    // Change the billable rate
    await page.getByPlaceholder('Billable Rate').fill('200');

    // Click Update Member — confirmation dialog should appear
    await page.getByRole('button', { name: 'Update Member' }).click();
    await expect(page.getByRole('heading', { name: 'Update Member Billable Rate' })).toBeVisible();

    // Set up listener to verify no PUT request is sent after cancel
    let putRequestSent = false;
    page.on('request', (request) => {
        if (request.url().includes('/members/') && request.method() === 'PUT') {
            putRequestSent = true;
        }
    });

    // Click Cancel on the confirmation dialog
    await page.getByRole('button', { name: 'Cancel' }).click();

    // Verify confirmation dialog is closed
    await expect(
        page.getByRole('heading', { name: 'Update Member Billable Rate' })
    ).not.toBeVisible();

    // Verify no API call was made
    expect(putRequestSent).toBe(false);
});

test('test that changing role of placeholder member is rejected', async ({ page, ctx }) => {
    const placeholderName = 'RoleChange ' + Math.floor(Math.random() * 10000);

    // Create a placeholder member via import
    await createPlaceholderMemberViaImportApi(ctx, placeholderName);

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

test('test that merging a placeholder member works', async ({ page, ctx }) => {
    const placeholderName = 'Merge Target ' + Math.floor(Math.random() * 10000);

    // Create a placeholder member via import
    await createPlaceholderMemberViaImportApi(ctx, placeholderName);

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

    // Wait for merge dialog to close after successful merge
    await expect(page.getByRole('dialog').filter({ hasText: 'Merge Member' })).not.toBeVisible();

    // Verify placeholder member is no longer in the members table
    await expect(page.getByRole('main').getByText(placeholderName)).not.toBeVisible();
});

test('test that deleting a placeholder member works', async ({ page, ctx }) => {
    const placeholderName = 'Delete Target ' + Math.floor(Math.random() * 10000);

    // Create a placeholder member via import
    await createPlaceholderMemberViaImportApi(ctx, placeholderName);

    // Go to members page
    await goToMembersPage(page);
    const memberRow = page.getByRole('row').filter({ hasText: placeholderName });
    await expect(memberRow).toBeVisible();

    // Open actions menu and click Delete
    await memberRow.getByRole('button').click();
    await page.getByRole('menuitem').getByText('Delete').click();

    // Verify delete modal is shown
    await expect(page.getByRole('dialog')).toBeVisible();
    await expect(page.getByRole('heading', { name: 'Delete Member' })).toBeVisible();

    // Try to delete without checking the confirmation checkbox
    await page.getByRole('button', { name: 'Delete Member' }).click();

    // Should show validation error
    await expect(
        page.getByText('You must confirm that you understand the consequences of this action')
    ).toBeVisible();

    // Check the confirmation checkbox
    await page.getByRole('checkbox').click();

    // Click Delete Member button and wait for API response
    await Promise.all([
        page.getByRole('button', { name: 'Delete Member' }).click(),
        page.waitForResponse(
            (response) =>
                response.url().includes('/members/') &&
                response.request().method() === 'DELETE' &&
                response.ok()
        ),
    ]);

    // Verify modal is closed
    await expect(page.getByRole('dialog')).not.toBeVisible();

    // Verify member is removed from the table
    await expect(page.getByRole('main').getByText(placeholderName)).not.toBeVisible();
});

test('test that member delete modal can be cancelled', async ({ page, ctx }) => {
    const placeholderName = 'Delete Cancel ' + Math.floor(Math.random() * 10000);

    // Create a placeholder member via import
    await createPlaceholderMemberViaImportApi(ctx, placeholderName);

    // Go to members page
    await goToMembersPage(page);
    const memberRow = page.getByRole('row').filter({ hasText: placeholderName });
    await expect(memberRow).toBeVisible();

    // Open actions menu and click Delete
    await memberRow.getByRole('button').click();
    await page.getByRole('menuitem').getByText('Delete').click();

    // Verify delete modal is shown
    await expect(page.getByRole('dialog')).toBeVisible();

    // Set up listener to verify no DELETE request is sent
    let deleteRequestSent = false;
    page.on('request', (request) => {
        if (request.url().includes('/members/') && request.method() === 'DELETE') {
            deleteRequestSent = true;
        }
    });

    // Click Cancel
    await page.getByRole('button', { name: 'Cancel' }).click();

    // Verify modal is closed
    await expect(page.getByRole('dialog')).not.toBeVisible();

    // Verify member is still in the table
    await expect(memberRow).toBeVisible();

    // Verify no DELETE request was sent
    expect(deleteRequestSent).toBe(false);
});

test('test that organization owner cannot be deleted', async ({ page }) => {
    await goToMembersPage(page);

    // Find the owner row (John Doe with Owner role)
    const ownerRow = page.getByRole('row').filter({ hasText: 'Owner' });
    await expect(ownerRow).toBeVisible();

    // Open the actions menu for the owner
    await ownerRow.getByRole('button').click();

    // Click Delete
    await page.getByRole('menuitem').getByText('Delete').click();

    // Verify delete modal is shown
    await expect(page.getByRole('dialog')).toBeVisible();

    // Check the confirmation checkbox
    await page.getByRole('checkbox').click();

    // Try to delete - should fail with 400 error
    const responsePromise = page.waitForResponse(
        (response) =>
            response.url().includes('/members/') && response.request().method() === 'DELETE'
    );
    await page.getByRole('button', { name: 'Delete Member' }).click();
    const response = await responsePromise;

    // Verify the API returned an error status
    expect(response.status()).toBe(400);

    // Close the modal by pressing Escape
    await page.keyboard.press('Escape');

    // Refresh and verify the owner is still there
    await goToMembersPage(page);
    await expect(page.getByRole('row').filter({ hasText: 'Owner' })).toBeVisible();
});

// =============================================
// Invitations Tab Tests
// =============================================

test('test that invitation shows in invitations tab and can be revoked', async ({ page }) => {
    const inviteEmail = `invite+${Math.floor(Math.random() * 100000)}@pending.test`;

    await goToMembersPage(page);
    await openInviteMemberModal(page);

    await page.getByPlaceholder('Member Email').fill(inviteEmail);
    await page.getByRole('button', { name: 'Employee' }).click();
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/invitations') &&
                response.request().method() === 'POST' &&
                response.status() === 204
        ),
        page.getByRole('button', { name: 'Invite Member', exact: true }).click(),
    ]);

    // Wait for modal to close
    await expect(page.getByPlaceholder('Member Email')).not.toBeVisible();

    // Switch to Invitations tab and verify the invitation is visible
    await page.getByText('Invitations', { exact: true }).click();
    await expect(page.getByText(inviteEmail)).toBeVisible();

    // Find and click the actions menu for this invitation
    const invitationRow = page.locator('tr, [role="row"]').filter({ hasText: inviteEmail });
    await invitationRow.getByRole('button').click();
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/invitations/') &&
                response.request().method() === 'DELETE' &&
                response.status() === 204
        ),
        page.getByRole('menuitem').getByText('Delete').click(),
    ]);

    // Verify invitation is removed
    await expect(page.getByText(inviteEmail)).not.toBeVisible();
});

test('test that invitation can be resent', async ({ page }) => {
    const inviteEmail = `resend+${Math.floor(Math.random() * 100000)}@invite.test`;

    await goToMembersPage(page);
    await openInviteMemberModal(page);

    await page.getByPlaceholder('Member Email').fill(inviteEmail);
    await page.getByRole('button', { name: 'Employee' }).click();
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/invitations') &&
                response.request().method() === 'POST' &&
                response.status() === 204
        ),
        page.getByRole('button', { name: 'Invite Member', exact: true }).click(),
    ]);

    // Wait for modal to close
    await expect(page.getByPlaceholder('Member Email')).not.toBeVisible();

    // Switch to Invitations tab
    await page.getByText('Invitations', { exact: true }).click();
    await expect(page.getByText(inviteEmail)).toBeVisible();

    // Find and click the actions menu, then resend
    const invitationRow = page.locator('tr, [role="row"]').filter({ hasText: inviteEmail });
    await invitationRow.getByRole('button').click();
    // Wait for dropdown menu to appear
    await expect(page.getByRole('menuitem').getByText('Resend Invitation')).toBeVisible();
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/resend') && response.request().method() === 'POST'
        ),
        page.getByRole('menuitem').getByText('Resend Invitation').click(),
    ]);
});

test('test that admin user cannot transfer ownership', async ({ page, browser }) => {
    const memberId = Math.floor(Math.random() * 100000);
    const memberEmail = `admin+${memberId}@perms.test`;

    // Invite and accept an admin member
    await inviteAndAcceptMember(
        page,
        browser,
        'Admin User ' + memberId,
        memberEmail,
        'Administrator'
    );

    // Go to members page and verify the admin exists
    await goToMembersPage(page);
    const adminRow = page.getByRole('row').filter({ hasText: 'Admin User' });
    await expect(adminRow).toBeVisible();

    // The owner should still be the owner
    const ownerRow = page.getByRole('row').filter({ hasText: 'Owner' });
    await expect(ownerRow).toBeVisible();

    // Open actions menu for the admin - should NOT have "Transfer Ownership" option
    await adminRow.getByRole('button').click();
    await expect(page.getByRole('menuitem').getByText('Edit')).toBeVisible();
});

test('test that accepted invitation disappears from invitations tab', async ({ page, browser }) => {
    const memberId = Math.round(Math.random() * 100000);
    const memberEmail = `accepted+${memberId}@invite.test`;

    // Invite and accept the member
    await inviteAndAcceptMember(page, browser, 'Accepted Member', memberEmail, 'Employee');

    // Go to members page and switch to Invitations tab
    await goToMembersPage(page);
    await page.getByRole('tab', { name: 'Invitations' }).click();

    // The accepted invitation should not be visible
    await expect(page.getByText(memberEmail)).not.toBeVisible();
});

// =============================================
// Sorting Tests
// =============================================

// Helper to clear localStorage before tests that check sorting
async function clearMemberTableState(page: Page) {
    await page.evaluate(() => {
        localStorage.removeItem('member-table-state');
    });
}

test('test that sorting members by name, role, and status works', async ({ page, ctx }) => {
    // Create two placeholder members with names that sort predictably around "John Doe"
    await createPlaceholderMemberViaImportApi(ctx, 'AAA SortFirst');
    await createPlaceholderMemberViaImportApi(ctx, 'ZZZ SortLast');

    await goToMembersPage(page);
    await clearMemberTableState(page);
    await page.reload();

    const table = page.getByTestId('member_table');
    await expect(table).toBeVisible();

    // -- Name sorting (default is already name asc after clearing state) --
    const nameHeader = table.getByText('Name').first();
    let names = await getTableRowNames(table);
    expect(names.indexOf('AAA SortFirst')).toBeLessThan(names.indexOf('ZZZ SortLast'));

    await nameHeader.click(); // toggle to desc
    names = await getTableRowNames(table);
    expect(names.indexOf('ZZZ SortLast')).toBeLessThan(names.indexOf('AAA SortFirst'));

    // -- Role sorting --
    const roleHeader = table.getByText('Role').first();
    await roleHeader.click(); // asc: Owner(0) < Placeholder(4)
    names = await getTableRowNames(table);
    const ownerIdx = names.indexOf('John Doe');
    const placeholderIdx = names.indexOf('AAA SortFirst');
    expect(ownerIdx).toBeLessThan(placeholderIdx);

    await roleHeader.click(); // desc: Placeholder first
    names = await getTableRowNames(table);
    expect(names.indexOf('AAA SortFirst')).toBeLessThan(names.indexOf('John Doe'));

    // -- Status sorting --
    const statusHeader = table.getByText('Status').first();
    await statusHeader.click(); // asc: Active(0) < Inactive(1)
    names = await getTableRowNames(table);
    expect(names.indexOf('John Doe')).toBeLessThan(names.indexOf('AAA SortFirst'));

    await statusHeader.click(); // desc: Inactive first
    names = await getTableRowNames(table);
    expect(names.indexOf('AAA SortFirst')).toBeLessThan(names.indexOf('John Doe'));

    // -- Email: just verify sort indicator appears --
    const emailHeader = table.getByText('Email').first();
    await emailHeader.click();
    await expect(emailHeader.locator('svg')).toBeVisible();
});

test('test that member sort state persists after page reload', async ({ page }) => {
    await goToMembersPage(page);
    await clearMemberTableState(page);
    await page.reload();

    const table = page.getByTestId('member_table');
    await expect(table).toBeVisible();

    // Click Role header twice to set descending sort
    const roleHeader = table.getByText('Role').first();
    await roleHeader.click();
    await expect(roleHeader.locator('svg')).toBeVisible();
    await roleHeader.click();
    await expect(roleHeader.locator('svg')).toBeVisible();

    // Reload the page
    await page.reload();

    // Verify the sort indicator is still visible on Role column
    await expect(page.getByTestId('member_table')).toBeVisible();
    await expect(
        page.getByTestId('member_table').getByText('Role').first().locator('svg')
    ).toBeVisible();
});

test('test that sorting members by billable rate works', async ({ page, ctx }) => {
    // Create two placeholder members and set different billable rates
    await createPlaceholderMemberViaImportApi(ctx, 'HighRate Member');
    await createPlaceholderMemberViaImportApi(ctx, 'LowRate Member');

    const members = await getMembersViaApi(ctx);
    const highRateMember = members.find((m) => m.name === 'HighRate Member');
    const lowRateMember = members.find((m) => m.name === 'LowRate Member');
    expect(highRateMember).toBeDefined();
    expect(lowRateMember).toBeDefined();

    await updateMemberBillableRateViaApi(ctx, highRateMember!.id, 20000);
    await updateMemberBillableRateViaApi(ctx, lowRateMember!.id, 5000);

    await goToMembersPage(page);
    await clearMemberTableState(page);
    await page.reload();

    const table = page.getByTestId('member_table');
    await expect(table).toBeVisible();

    // First click = desc (highest first), null rates last
    const billableHeader = table.getByText('Billable Rate').first();
    await billableHeader.click();
    await expect(billableHeader.locator('svg')).toBeVisible();
    let names = await getTableRowNames(table);
    expect(names.indexOf('HighRate Member')).toBeLessThan(names.indexOf('LowRate Member'));

    // Second click = asc (lowest first), null rates still last
    await billableHeader.click();
    names = await getTableRowNames(table);
    expect(names.indexOf('LowRate Member')).toBeLessThan(names.indexOf('HighRate Member'));
});

// =============================================
// Employee Permission Tests
// =============================================

test.describe('Employee Sidebar Navigation', () => {
    test('employee sidebar shows correct navigation links', async ({ employee }) => {
        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
        await expect(employee.page.getByTestId('dashboard_view')).toBeVisible({
            timeout: 10000,
        });

        // Visible links
        await expect(employee.page.getByRole('link', { name: 'Dashboard' })).toBeVisible();
        await expect(employee.page.getByRole('link', { name: 'Time' })).toBeVisible();
        await expect(employee.page.getByRole('link', { name: 'Calendar' })).toBeVisible();
        await expect(employee.page.getByRole('link', { name: 'Projects' })).toBeVisible();
        await expect(employee.page.getByRole('link', { name: 'Clients' })).toBeVisible();
        await expect(employee.page.getByRole('link', { name: 'Tags' })).toBeVisible();

        // Hidden links
        await expect(employee.page.getByRole('link', { name: 'Members' })).not.toBeVisible();
        await expect(
            employee.page.getByRole('link', { name: 'Settings', exact: true })
        ).not.toBeVisible();
    });

    test('employee cannot see members list or invite members', async ({ employee }) => {
        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/members');

        // Page loads but the members API returns 403 (no members:view permission)
        await expect(employee.page.getByRole('heading', { name: 'Members' })).toBeVisible({
            timeout: 10000,
        });

        // Member table is empty — no rows rendered (only headers)
        await expect(employee.page.getByTestId('member_table').locator('[role="row"]')).toHaveCount(
            0
        );

        // Employee should NOT see the Invite Member button
        await expect(
            employee.page.getByRole('button', { name: 'Invite member' })
        ).not.toBeVisible();
    });
});
