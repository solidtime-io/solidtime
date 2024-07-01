import { expect, test } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';

async function goToOrganizationSettings(page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
    await page.locator('[data-testid="organization_switcher"]:visible').click();
    await page.getByText('Organization Settings').click();
}

test('test that organization name can be updated', async ({ page }) => {
    await goToOrganizationSettings(page);
    await page.getByLabel('Organization Name').fill('NEW ORG NAME');
    await page.getByLabel('Organization Name').press('Enter');
    await page.getByLabel('Organization Name').press('Meta+r');
    await expect(
        page.locator('[data-testid="organization_switcher"]:visible')
    ).toContainText('NEW ORG NAME');
});

test('test that new manager can be invited', async ({ page }) => {
    await goToOrganizationSettings(page);
    const editorId = Math.round(Math.random() * 10000);
    await page.getByLabel('Email').fill(`new+${editorId}@editor.test`);
    await page.getByRole('button', { name: 'Manager' }).click();
    await Promise.all([
        page.getByRole('button', { name: 'Add', exact: true }).click(),
        expect(page.getByRole('main')).toContainText(
            `new+${editorId}@editor.test`
        ),
    ]);
});

test('test that new employee can be invited', async ({ page }) => {
    await goToOrganizationSettings(page);
    const editorId = Math.round(Math.random() * 10000);
    await page.getByLabel('Email').fill(`new+${editorId}@editor.test`);
    await page.getByRole('button', { name: 'Employee' }).click();
    await Promise.all([
        page.getByRole('button', { name: 'Add', exact: true }).click(),
        await expect(page.getByRole('main')).toContainText(
            `new+${editorId}@editor.test`
        ),
    ]);
});

test('test that new admin can be invited', async ({ page }) => {
    await goToOrganizationSettings(page);
    const adminId = Math.round(Math.random() * 10000);
    await page.getByLabel('Email').fill(`new+${adminId}@admin.test`);
    await page.getByRole('button', { name: 'Administrator' }).click();
    await Promise.all([
        page.getByRole('button', { name: 'Add', exact: true }).click(),
        expect(page.getByRole('main')).toContainText(
            `new+${adminId}@admin.test`
        ),
    ]);
});
test('test that error shows if no role is selected', async ({ page }) => {
    await goToOrganizationSettings(page);
    const noRoleId = Math.round(Math.random() * 10000);

    await page.getByLabel('Email').fill(`new+${noRoleId}@norole.test`);
    await Promise.all([
        page.getByRole('button', { name: 'Add', exact: true }).click(),
        expect(page.getByRole('main')).toContainText(
            'The role field is required.'
        ),
    ]);
});

test('test that organization billable rate can be updated', async ({
    page,
}) => {
    await goToOrganizationSettings(page);
    const newBillableRate = Math.round(Math.random() * 10000);
    await page.getByLabel('Organization Billable Rate').click();
    await page
        .getByLabel('Organization Billable Rate')
        .fill(newBillableRate.toString());
    await page
        .locator('button')
        .filter({ hasText: /^Save$/ })
        .click();
    await Promise.all([
        page
            .getByRole('button', { name: 'No, only for new time entries' })
            .click(),
        page.waitForRequest(
            async (request) =>
                request.url().includes('/organizations/') &&
                request.method() === 'PUT' &&
                request.postDataJSON().billable_rate ===
                    newBillableRate * 100 &&
                request.postDataJSON().billable_rate_update_time_entries ===
                    false
        ),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/organizations/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200 &&
                (await response.json()).data.billable_rate ===
                    newBillableRate * 100
        ),
    ]);
});

test('test that organization billable rate can be updated with all existing time entries', async ({
    page,
}) => {
    await goToOrganizationSettings(page);
    const newBillableRate = Math.round(Math.random() * 10000);
    await page.getByLabel('Organization Billable Rate').click();
    await page
        .getByLabel('Organization Billable Rate')
        .fill(newBillableRate.toString());
    await page
        .locator('button')
        .filter({ hasText: /^Save$/ })
        .click();
    await Promise.all([
        page
            .getByRole('button', { name: 'Yes, update existing time entries' })
            .click(),
        page.waitForRequest(
            async (request) =>
                request.url().includes('/organizations/') &&
                request.method() === 'PUT' &&
                request.postDataJSON().billable_rate ===
                    newBillableRate * 100 &&
                request.postDataJSON().billable_rate_update_time_entries ===
                    true
        ),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/organizations/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200 &&
                (await response.json()).data.billable_rate ===
                    newBillableRate * 100
        ),
    ]);
});

// TODO: Add Test for import
