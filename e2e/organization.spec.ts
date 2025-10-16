import { expect, test } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';

async function goToOrganizationSettings(page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
    await page.locator('[data-testid="organization_switcher"]:visible').click();
    await page.getByText('Organization Settings').click();
}

async function createTimeEntry(page, duration: string) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/time');

    // Open the dropdown menu and click "Manual time entry"
    await page.getByRole('button', { name: 'Time entry actions' }).click();
    await page.getByRole('menuitem', { name: 'Manual time entry' }).click();

    // Fill in the time entry details
    await page.getByTestId('time_entry_description').fill('Test time entry');

    // Set duration
    await page.locator('[role="dialog"] input[name="Duration"]').fill(duration);
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');

    // Submit the time entry
    await Promise.all([
        page.getByRole('button', { name: 'Create Time Entry' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/time-entries') &&
                response.request().method() === 'POST' &&
                response.status() === 201
        ),
    ]);
}

test('test that organization name can be updated', async ({ page }) => {
    await goToOrganizationSettings(page);
    await page.getByLabel('Organization Name').fill('NEW ORG NAME');
    await page.getByLabel('Organization Name').press('Enter');
    await page.getByLabel('Organization Name').press('Meta+r');
    await expect(page.locator('[data-testid="organization_switcher"]:visible')).toContainText(
        'NEW ORG NAME'
    );
});

test('test that organization billable rate can be updated with all existing time entries', async ({
    page,
}) => {
    await goToOrganizationSettings(page);
    const newBillableRate = Math.round(Math.random() * 10000);
    await page.getByLabel('Organization Billable Rate').click();
    await page.getByLabel('Organization Billable Rate').fill(newBillableRate.toString());
    await page
        .locator('form')
        .filter({ hasText: 'Organization Billable' })
        .getByRole('button', { name: 'Save' })
        .click();

    await Promise.all([
        page.getByRole('button', { name: 'Yes, update existing time entries' }).click(),
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
                (await response.json()).data.billable_rate === newBillableRate * 100
        ),
    ]);
});

test('test that organization format settings can be updated', async ({ page }) => {
    await goToOrganizationSettings(page);

    // Test number format
    await page.getByLabel('Number Format').click();
    await page.getByRole('option', { name: '1,111.11' }).click();
    await Promise.all([
        page
            .locator('form')
            .filter({ hasText: 'Number Format' })
            .getByRole('button', { name: 'Save' })
            .click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/organizations/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200 &&
                (await response.json()).data.number_format === 'comma-point'
        ),
    ]);

    // Test currency format
    await page.getByLabel('Currency Format').click();
    await page.getByRole('option', { name: '111 EUR' }).click();
    await Promise.all([
        page
            .locator('form')
            .filter({ hasText: 'Currency Format' })
            .getByRole('button', { name: 'Save' })
            .click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/organizations/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200 &&
                (await response.json()).data.currency_format === 'iso-code-after-with-space'
        ),
    ]);

    // Test date format
    await page.getByLabel('Date Format').click();
    await page.getByRole('option', { name: 'DD/MM/YYYY' }).click();
    await Promise.all([
        page
            .locator('form')
            .filter({ hasText: 'Date Format' })
            .getByRole('button', { name: 'Save' })
            .click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/organizations/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200 &&
                (await response.json()).data.date_format === 'slash-separated-dd-mm-yyyy'
        ),
    ]);

    // Test time format
    await page.getByLabel('Time Format').click();
    await page.getByRole('option', { name: '24-hour clock' }).click();
    await Promise.all([
        page
            .locator('form')
            .filter({ hasText: 'Time Format' })
            .getByRole('button', { name: 'Save' })
            .click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/organizations/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200 &&
                (await response.json()).data.time_format === '24-hours'
        ),
    ]);

    // Test interval format
    await page.getByLabel('Time Duration Format').click();
    await page.getByRole('option', { name: '12:03', exact: true }).click();
    await Promise.all([
        page
            .locator('form')
            .filter({ hasText: 'Time Duration Format' })
            .getByRole('button', { name: 'Save' })
            .click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/organizations/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200 &&
                (await response.json()).data.interval_format === 'hours-minutes-colon-separated'
        ),
    ]);
});

test('test that format settings are reflected in the dashboard', async ({ page }) => {
    // check that 0h 00min is displayed
    await expect(page.getByText('0h 00min', { exact: true }).nth(0)).toBeVisible();

    // First set the format settings
    await goToOrganizationSettings(page);

    // Set number format to comma-point
    await page.getByLabel('Number Format').click();
    await page.getByRole('option', { name: '1,111.11' }).click();

    // Set currency format to symbol-after
    await page.getByLabel('Currency Format').click();
    await page.getByRole('option', { name: '111€' }).click();

    // Set interval format to hours-minutes-colon-separated
    await page.getByLabel('Time Duration Format').click();
    await page.getByRole('option', { name: '12:03', exact: true }).click();

    // Set date format to DD/MM/YYYY
    await page.getByLabel('Date Format').click();
    await page.getByRole('option', { name: 'DD/MM/YYYY' }).click();

    await Promise.all([
        page
            .locator('form')
            .filter({ hasText: 'Time Duration Format' })
            .getByRole('button', { name: 'Save' })
            .click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/organizations/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200 &&
                (await response.json()).data.interval_format === 'hours-minutes-colon-separated' &&
                (await response.json()).data.currency_format === 'symbol-after' &&
                (await response.json()).data.number_format === 'comma-point'
        ),
    ]);

    await createTimeEntry(page, '00:00');

    // Go to dashboard and check the formats
    await page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');

    // Check billable amount format (number and currency)
    await expect(page.getByText('0.00€')).toBeVisible();

    // check that 00:00 is displayed
    await expect(page.getByText('0:00', { exact: true }).nth(0)).toBeVisible();
    // check that 0h 00min is not displayed
    await expect(page.getByText('0h 00min', { exact: true }).nth(0)).not.toBeVisible();

    // check that the current date is displayed in the dd/mm/yyyy format on the time page
    await page.goto(PLAYWRIGHT_BASE_URL + '/time');
    await expect(
        page.getByText(new Date().toLocaleDateString('en-GB'), { exact: true }).nth(0)
    ).toBeVisible();
});

// TODO: Test 12-hour clock format
