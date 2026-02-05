import { expect } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import {
    goToReporting,
    goToReportingShared,
    createProject,
    createClient,
    createProjectWithClient,
    createTask,
    createTimeEntryWithProject,
    createTimeEntryWithProjectAndTask,
    createTimeEntryWithTag,
    createBareTimeEntry,
    waitForReportingUpdate,
    saveAsSharedReport,
} from './utils/reporting';

// Each test registers a new user and creates test data, which needs more time
test.describe.configure({ timeout: 60000 });

// Date picker button name patterns for different date formats
const DATE_PICKER_BUTTON_PATTERN =
    /^Pick a date$|^\d{4}-\d{2}-\d{2}$|^\d{2}\/\d{2}\/\d{4}$|^\d{2}\.\d{2}\.\d{4}$/;

// ──────────────────────────────────────────────────
// Shared Report Lifecycle Tests
// ──────────────────────────────────────────────────

test('test that saving a report creates a shared report and its shareable link shows correct data', async ({
    page,
}) => {
    const projectName = 'SharedProject ' + Math.floor(Math.random() * 10000);
    const reportName = 'SharedReport ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createTimeEntryWithProject(page, projectName, '1h');

    await goToReporting(page);
    await expect(page.getByTestId('reporting_view').getByText(projectName)).toBeVisible();

    const { shareableLink } = await saveAsSharedReport(page, reportName);

    // Verify report appears on shared tab
    await goToReportingShared(page);
    await expect(page.getByTestId('report_table')).toBeVisible();
    await expect(page.getByText(reportName)).toBeVisible();
    await expect(page.getByText('Public', { exact: true })).toBeVisible();
    await expect(page.getByRole('button', { name: 'Copy URL' })).toBeVisible();

    // Navigate to shareable link and verify report data
    await page.goto(shareableLink);
    await expect(page.getByText('Reporting')).toBeVisible();
    await expect(page.getByText(projectName)).toBeVisible();
    await expect(page.getByText('Total')).toBeVisible();
});

test('test that shared report with invalid secret shows no data', async ({ page }) => {
    await page.goto(PLAYWRIGHT_BASE_URL + '/shared-report#invalid-secret-value');
    await expect(page.getByText('No time entries found').first()).toBeVisible();
});

test('test that a shared report can be edited to toggle public/private and then deleted', async ({
    page,
}) => {
    const projectName = 'EditDelProject ' + Math.floor(Math.random() * 10000);
    const reportName = 'EditDelReport ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createTimeEntryWithProject(page, projectName, '1h');

    await goToReporting(page);
    await expect(page.getByTestId('reporting_view').getByText(projectName)).toBeVisible();

    await saveAsSharedReport(page, reportName);

    await goToReportingShared(page);
    await expect(page.getByText(reportName)).toBeVisible();
    await expect(page.getByText('Public', { exact: true })).toBeVisible();

    // Click more options and edit
    await page
        .getByRole('button', { name: new RegExp('Actions for Project ' + reportName) })
        .click();
    await page.getByRole('menuitem', { name: /^Edit Report/ }).click();

    // Uncheck public and save
    await page.getByLabel('Public').click();
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/reports/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page.getByRole('button', { name: 'Update Report' }).click(),
    ]);

    // Verify status changed to private
    await expect(page.getByText('Private')).toBeVisible();
    await expect(page.getByText('--')).toBeVisible();

    // Delete the report
    await page
        .getByRole('button', { name: new RegExp('Actions for Project ' + reportName) })
        .click();
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/reports/') &&
                response.request().method() === 'DELETE' &&
                response.status() === 204
        ),
        page.getByRole('menuitem', { name: /^Delete Report/ }).click(),
    ]);

    await expect(page.getByText('No shared reports found')).toBeVisible();
});

// ──────────────────────────────────────────────────
// Shared Report Filter Tests
// ──────────────────────────────────────────────────

test('test that shared report respects project filter', async ({ page }) => {
    const projectA = 'FilterProjA ' + Math.floor(Math.random() * 10000);
    const projectB = 'FilterProjB ' + Math.floor(Math.random() * 10000);
    const reportName = 'FilterProjReport ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectA);
    await createProject(page, projectB);
    await createTimeEntryWithProject(page, projectA, '1h');
    await createTimeEntryWithProject(page, projectB, '2h');

    await goToReporting(page);
    await expect(page.getByTestId('reporting_view').getByText(projectA)).toBeVisible();

    // Filter by project A
    await page.getByRole('button', { name: 'Projects' }).first().click();
    await page.getByRole('option').filter({ hasText: projectA }).click();
    await page.keyboard.press('Escape');
    await waitForReportingUpdate(page);

    const { shareableLink } = await saveAsSharedReport(page, reportName);

    // View the shared report
    await page.goto(shareableLink);
    await expect(page.getByText('Reporting')).toBeVisible();
    await expect(page.getByText(projectA)).toBeVisible();
    await expect(page.getByText(projectB)).not.toBeVisible();
});

