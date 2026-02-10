import { expect, test } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import type { Page } from '@playwright/test';
import path from 'path';

async function goToImportExport(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/import');
}

test('test that import page loads with type dropdown and file upload', async ({ page }) => {
    await goToImportExport(page);
    await expect(page.getByTestId('import_view')).toBeVisible({ timeout: 10000 });

    // Import section
    await expect(page.getByRole('heading', { name: 'Import Data' })).toBeVisible();
    await expect(page.locator('#importType')).toBeVisible();

    // Export section
    await expect(page.getByRole('heading', { name: 'Export Data' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Export Organization Data' })).toBeVisible();
});

test('test that selecting an import type shows instructions', async ({ page }) => {
    await goToImportExport(page);

    // Select a Toggl import type
    await page.getByLabel('Import Type').selectOption({ index: 1 });

    // Instructions should appear
    await expect(page.getByText('Instructions:')).toBeVisible();
});

test('test that importing without selecting type shows error', async ({ page }) => {
    await goToImportExport(page);

    // Click Import Data without selecting a type
    await page.getByRole('button', { name: 'Import Data' }).click();

    // Should show an error notification
    await expect(page.getByText('Please select the import type')).toBeVisible();
});

test('test that importing without selecting file shows error', async ({ page }) => {
    await goToImportExport(page);

    // Select an import type first
    await page.getByLabel('Import Type').selectOption({ index: 1 });

    // Click Import Data without selecting a file
    await page.getByRole('button', { name: 'Import Data' }).click();

    // Should show an error notification
    await expect(
        page.getByText('Please select the CSV or ZIP file that you want to import')
    ).toBeVisible();
});

test('test that export button triggers export and shows success modal', async ({ page }) => {
    await goToImportExport(page);
    await expect(page.getByRole('button', { name: 'Export Organization Data' })).toBeVisible();

    // Override window.open to prevent the page from navigating away to the
    // download URL (the app uses window.open(url, '_self') which would navigate
    // away before we can verify the success modal)
    await page.evaluate(() => {
        window.open = () => null;
    });

    // Click Export Organization Data and wait for the API response
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/export') &&
                response.request().method() === 'POST' &&
                response.status() === 200,
            { timeout: 60000 }
        ),
        page.getByRole('button', { name: 'Export Organization Data' }).click(),
    ]);

    // Success modal should appear after export completes
    await expect(page.getByText('The export was successful!')).toBeVisible();
});

test('test that import type dropdown has multiple options', async ({ page }) => {
    await goToImportExport(page);

    // The dropdown should load with options from the API
    await page.waitForResponse(
        (response) =>
            response.url().includes('/importers') &&
            response.request().method() === 'GET' &&
            response.status() === 200
    );

    // Verify the select has options besides the default placeholder
    const options = page.getByLabel('Import Type').locator('option');
    const count = await options.count();
    // Should have at least the placeholder + some import types
    expect(count).toBeGreaterThan(1);
});

test('test that importing a generic time entries CSV works', async ({ page }) => {
    await goToImportExport(page);
    await expect(page.getByTestId('import_view')).toBeVisible({ timeout: 10000 });

    // Select "Generic Time Entries" import type
    await page.getByLabel('Import Type').selectOption({ label: 'Generic Time Entries' });
    await expect(page.getByText('Instructions:')).toBeVisible();

    // Upload the test CSV file
    const csvPath = path.resolve('resources/testfiles/generic_time_entries_import_test_1.csv');
    await page.locator('#file-upload').setInputFiles(csvPath);

    // Click Import and wait for the API response
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/import') &&
                response.request().method() === 'POST' &&
                response.status() === 200,
            { timeout: 30000 }
        ),
        page.getByRole('button', { name: 'Import Data' }).click(),
    ]);

    // Verify success modal with import results
    await expect(page.getByRole('heading', { name: 'Import Result' })).toBeVisible();
    await expect(page.getByText('The import was successful!')).toBeVisible();

    // The CSV has 2 time entries, 1 client, 2 projects, 1 task
    await expect(page.getByText('Time entries created:').locator('..')).toContainText('2');
    await expect(page.getByText('Projects created:').locator('..')).toContainText('2');
    await expect(page.getByText('Clients created:').locator('..')).toContainText('1');
    await expect(page.getByText('Tasks created:').locator('..')).toContainText('1');
});

// =============================================
// Employee Permission Tests
// =============================================

test.describe('Employee Import Restrictions', () => {
    test('employee does not see Import / Export link in the sidebar', async ({ employee }) => {
        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
        await expect(employee.page.getByTestId('dashboard_view')).toBeVisible({
            timeout: 10000,
        });

        // The Import / Export link should NOT be visible in the sidebar for employees
        await expect(
            employee.page.getByRole('link', { name: 'Import / Export' })
        ).not.toBeVisible();
    });
});
