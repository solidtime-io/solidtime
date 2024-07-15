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
                request.postDataJSON().billable_rate === newBillableRate * 100
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