test('test that shared report with No Project filter shows entries without a project', async ({
    page,
}) => {
    const projectName = 'NoProjFilter ' + Math.floor(Math.random() * 10000);
    const reportName = 'NoProjReport ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createTimeEntryWithProject(page, projectName, '1h');
    await createBareTimeEntry(page, 'Bare entry no project', '2h');

    await goToReporting(page);
    await expect(page.getByTestId('reporting_view').getByText(projectName)).toBeVisible();

    // Filter by "No Project"
    await page.getByRole('button', { name: 'Projects' }).first().click();
    await page.getByRole('option').filter({ hasText: 'No Project' }).click();
    await page.keyboard.press('Escape');
    await waitForReportingUpdate(page);

    const { shareableLink } = await saveAsSharedReport(page, reportName);

    // View the shared report
    await page.goto(shareableLink);
    await expect(page.getByText('Reporting')).toBeVisible();
    // The "No Project" group should show, but the project name should not appear as a group
    await expect(page.getByText('Total')).toBeVisible();
    await expect(page.getByText(projectName)).not.toBeVisible();
});

test('test that shared report with No Task filter shows entries without a task', async ({
    page,
}) => {
    const projectName = 'NoTaskProj ' + Math.floor(Math.random() * 10000);
    const taskName = 'NoTaskFilter ' + Math.floor(Math.random() * 10000);
    const reportName = 'NoTaskReport ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createTask(page, projectName, taskName);
    await createTimeEntryWithProjectAndTask(page, projectName, taskName, '1h');
    await createTimeEntryWithProject(page, projectName, '2h');

    await goToReporting(page);
    await expect(page.getByTestId('reporting_view').getByText(projectName)).toBeVisible();

    // Filter by "No Task"
    await page.getByRole('button', { name: 'Tasks' }).first().click();
    await page.getByRole('option').filter({ hasText: 'No Task' }).click();
    await page.keyboard.press('Escape');
    await waitForReportingUpdate(page);

    const { shareableLink } = await saveAsSharedReport(page, reportName);

    // View the shared report
    await page.goto(shareableLink);
    await expect(page.getByText('Reporting')).toBeVisible();
    await expect(page.getByText('Total')).toBeVisible();
});

// ──────────────────────────────────────────────────
// Report Date Picker Tests
// ──────────────────────────────────────────────────

test('test that creating a report with an expiration date works', async ({ page }) => {
    const projectName = 'DatePickerProj ' + Math.floor(Math.random() * 10000);
    const reportName = 'DatePickerReport ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createTimeEntryWithProject(page, projectName, '1h');

    await goToReporting(page);
    await expect(page.getByTestId('reporting_view').getByText(projectName)).toBeVisible();

    // Open the save report modal
    await page.getByRole('button', { name: 'Save Report' }).click();
    await page.getByLabel('Name').fill(reportName);

    // The "Public" checkbox should be checked by default, showing the date picker
    const datePicker = page
        .getByRole('dialog')
        .getByRole('button', { name: DATE_PICKER_BUTTON_PATTERN });
    await expect(datePicker).toBeVisible();
    await datePicker.click();

    // Select a date in the next month
    const calendarGrid = page.getByRole('grid');
    await expect(calendarGrid).toBeVisible({ timeout: 5000 });
    await page.getByRole('button', { name: /Next/i }).click();
    await page.getByRole('gridcell').filter({ hasText: /^15$/ }).first().click();

    // Wait for the calendar to close
    await expect(calendarGrid).not.toBeVisible();

    // Create the report and verify it includes the public_until date
    const [response] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/reports') &&
                response.request().method() === 'POST' &&
                response.status() === 201
        ),
        page.getByRole('dialog').getByRole('button', { name: 'Create Report' }).click(),
    ]);
    const responseBody = await response.json();
    expect(responseBody.data.public_until).toBeTruthy();
});

