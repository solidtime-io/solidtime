import { expect, test } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL, TEST_USER_PASSWORD } from '../playwright/config';

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
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/api/v1/organizations/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page
            .locator('form')
            .filter({ hasText: 'Organization Name' })
            .getByRole('button', { name: 'Save' })
            .click(),
    ]);
    await page.reload();
    await expect(page.locator('[data-testid="organization_switcher"]:visible')).toContainText(
        'NEW ORG NAME'
    );
});

test('test that organization currency can be updated', async ({ page }) => {
    await goToOrganizationSettings(page);
    await page.getByLabel('Currency', { exact: true }).selectOption('USD');
    await Promise.all([
        page.waitForRequest(
            (request) =>
                request.url().includes('/api/v1/organizations/') &&
                request.method() === 'PUT' &&
                request.postDataJSON().currency === 'USD'
        ),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/api/v1/organizations/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200 &&
                (await response.json()).data.currency === 'USD'
        ),
        page
            .locator('form')
            .filter({ hasText: 'Organization Name' })
            .getByRole('button', { name: 'Save' })
            .click(),
    ]);
    await page.reload();
    await expect(page.getByLabel('Currency', { exact: true })).toHaveValue('USD');
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
    // Wait for time entries to load so organization data is available for date formatting
    await page.waitForResponse(
        (response) => response.url().includes('/time-entries') && response.status() === 200
    );
    await expect(
        page.getByText(new Date().toLocaleDateString('en-GB'), { exact: true }).nth(0)
    ).toBeVisible({ timeout: 10000 });
});

test('test that organization time entry settings can be toggled', async ({ page }) => {
    await goToOrganizationSettings(page);

    const preventOverlappingCheckbox = page.getByLabel(
        'Prevent overlapping time entries (new entries only)'
    );
    const manageTasksCheckbox = page.getByLabel('Allow Employees to manage tasks');

    // Get current states and toggle both
    const wasOverlappingChecked = await preventOverlappingCheckbox.isChecked();
    const wasManageTasksChecked = await manageTasksCheckbox.isChecked();

    if (wasOverlappingChecked) {
        await preventOverlappingCheckbox.uncheck();
    } else {
        await preventOverlappingCheckbox.check();
    }

    if (wasManageTasksChecked) {
        await manageTasksCheckbox.uncheck();
    } else {
        await manageTasksCheckbox.check();
    }

    // Save
    const settingsForm = page.locator('form').filter({ hasText: 'Prevent overlapping' });
    await Promise.all([
        settingsForm.getByRole('button', { name: 'Save' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/organizations/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200 &&
                (await response.json()).data.prevent_overlapping_time_entries ===
                    !wasOverlappingChecked
        ),
    ]);

    // Reload and verify both settings persisted
    await page.reload();
    await expect(preventOverlappingCheckbox).toBeChecked({ checked: !wasOverlappingChecked });
    await expect(manageTasksCheckbox).toBeChecked({ checked: !wasManageTasksChecked });

    // Toggle both back to restore original state
    if (!wasOverlappingChecked) {
        await preventOverlappingCheckbox.uncheck();
    } else {
        await preventOverlappingCheckbox.check();
    }

    if (!wasManageTasksChecked) {
        await manageTasksCheckbox.uncheck();
    } else {
        await manageTasksCheckbox.check();
    }

    await Promise.all([
        settingsForm.getByRole('button', { name: 'Save' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/organizations/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200 &&
                (await response.json()).data.prevent_overlapping_time_entries ===
                    wasOverlappingChecked
        ),
    ]);
});

test('test that 12-hour clock format can be set', async ({ page }) => {
    await goToOrganizationSettings(page);

    await page.getByLabel('Time Format').click();
    await page.getByRole('option', { name: '12-hour clock' }).click();
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
                (await response.json()).data.time_format === '12-hours'
        ),
    ]);

    // Reload and verify it persisted
    await page.reload();
    await expect(page.getByLabel('Time Format')).toContainText('12-hour clock');

    // Reset back to 24-hour
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
});

