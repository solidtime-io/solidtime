// TODO: Edit Billable Rate
// TODO: Resend Email Invitation
// TODO: Remove Invitation
import { expect, test } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';

async function goToMembersPage(page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/members');
}

async function openInviteMemberModal(page) {
    await Promise.all([
        page.getByRole('button', { name: 'Invite Member' }).click(),
        expect(page.getByPlaceholder('Member Email')).toBeVisible(),
    ]);
}

test('test that new manager can be invited', async ({ page }) => {
    await goToMembersPage(page);
    await openInviteMemberModal(page);
    const editorId = Math.round(Math.random() * 10000);
    await page.getByLabel('Email').fill(`new+${editorId}@editor.test`);
    await page.getByRole('button', { name: 'Manager' }).click();
    await Promise.all([
        page.getByRole('button', { name: 'Invite Member', exact: true }).click(),
        expect(page.getByRole('main')).toContainText(`new+${editorId}@editor.test`),
    ]);
});

test('test that new employee can be invited', async ({ page }) => {
    await goToMembersPage(page);
    await openInviteMemberModal(page);
    const editorId = Math.round(Math.random() * 10000);
    await page.getByLabel('Email').fill(`new+${editorId}@editor.test`);
    await page.getByRole('button', { name: 'Employee' }).click();
    await Promise.all([
        page.getByRole('button', { name: 'Invite Member', exact: true }).click(),
        await expect(page.getByRole('main')).toContainText(`new+${editorId}@editor.test`),
    ]);
});

test('test that new admin can be invited', async ({ page }) => {
    await goToMembersPage(page);
    await openInviteMemberModal(page);
    const adminId = Math.round(Math.random() * 10000);
    await page.getByLabel('Email').fill(`new+${adminId}@admin.test`);
    await page.getByRole('button', { name: 'Administrator' }).click();
    await Promise.all([
        page.getByRole('button', { name: 'Invite Member', exact: true }).click(),
        expect(page.getByRole('main')).toContainText(`new+${adminId}@admin.test`),
    ]);
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