test('test that editing a report to make it public with expiration date works', async ({
    page,
}) => {
    const projectName = 'EditDateProj ' + Math.floor(Math.random() * 10000);
    const reportName = 'EditDateReport ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createTimeEntryWithProject(page, projectName, '1h');

    await goToReporting(page);
    await expect(page.getByTestId('reporting_view').getByText(projectName)).toBeVisible();

    // Open the save report modal and create a private report
    await page.getByRole('button', { name: 'Save Report' }).click();
    await page.getByLabel('Name').fill(reportName);

    // Uncheck "Public" to create a private report
    await page.getByLabel('Public').click();

    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/reports') &&
                response.request().method() === 'POST' &&
                response.status() === 201
        ),
        page.getByRole('dialog').getByRole('button', { name: 'Create Report' }).click(),
    ]);

    // Go to shared reports and edit
    await goToReportingShared(page);
    await expect(page.getByText(reportName)).toBeVisible();
    await expect(page.getByText('Private')).toBeVisible();

    // Click more options and edit
    await page
        .getByRole('button', { name: new RegExp('Actions for Project ' + reportName) })
        .click();
    await page.getByRole('menuitem', { name: /^Edit Report/ }).click();

    // Check "Public" to make it public - this should show the date picker
    await page.getByLabel('Public').click();

    // The date picker should now be visible
    const datePicker = page
        .getByRole('dialog')
        .getByRole('button', { name: DATE_PICKER_BUTTON_PATTERN });
    await expect(datePicker).toBeVisible();
    await datePicker.click();

    // Select a date in the next month
    const calendarGrid = page.getByRole('grid');
    await expect(calendarGrid).toBeVisible({ timeout: 5000 });
    await page.getByRole('button', { name: /Next/i }).click();
    await page.getByRole('gridcell').filter({ hasText: /^20$/ }).first().click();

    // Wait for the calendar to close
    await expect(calendarGrid).not.toBeVisible();

    // Update the report and verify it includes the public_until date
    const [response] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/reports/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page.getByRole('button', { name: 'Update Report' }).click(),
    ]);
    const responseBody = await response.json();
    expect(responseBody.data.public_until).toBeTruthy();
    expect(responseBody.data.is_public).toBe(true);
});

test('test that shared report with No Client filter shows entries without a client', async ({
    page,
}) => {
    const clientName = 'NoClientCli ' + Math.floor(Math.random() * 10000);
    const projectName = 'NoClientProj ' + Math.floor(Math.random() * 10000);
    const reportName = 'NoClientReport ' + Math.floor(Math.random() * 10000);

    await createClient(page, clientName);
    await createProjectWithClient(page, projectName, clientName);
    await createTimeEntryWithProject(page, projectName, '1h');
    await createBareTimeEntry(page, 'Entry without client', '2h');

    await goToReporting(page);
    await expect(page.getByTestId('reporting_view').getByText(projectName)).toBeVisible();

    // Filter by "No Client"
    await page.getByRole('button', { name: 'Clients' }).first().click();
    await page.getByRole('option').filter({ hasText: 'No Client' }).click();
    await page.keyboard.press('Escape');
    await waitForReportingUpdate(page);

    const { shareableLink } = await saveAsSharedReport(page, reportName);

    // View the shared report
    await page.goto(shareableLink);
    await expect(page.getByText('Reporting')).toBeVisible();
    await expect(page.getByText('Total')).toBeVisible();
    await expect(page.getByText(projectName)).not.toBeVisible();
});

test('test that shared report with No Tag filter shows entries without tags', async ({ page }) => {
    const tagName = 'NoTagFilter ' + Math.floor(Math.random() * 10000);
    const reportName = 'NoTagReport ' + Math.floor(Math.random() * 10000);

    await createTimeEntryWithTag(page, tagName, '1h');
    await createBareTimeEntry(page, 'Entry without tags', '2h');

    await goToReporting(page);
    await expect(page.getByText('Total')).toBeVisible();

    // Filter by "No Tag"
    await page.getByRole('button', { name: 'Tags' }).first().click();
    await page.getByRole('option').filter({ hasText: 'No Tag' }).click();
    await page.keyboard.press('Escape');
    await waitForReportingUpdate(page);

    const { shareableLink } = await saveAsSharedReport(page, reportName);

    // View the shared report
    await page.goto(shareableLink);
    await expect(page.getByText('Reporting')).toBeVisible();
    await expect(page.getByText('Total')).toBeVisible();
});