test('test that format settings persist after page reload', async ({ page }) => {
    await goToOrganizationSettings(page);

    // Set a specific date format
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
                response.status() === 200
        ),
    ]);

    // Reload and verify it persisted
    await page.reload();
    await expect(page.getByLabel('Date Format')).toContainText('DD/MM/YYYY');
});

// =============================================
// Create, Delete & Switch
// =============================================

test.describe('Organization Create, Delete & Switch', () => {
    async function createOrganization(page, name: string) {
        await page.goto(PLAYWRIGHT_BASE_URL + '/organizations/create');
        await page.getByLabel('Organization Name').fill(name);
        await Promise.all([
            page.waitForResponse(
                (response) =>
                    response.url().includes('/api/v1/organizations') &&
                    response.request().method() === 'POST' &&
                    response.status() === 201
            ),
            page.getByRole('button', { name: 'Create' }).click(),
        ]);
        // The backend switches the current organization to the new one and the
        // frontend reloads into its dashboard.
        await expect(page.getByTestId('dashboard_view')).toBeVisible({ timeout: 10000 });
    }

    test('can create a new organization and switches to it automatically', async ({ page }) => {
        const newOrgName = 'CreateOrg' + Math.floor(Math.random() * 100000);
        await createOrganization(page, newOrgName);

        await expect(page.locator('[data-testid="organization_switcher"]:visible')).toContainText(
            newOrgName
        );
    });

    test('does not create an organization when the name is empty', async ({ page }) => {
        await page.goto(PLAYWRIGHT_BASE_URL + '/organizations/create');

        // The form posts to the API, which rejects the empty name with a 422.
        await Promise.all([
            page.waitForResponse(
                (response) =>
                    response.url().includes('/api/v1/organizations') &&
                    response.request().method() === 'POST' &&
                    response.status() === 422
            ),
            page.getByRole('button', { name: 'Create' }).click(),
        ]);

        // Validation failed, so we stay on the create form and never reach a
        // dashboard. Assert on the form rather than the URL.
        await expect(page.getByText('Organization Details')).toBeVisible();
        await expect(page.getByRole('alert')).toContainText('The name field is required.');
        await expect(page.getByLabel('Organization Name')).toHaveAttribute('aria-invalid', 'true');
        await expect(page.getByTestId('dashboard_view')).toHaveCount(0);
    });

    test('can delete an organization', async ({ page }) => {
        // Create a throwaway organization so the primary one is never deleted.
        const orgName = 'DeleteOrg' + Math.floor(Math.random() * 100000);
        await createOrganization(page, orgName);

        // Open the (now current) throwaway organization's settings.
        await goToOrganizationSettings(page);

        // Open the confirmation modal, then confirm inside the dialog.
        await page.getByRole('button', { name: 'Delete Organization' }).click();
        await page.getByRole('dialog').getByPlaceholder('Password').fill(TEST_USER_PASSWORD);
        await Promise.all([
            page.waitForResponse(
                (response) =>
                    response.url().includes('/api/v1/organizations') &&
                    response.request().method() === 'DELETE' &&
                    response.status() === 204
            ),
            page.getByRole('dialog').getByRole('button', { name: 'Delete Organization' }).click(),
        ]);

        // We are redirected to the dashboard of a different organization.
        await expect(page.getByTestId('dashboard_view')).toBeVisible({ timeout: 10000 });
        await expect(
            page.locator('[data-testid="organization_switcher"]:visible')
        ).not.toContainText(orgName);
    });

    test('delete organization shows an error when the password is wrong', async ({ page }) => {
        const orgName = 'DeleteOrgWrongPassword' + Math.floor(Math.random() * 100000);
        await createOrganization(page, orgName);
        await goToOrganizationSettings(page);

        await page.getByRole('button', { name: 'Delete Organization' }).click();
        const dialog = page.getByRole('dialog');
        await dialog.getByPlaceholder('Password').fill('not-the-real-password');
        await Promise.all([
            page.waitForResponse(
                (response) =>
                    response.url().includes('/api/v1/organizations') &&
                    response.request().method() === 'DELETE' &&
                    response.status() === 422
            ),
            dialog.getByRole('button', { name: 'Delete Organization' }).click(),
        ]);

        await expect(dialog.getByRole('alert')).toBeVisible();
        await expect(dialog).toBeVisible();
    });

    test('can switch the current organization via the organization switcher', async ({ page }) => {
        await page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
        const orgSwitcher = page.locator('[data-testid="organization_switcher"]:visible');
        await expect(orgSwitcher).toBeVisible();
        const previousOrgNameLines = (await orgSwitcher.innerText())
            .split('\n')
            .map((line) => line.trim())
            .filter(Boolean);
        const previousOrgName = previousOrgNameLines[previousOrgNameLines.length - 1];

        // Ensure there are at least two organizations to switch between.
        const orgName = 'SwitchOrg' + Math.floor(Math.random() * 100000);
        await createOrganization(page, orgName);

        await expect(orgSwitcher).toContainText(orgName);

        // Open the switcher and pick a different organization.
        await orgSwitcher.click();
        await expect(page.getByText('Switch Organizations')).toBeVisible();
        const otherOrgButton = page.getByRole('menuitem', { name: previousOrgName });
        await expect(otherOrgButton).toBeVisible();

        await Promise.all([
            page.waitForResponse(
                (response) =>
                    response.url().includes('/users/me/current-organization') &&
                    response.request().method() === 'PUT' &&
                    response.status() === 200
            ),
            otherOrgButton.click(),
        ]);

        await expect(orgSwitcher).not.toContainText(orgName, { timeout: 10000 });
        await expect(orgSwitcher).toContainText(previousOrgName, { timeout: 10000 });
    });
});

