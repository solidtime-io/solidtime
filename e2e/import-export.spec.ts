import { expect, test } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import type { Page } from '@playwright/test';
import fs from 'fs';
import path from 'path';

async function goToImportExport(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/import');
    await expect(page.getByTestId('import_view')).toBeVisible({ timeout: 10000 });
}

/**
 * Uploads the file in the current wizard step and waits until the import was accepted.
 * The import is processed in the background, the wizard polls it until it is finished.
 */
async function importFileInCurrentStep(page: Page, testFile: string) {
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/import') &&
                response.request().method() === 'POST' &&
                response.status() === 202,
            { timeout: 30000 }
        ),
        page
            .getByTestId('import_file_input')
            .setInputFiles(path.resolve('resources/testfiles/' + testFile)),
    ]);
}

test('test that import page shows the import sources and the export', async ({ page }) => {
    await goToImportExport(page);

    // Import section
    await expect(page.getByRole('heading', { name: 'Import Data' })).toBeVisible();
    for (const source of ['toggl', 'clockify', 'harvest', 'solidtime', 'generic']) {
        await expect(page.getByTestId(`import_source_${source}`)).toBeVisible();
    }

    // Export section
    await expect(page.getByRole('heading', { name: 'Export Data' })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Export Organization Data' })).toBeVisible();
});

test('test that every step of the import wizard can be skipped and gone back to', async ({
    page,
}) => {
    await goToImportExport(page);
    const wizard = page.getByTestId('import_wizard');
    const back = page.getByTestId('import_wizard_back');
    const next = page.getByTestId('import_wizard_next');

    // Preparation
    await page.getByTestId('import_source_toggl').click();
    await expect(wizard).toContainText('Before you export from');
    await expect(wizard).toContainText('Sign in to Toggl as an Admin of the workspace');
    await expect(next).toHaveText('Continue');

    // Step 1: workspace data
    await next.click();
    await expect(wizard).toContainText('Step 2 of 3');
    await expect(wizard).toContainText('Export the workspace data');
    await expect(wizard).toContainText('Choose the ZIP file');
    await expect(next).toHaveText('Skip');

    // Skipping a required step without an import asks first, "Go back" stays on the step
    const skipConfirm = page.getByTestId('import_wizard_skip_confirm');
    await next.click();
    await expect(page.getByText('Skip without importing?')).toBeVisible();
    // Earlier steps have to be imported first, a later import does not update existing data
    await expect(page.getByText('Import it before the next steps')).toBeVisible();
    await page.getByRole('button', { name: 'Go back' }).click();
    await expect(page.getByText('Skip without importing?')).toBeHidden();
    await expect(wizard).toContainText('Step 2 of 3');

    // Step 2: time entries
    await next.click();
    await skipConfirm.click();
    await expect(wizard).toContainText('Step 3 of 3');
    await expect(wizard).toContainText('Export the time entries');
    await expect(wizard).toContainText('Choose the CSV file');
    await expect(next).toHaveText('Skip and finish');

    // Summary: nothing was imported, the last step can be imported later
    await next.click();
    await expect(page.getByText('You can come back to it later.')).toBeVisible();
    await skipConfirm.click();
    await expect(wizard).toContainText('Summary');
    await expect(wizard.getByText('Not imported.')).toHaveCount(2);
    await expect(next).toHaveText('Import from another tool');

    // "Go to step" jumps back to a skipped step
    await wizard.getByRole('button', { name: 'Go to step' }).first().click();
    await expect(wizard).toContainText('Step 2 of 3');

    // Back goes through all screens back to the source picker
    await next.click();
    await skipConfirm.click();
    await expect(wizard).toContainText('Step 3 of 3');
    await back.click();
    await expect(wizard).toContainText('Step 2 of 3');
    await back.click();
    await expect(wizard).toContainText('Before you export from');
    await back.click();
    await expect(page.getByTestId('import_source_toggl')).toBeVisible();
    await expect(back).not.toBeVisible();

    // Change source switches to another tool
    await page.getByTestId('import_source_harvest').click();
    await next.click();
    await expect(wizard).toContainText('Step 2 of 4');
    await expect(wizard).toContainText('Export the clients');
    await wizard.getByRole('button', { name: 'Change source' }).click();
    await page.getByTestId('import_source_clockify').click();
    await expect(wizard).toContainText('Clockify');
    await expect(wizard).toContainText('Before you export from');
});

test('test that importing a generic time entries CSV works through the wizard', async ({
    page,
}) => {
    // The imports are processed in the background and polled
    test.slow();
    await goToImportExport(page);
    const wizard = page.getByTestId('import_wizard');
    const next = page.getByTestId('import_wizard_next');

    await page.getByTestId('import_source_generic').click();
    await next.click();
    // Skip the projects
    await expect(wizard).toContainText('Export the projects');
    await next.click();
    await page.getByTestId('import_wizard_skip_confirm').click();
    await expect(wizard).toContainText('Export the time entries');

    await importFileInCurrentStep(page, 'generic_time_entries_import_test_1.csv');

    // The CSV has 2 time entries, 2 projects, 1 client and 1 task
    const stepImports = page.getByTestId('import_step_imports');
    await expect(stepImports).toContainText(
        'Created 2 time entries, 2 projects, 1 client, 1 task',
        { timeout: 30000 }
    );
    await expect(next).toHaveText('Finish');

    // The import is listed in the summary and, once the source is closed, in the recent imports
    await next.click();
    await expect(wizard).toContainText('Summary');
    await expect(wizard).toContainText('Created 2 time entries, 2 projects, 1 client, 1 task');
    await expect(page.getByTestId('recent_imports')).toBeHidden();
    await wizard.getByRole('button', { name: 'Change source' }).click();
    await expect(page.getByTestId('recent_imports')).toContainText('Generic Time Entries');
    await expect(page.getByTestId('recent_imports')).toContainText('Completed');

    // The import created the projects, they are available in the app right away
    await page.goto(PLAYWRIGHT_BASE_URL + '/projects');
    await expect(page.getByTestId('projects_view')).toBeVisible({ timeout: 10000 });
    await expect(page.getByTestId('project_table')).toContainText('Project without Client');
});

test('test that a failed import is shown in the step and the step can be retried', async ({
    page,
}) => {
    // The imports are processed in the background and polled
    test.slow();
    await goToImportExport(page);
    const next = page.getByTestId('import_wizard_next');

    await page.getByTestId('import_source_generic').click();
    await next.click();
    await next.click();
    await page.getByTestId('import_wizard_skip_confirm').click();

    // A Clockify export is not a valid generic CSV
    await importFileInCurrentStep(page, 'clockify_projects_import_test_1.csv');
    await expect(page.getByTestId('import_step_imports')).toContainText(
        'Failed, nothing was imported.',
        { timeout: 30000 }
    );

    // The step is not done, it can be skipped or another file can be imported
    await expect(next).toHaveText('Skip and finish');
    await expect(page.getByTestId('import_file_input')).toBeEnabled();
    await importFileInCurrentStep(page, 'generic_time_entries_import_test_1.csv');
    await expect(page.getByTestId('import_step_imports')).toContainText('Created 2 time entries', {
        timeout: 30000,
    });
    await expect(next).toHaveText('Finish');
});

test('test that the wizard continues with the next step of a source after reloading', async ({
    page,
}) => {
    // The imports are processed in the background and polled
    test.slow();
    await goToImportExport(page);
    const wizard = page.getByTestId('import_wizard');
    const next = page.getByTestId('import_wizard_next');

    await page.getByTestId('import_source_clockify').click();
    await next.click();
    await expect(wizard).toContainText('Export the projects');
    await importFileInCurrentStep(page, 'clockify_projects_import_test_1.csv');
    await expect(page.getByTestId('import_step_imports')).toContainText('Created', {
        timeout: 30000,
    });
    await expect(next).toHaveText('Next');

    // After a reload the source picker suggests the next step of Clockify
    await goToImportExport(page);
    const suggestion = page.getByTestId('import_continue_suggestion');
    await expect(suggestion).toContainText('Continue with Clockify');
    await expect(suggestion).toContainText('Time entries');
    await suggestion.click();
    await expect(wizard).toContainText('Step 3 of 3');
    await expect(wizard).toContainText('Export the time entries');

    // Opening the source again also skips the imported step
    await wizard.getByRole('button', { name: 'Change source' }).click();
    await page.getByTestId('import_source_clockify').click();
    await expect(wizard).toContainText('Step 3 of 3');
});

test('test that a running import opens its step and does not block the navigation', async ({
    page,
}) => {
    // Imports finish within seconds in the tests, so the running import is simulated
    const runningImport = {
        id: '9d6b4cf1-6c11-4b6d-9f58-3e8e4f4b2a10',
        type: 'clockify_time_entries',
        source: 'web',
        status: 'running',
        report: null,
        error_message: null,
        user_id: null,
        started_at: new Date().toISOString(),
        finished_at: null,
        created_at: new Date().toISOString(),
    };
    await page.route(/\/api\/v1\/organizations\/[^/]+\/imports(\?.*)?$/, async (route) => {
        const response = await route.fetch();
        const json = await response.json();
        json.data = [runningImport, ...json.data];
        await route.fulfill({ response, json });
    });
    await page.route(`**/imports/${runningImport.id}`, (route) =>
        route.fulfill({ json: { data: runningImport } })
    );

    await goToImportExport(page);
    const wizard = page.getByTestId('import_wizard');

    // The step of the running import is opened, no other file can be added until it has finished
    await expect(wizard).toContainText('Clockify');
    await expect(wizard).toContainText('Step 3 of 3');
    await expect(page.getByTestId('import_step_imports')).toContainText('Importing…');
    await expect(page.getByTestId('import_file_input')).toBeDisabled();
    await expect(wizard).toContainText('Another import is running');

    // Navigation still works, the running import is shown in a banner on the other screens
    await page.getByTestId('import_wizard_back').click();
    await expect(wizard).toContainText('Step 2 of 3');
    await expect(page.getByTestId('import_in_progress')).toBeVisible();
    // Skipping the projects asks first and names what would be missing
    await page.getByTestId('import_wizard_next').click();
    await expect(
        page.getByText('Billable rates, billable status and estimates of the projects')
    ).toBeVisible();
    await page.getByTestId('import_wizard_skip_confirm').click();
    await expect(wizard).toContainText('Step 3 of 3');
    await page.getByTestId('import_wizard_next').click();
    await expect(wizard).toContainText('Summary');

    await wizard.getByRole('button', { name: 'Change source' }).click();
    await page.getByTestId('import_source_toggl').click();
    await expect(page.getByTestId('import_in_progress')).toBeVisible();
    await page.getByTestId('import_in_progress').getByRole('button', { name: 'Show' }).click();
    await expect(wizard).toContainText('Clockify');
    await expect(wizard).toContainText('Step 3 of 3');
    await expect(page.getByTestId('recent_imports')).toBeHidden();
    await wizard.getByRole('button', { name: 'Change source' }).click();
    await expect(page.getByTestId('recent_imports')).toContainText('In progress');
});

test('test that a step with one file per year takes several files', async ({ page }) => {
    // The imports are processed in the background and polled
    test.slow();
    await goToImportExport(page);
    const wizard = page.getByTestId('import_wizard');
    const next = page.getByTestId('import_wizard_next');
    const stepImports = page.getByTestId('import_step_imports');

    await page.getByTestId('import_source_toggl').click();
    await next.click();
    await next.click();
    await page.getByTestId('import_wizard_skip_confirm').click();
    await expect(wizard).toContainText('Export the time entries');

    await importFileInCurrentStep(page, 'toggl_time_entries_import_test_1.csv');
    await expect(stepImports).toContainText('Created', { timeout: 30000 });
    // The step asks for the next year's file instead of offering to import the same file again
    await expect(wizard).toContainText('Add the next file');
    await expect(next).toHaveText('Finish');

    await importFileInCurrentStep(page, 'toggl_time_entries_import_test_2.csv');
    await expect(stepImports.getByText(/^Created/)).toHaveCount(2, { timeout: 30000 });

    // Both files are listed in the summary
    await next.click();
    await expect(wizard).toContainText('Summary');
    await expect(wizard.getByText(/^Created/)).toHaveCount(2);
});

test('test that every Harvest step can be imported and the source reopens at the summary', async ({
    page,
}) => {
    // The imports are processed in the background and polled
    test.slow();
    await goToImportExport(page);
    const wizard = page.getByTestId('import_wizard');
    const next = page.getByTestId('import_wizard_next');
    const stepImports = page.getByTestId('import_step_imports');

    await page.getByTestId('import_source_harvest').click();
    await expect(wizard).toContainText('Sign in to Harvest as an Administrator');
    await next.click();

    await expect(wizard).toContainText('Export the clients');
    await importFileInCurrentStep(page, 'harvest_clients_import_test_1.csv');
    await expect(stepImports).toContainText('Created', { timeout: 30000 });
    await expect(next).toHaveText('Next');
    await next.click();

    await expect(wizard).toContainText('Export the projects');
    await importFileInCurrentStep(page, 'harvest_projects_import_test_1.csv');
    await expect(stepImports).toContainText('Created', { timeout: 30000 });
    await expect(next).toHaveText('Next');
    await next.click();

    await expect(wizard).toContainText('Export the time entries');
    await importFileInCurrentStep(page, 'harvest_time_entries_import_test_1.csv');
    await expect(stepImports).toContainText('Created', { timeout: 30000 });
    await expect(next).toHaveText('Finish');
    await next.click();

    await expect(wizard).toContainText('Summary');
    await expect(wizard).toContainText('Import from Harvest done');
    await expect(wizard.getByText('Not imported.')).toHaveCount(0);

    // A source with every step imported opens at the summary
    await wizard.getByRole('button', { name: 'Change source' }).click();
    await page.getByTestId('import_source_harvest').click();
    await expect(wizard).toContainText('Import from Harvest done');
});

test('test that a file can be dropped on the upload area', async ({ page }) => {
    // The imports are processed in the background and polled
    test.slow();
    await goToImportExport(page);
    const wizard = page.getByTestId('import_wizard');

    await page.getByTestId('import_source_generic').click();
    await page.getByTestId('import_wizard_next').click();
    await expect(wizard).toContainText('Export the projects');

    const fileName = 'generic_projects_import_test_1.csv';
    const content = fs.readFileSync(path.resolve('resources/testfiles/' + fileName), 'utf8');
    const dataTransfer = await page.evaluateHandle(
        ({ content, fileName }) => {
            const transfer = new DataTransfer();
            transfer.items.add(new File([content], fileName, { type: 'text/csv' }));
            return transfer;
        },
        { content, fileName }
    );
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/import') &&
                response.request().method() === 'POST' &&
                response.status() === 202,
            { timeout: 30000 }
        ),
        page
            .locator('label', { has: page.getByTestId('import_file_input') })
            .dispatchEvent('drop', { dataTransfer }),
    ]);
    await expect(page.getByTestId('import_step_imports')).toContainText(
        'Created 3 projects, 2 clients',
        {
            timeout: 30000,
        }
    );
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
                response.status() === 202,
            { timeout: 60000 }
        ),
        page.getByRole('button', { name: 'Export Organization Data' }).click(),
    ]);

    // Success modal should appear after export completes
    await expect(page.getByText('The export was successful!')).toBeVisible();
    await page.getByRole('button', { name: 'Close' }).click();

    // The export is listed and can be downloaded again
    await expect(page.getByText('Recent exports')).toBeVisible();
    await expect(page.getByRole('button', { name: 'Download' }).first()).toBeVisible();
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