// =============================================
// Admin Permission Tests
// =============================================

test.describe('Admin Organization Settings Access', () => {
    test('admin can see and edit organization settings', async ({ ctx, admin }) => {
        await admin.page.goto(PLAYWRIGHT_BASE_URL + '/organizations/' + ctx.orgId);

        // Organization Name section is visible
        await expect(
            admin.page.getByRole('heading', { name: 'Organization Name', level: 3 })
        ).toBeVisible({ timeout: 10000 });

        // Editable settings sections should be visible
        await expect(
            admin.page.getByRole('heading', { name: 'Billable Rate', level: 3 })
        ).toBeVisible();
        await expect(
            admin.page.getByRole('heading', { name: 'Format Settings', level: 3 })
        ).toBeVisible();
        await expect(
            admin.page.getByRole('heading', { name: 'Organization Settings', level: 3 })
        ).toBeVisible();

        // Save buttons should be visible (admin can update)
        await expect(admin.page.getByRole('button', { name: 'Save' }).first()).toBeVisible();

        // The Organization Name input is editable (admin can update)
        await expect(admin.page.getByLabel('Organization Name')).toBeEnabled();

        // Delete organization should NOT be visible (owner only)
        await expect(
            admin.page.getByRole('heading', { name: 'Delete Organization' })
        ).not.toBeVisible();
    });
});

// =============================================
// Employee Permission Tests
// =============================================

test.describe('Employee Organization Settings Restrictions', () => {
    test('employee can see org name but not editable settings', async ({ ctx, employee }) => {
        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/organizations/' + ctx.orgId);

        // Organization Name section is visible (but inputs are disabled)
        await expect(
            employee.page.getByRole('heading', { name: 'Organization Name', level: 3 })
        ).toBeVisible({ timeout: 10000 });

        // The name and currency inputs are rendered but disabled (employee cannot update)
        await expect(employee.page.getByLabel('Organization Name')).toBeDisabled();
        await expect(employee.page.getByLabel('Currency')).toBeDisabled();

        // Editable settings sections should NOT be visible
        await expect(
            employee.page.getByRole('heading', { name: 'Billable Rate', level: 3 })
        ).not.toBeVisible();
        await expect(
            employee.page.getByRole('heading', { name: 'Format Settings', level: 3 })
        ).not.toBeVisible();
        await expect(
            employee.page.getByRole('heading', { name: 'Organization Settings', level: 3 })
        ).not.toBeVisible();

        // Save button should not be visible (employee cannot update)
        await expect(employee.page.getByRole('button', { name: 'Save' })).not.toBeVisible();

        // Delete organization should NOT be visible (owner only)
        await expect(
            employee.page.getByRole('heading', { name: 'Delete Organization' })
        ).not.toBeVisible();
    });
});
