import { expect } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import {
    goToReporting,
    createProject,
    createClient,
    createProjectWithClient,
    createTask,
    createTimeEntryWithProject,
    createTimeEntryWithProjectAndTask,
    createTimeEntryWithTag,
    createTimeEntryWithBillableStatus,
    waitForReportingUpdate,
} from './utils/reporting';

// Each test registers a new user and creates test data, which needs more time
test.describe.configure({ timeout: 60000 });

// ──────────────────────────────────────────────────
// No-op Dropdown Close Tests
// ──────────────────────────────────────────────────

test('test that opening and closing a filter dropdown without changes does not trigger an API request', async ({
    page,
}) => {
    const projectName = 'NoOpDropdown ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createTimeEntryWithProject(page, projectName, '1h');

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Wait for initial reporting data to fully load and all network activity to settle
    await expect(page.getByTestId('reporting_view').getByText(projectName)).toBeVisible();
    await page.waitForLoadState('networkidle');

    // Set up a request counter for aggregate API calls
    let aggregateRequestCount = 0;
    page.on('response', (response) => {
        if (
            response.url().includes('/time-entries/aggregate') &&
            !response.url().includes('/export') &&
            response.status() === 200
        ) {
            aggregateRequestCount++;
        }
    });

    // Open project dropdown, change nothing, close it
    await page.getByRole('button', { name: 'Projects' }).first().click();
    await expect(page.getByPlaceholder('Search for a Project...')).toBeVisible();
    await page.keyboard.press('Escape');

    // Open member dropdown, change nothing, close it
    await page.getByRole('button', { name: 'Members' }).first().click();
    await expect(page.getByPlaceholder('Search for a Member...')).toBeVisible();
    await page.keyboard.press('Escape');

    // Open client dropdown, change nothing, close it
    await page.getByRole('button', { name: 'Clients' }).first().click();
    await expect(page.getByPlaceholder('Search for a Client...')).toBeVisible();
    await page.keyboard.press('Escape');

    // Wait for all network activity to settle before asserting no requests were made
    await page.waitForLoadState('networkidle');

    // No aggregate API requests should have been made
    expect(aggregateRequestCount).toBe(0);

    // Verify the report data is still intact (no flash/reload)
    await expect(page.getByTestId('reporting_view').getByText(projectName)).toBeVisible();
});

// ──────────────────────────────────────────────────
// Project Multiselect Dropdown Tests
// ──────────────────────────────────────────────────

test('test that project multiselect dropdown shows projects and filters reporting', async ({
    page,
}) => {
    const project1 = 'ProjFilter1 ' + Math.floor(Math.random() * 10000);
    const project2 = 'ProjFilter2 ' + Math.floor(Math.random() * 10000);

    await createProject(page, project1);
    await createProject(page, project2);
    await createTimeEntryWithProject(page, project1, '1h');
    await createTimeEntryWithProject(page, project2, '2h');

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Open project multiselect dropdown
    await page.getByRole('button', { name: 'Projects' }).first().click();

    // Verify both projects appear as options
    await expect(page.getByRole('option').filter({ hasText: project1 })).toBeVisible();
    await expect(page.getByRole('option').filter({ hasText: project2 })).toBeVisible();

    // Select project1
    await page.getByRole('option').filter({ hasText: project1 }).click();

    // Close dropdown and wait for report update
    await Promise.all([page.keyboard.press('Escape'), waitForReportingUpdate(page)]);

    // Verify filter badge shows count of 1
    await expect(
        page.getByRole('button', { name: 'Projects' }).first().getByText('1')
    ).toBeVisible();

    // Verify only project1 data is shown
    await expect(page.getByTestId('reporting_view').getByText(project1)).toBeVisible();
    await expect(page.getByTestId('reporting_view').getByText(project2)).not.toBeVisible();
});

test('test that project multiselect search filters the option list', async ({ page }) => {
    const project1 = 'SearchableAlpha ' + Math.floor(Math.random() * 10000);
    const project2 = 'SearchableBeta ' + Math.floor(Math.random() * 10000);

    await createProject(page, project1);
    await createProject(page, project2);
    await createTimeEntryWithProject(page, project1, '1h');

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Open project multiselect dropdown
    await page.getByRole('button', { name: 'Projects' }).first().click();

    // Type in search
    await page.getByPlaceholder('Search for a Project...').fill('Alpha');

    // Verify only matching project is visible
    await expect(page.getByRole('option').filter({ hasText: project1 })).toBeVisible();
    await expect(page.getByRole('option').filter({ hasText: project2 })).not.toBeVisible();

    await page.keyboard.press('Escape');
});

test('test that selecting multiple projects shows correct badge count', async ({ page }) => {
    const project1 = 'MultiProj1 ' + Math.floor(Math.random() * 10000);
    const project2 = 'MultiProj2 ' + Math.floor(Math.random() * 10000);

    await createProject(page, project1);
    await createProject(page, project2);
    await createTimeEntryWithProject(page, project1, '1h');
    await createTimeEntryWithProject(page, project2, '2h');

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Open project dropdown and select both
    await page.getByRole('button', { name: 'Projects' }).first().click();
    await page.getByRole('option').filter({ hasText: project1 }).click();
    await page.getByRole('option').filter({ hasText: project2 }).click();

    await Promise.all([page.keyboard.press('Escape'), waitForReportingUpdate(page)]);

    // Verify filter badge shows count of 2
    await expect(
        page.getByRole('button', { name: 'Projects' }).first().getByText('2')
    ).toBeVisible();

    // Verify both projects are shown in the report
    await expect(page.getByTestId('reporting_view').getByText(project1)).toBeVisible();
    await expect(page.getByTestId('reporting_view').getByText(project2)).toBeVisible();
});

test('test that deselecting a project removes the filter', async ({ page }) => {
    const project1 = 'DeselectProj ' + Math.floor(Math.random() * 10000);

    await createProject(page, project1);
    await createTimeEntryWithProject(page, project1, '1h');

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Select project
    await page.getByRole('button', { name: 'Projects' }).first().click();
    await page.getByRole('option').filter({ hasText: project1 }).click();
    await Promise.all([page.keyboard.press('Escape'), waitForReportingUpdate(page)]);

    // Verify badge count is 1
    await expect(
        page.getByRole('button', { name: 'Projects' }).first().getByText('1')
    ).toBeVisible();

    // Deselect project
    await page.getByRole('button', { name: 'Projects' }).first().click();
    await page.getByRole('option').filter({ hasText: project1 }).click();
    await page.keyboard.press('Escape');

    // Verify badge count is gone (no count displayed when 0)
    await expect(
        page.getByRole('button', { name: 'Projects' }).first().getByText(/^\d+$/)
    ).not.toBeVisible();
});

// ──────────────────────────────────────────────────
// Client Multiselect Dropdown Tests
// ──────────────────────────────────────────────────

test('test that client multiselect dropdown filters reporting by client', async ({ page }) => {
    const client1 = 'ClientFilter1 ' + Math.floor(Math.random() * 10000);
    const client2 = 'ClientFilter2 ' + Math.floor(Math.random() * 10000);
    const project1 = 'ClientProj1 ' + Math.floor(Math.random() * 10000);
    const project2 = 'ClientProj2 ' + Math.floor(Math.random() * 10000);

    await createClient(page, client1);
    await createClient(page, client2);
    await createProjectWithClient(page, project1, client1);
    await createProjectWithClient(page, project2, client2);
    await createTimeEntryWithProject(page, project1, '1h');
    await createTimeEntryWithProject(page, project2, '2h');

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Open client multiselect dropdown
    await page.getByRole('button', { name: 'Clients' }).first().click();

    // Verify both clients appear
    await expect(page.getByRole('option').filter({ hasText: client1 })).toBeVisible();
    await expect(page.getByRole('option').filter({ hasText: client2 })).toBeVisible();

    // Select client1
    await page.getByRole('option').filter({ hasText: client1 }).click();

    await Promise.all([page.keyboard.press('Escape'), waitForReportingUpdate(page)]);

    // Verify badge shows count of 1
    await expect(
        page.getByRole('button', { name: 'Clients' }).first().getByText('1')
    ).toBeVisible();

    // Verify only project1 (belonging to client1) is shown
    await expect(page.getByTestId('reporting_view').getByText(project1)).toBeVisible();
    await expect(page.getByTestId('reporting_view').getByText(project2)).not.toBeVisible();
});

test('test that client multiselect search filters the option list', async ({ page }) => {
    const client1 = 'ClientSearchAlpha ' + Math.floor(Math.random() * 10000);
    const client2 = 'ClientSearchBeta ' + Math.floor(Math.random() * 10000);

    await createClient(page, client1);
    await createClient(page, client2);

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    await page.getByRole('button', { name: 'Clients' }).first().click();

    // Search for "Alpha"
    await page.getByPlaceholder('Search for a Client...').fill('Alpha');

    await expect(page.getByRole('option').filter({ hasText: client1 })).toBeVisible();
    await expect(page.getByRole('option').filter({ hasText: client2 })).not.toBeVisible();

    await page.keyboard.press('Escape');
});

test('test that deselecting a client removes the filter', async ({ page }) => {
    const client1 = 'ClientDeselect ' + Math.floor(Math.random() * 10000);
    const project1 = 'ClientDeselectProj ' + Math.floor(Math.random() * 10000);

    await createClient(page, client1);
    await createProjectWithClient(page, project1, client1);
    await createTimeEntryWithProject(page, project1, '1h');

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Select client
    await page.getByRole('button', { name: 'Clients' }).first().click();
    await page.getByRole('option').filter({ hasText: client1 }).click();
    await Promise.all([page.keyboard.press('Escape'), waitForReportingUpdate(page)]);

    await expect(
        page.getByRole('button', { name: 'Clients' }).first().getByText('1')
    ).toBeVisible();

    // Deselect client
    await page.getByRole('button', { name: 'Clients' }).first().click();
    await page.getByRole('option').filter({ hasText: client1 }).click();
    await page.keyboard.press('Escape');

    await expect(
        page.getByRole('button', { name: 'Clients' }).first().getByText(/^\d+$/)
    ).not.toBeVisible();
});

// ──────────────────────────────────────────────────
// Task Multiselect Dropdown Tests
// ──────────────────────────────────────────────────

test('test that task filtering works in reporting', async ({ page }) => {
    const projectName = 'Task Filter Proj ' + Math.floor(Math.random() * 10000);
    const task1 = 'Task Filter A ' + Math.floor(Math.random() * 10000);
    const task2 = 'Task Filter B ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createTimeEntryWithProject(page, projectName, '30min');
    await createTask(page, projectName, task1);
    await createTask(page, projectName, task2);
    await createTimeEntryWithProjectAndTask(page, projectName, task1, '1h');
    await createTimeEntryWithProjectAndTask(page, projectName, task2, '2h');

    // Go to reporting and group by task to see individual tasks
    await goToReporting(page);

    // Filter by task1
    await page.getByRole('button', { name: 'Tasks' }).first().click();
    await page.getByRole('option').filter({ hasText: task1 }).click();

    await Promise.all([page.keyboard.press('Escape'), waitForReportingUpdate(page)]);

    // Verify the report only shows 1h (task1's duration)
    await expect(page.getByTestId('reporting_view').getByText('1h 00min').first()).toBeVisible();
});

test('test that task multiselect search filters the option list', async ({ page }) => {
    const projectName = 'TaskSearchProj ' + Math.floor(Math.random() * 10000);
    const task1 = 'TaskSearchAlpha ' + Math.floor(Math.random() * 10000);
    const task2 = 'TaskSearchBeta ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createTask(page, projectName, task1);
    await createTask(page, projectName, task2);

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    await page.getByRole('button', { name: 'Tasks' }).first().click();

    await page.getByPlaceholder('Search for a Task...').fill('Alpha');

    await expect(page.getByRole('option').filter({ hasText: task1 })).toBeVisible();
    await expect(page.getByRole('option').filter({ hasText: task2 })).not.toBeVisible();

    await page.keyboard.press('Escape');
});

// ──────────────────────────────────────────────────
// Member Multiselect Dropdown Tests
// ──────────────────────────────────────────────────

test('test that member multiselect dropdown shows current member and filters reporting', async ({
    page,
}) => {
    const projectName = 'MemberFilterProj ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createTimeEntryWithProject(page, projectName, '1h');

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Open member multiselect dropdown
    await page.getByRole('button', { name: 'Members' }).first().click();

    // Verify the current user (John Doe from fixture) appears as an option
    await expect(page.getByRole('option').filter({ hasText: 'John Doe' })).toBeVisible();

    // Select the member
    await page.getByRole('option').filter({ hasText: 'John Doe' }).click();

    await Promise.all([page.keyboard.press('Escape'), waitForReportingUpdate(page)]);

    // Verify badge shows count of 1
    await expect(
        page.getByRole('button', { name: 'Members' }).first().getByText('1')
    ).toBeVisible();

    // Verify data is still shown (since all entries belong to this member)
    await expect(page.getByTestId('reporting_view').getByText(projectName)).toBeVisible();
});

test('test that member multiselect search filters the option list', async ({ page }) => {
    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    await page.getByRole('button', { name: 'Members' }).first().click();

    // Search for the registered user
    await page.getByPlaceholder('Search for a Member...').fill('John');
    await expect(page.getByRole('option').filter({ hasText: 'John Doe' })).toBeVisible();

    // Search for a non-existent member
    await page.getByPlaceholder('Search for a Member...').fill('NonExistentMember');
    await expect(page.getByRole('option')).not.toBeVisible();

    await page.keyboard.press('Escape');
});

test('test that deselecting a member removes the filter', async ({ page }) => {
    const projectName = 'MemberDeselectProj ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createTimeEntryWithProject(page, projectName, '1h');

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Select member
    await page.getByRole('button', { name: 'Members' }).first().click();
    await page.getByRole('option').filter({ hasText: 'John Doe' }).click();
    await Promise.all([page.keyboard.press('Escape'), waitForReportingUpdate(page)]);

    await expect(
        page.getByRole('button', { name: 'Members' }).first().getByText('1')
    ).toBeVisible();

    // Deselect member
    await page.getByRole('button', { name: 'Members' }).first().click();
    await page.getByRole('option').filter({ hasText: 'John Doe' }).click();
    await page.keyboard.press('Escape');

    // Verify badge count is gone
    await expect(
        page.getByRole('button', { name: 'Members' }).first().getByText(/^\d+$/)
    ).not.toBeVisible();
});

// ──────────────────────────────────────────────────
// Tag Dropdown Tests
// ──────────────────────────────────────────────────

test('test that tag filtering works in reporting', async ({ page }) => {
    const tag1 = 'Test Tag 1 ' + Math.floor(Math.random() * 10000);
    const tag2 = 'Test Tag 2 ' + Math.floor(Math.random() * 10000);

    // Create time entries with different tags
    await createTimeEntryWithTag(page, tag1, '1h');
    await createTimeEntryWithTag(page, tag2, '2h');

    // Go to reporting and filter by tag1
    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Tags' })).toBeVisible();

    await page.getByRole('button', { name: 'Tags' }).click();
    await page.getByText(tag1).click();

    await Promise.all([page.keyboard.press('Escape'), waitForReportingUpdate(page)]);

    // Verify only time entries with tag1 are shown
    await expect(page.getByTestId('reporting_view').getByText('1h 00min').first()).toBeVisible();
});

test('test that tag dropdown search filters the option list', async ({ page }) => {
    const tag1 = 'TagSearchAlpha ' + Math.floor(Math.random() * 10000);
    const tag2 = 'TagSearchBeta ' + Math.floor(Math.random() * 10000);

    await createTimeEntryWithTag(page, tag1, '1h');
    await createTimeEntryWithTag(page, tag2, '2h');

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    await page.getByRole('button', { name: 'Tags' }).click();

    await page.getByPlaceholder('Search for a Tag...').fill('Alpha');

    await expect(page.getByRole('option').filter({ hasText: tag1 })).toBeVisible();
    await expect(page.getByRole('option').filter({ hasText: tag2 })).not.toBeVisible();

    await page.keyboard.press('Escape');
});

test('test that selecting multiple tags shows correct badge count', async ({ page }) => {
    const tag1 = 'MultiTag1 ' + Math.floor(Math.random() * 10000);
    const tag2 = 'MultiTag2 ' + Math.floor(Math.random() * 10000);

    await createTimeEntryWithTag(page, tag1, '1h');
    await createTimeEntryWithTag(page, tag2, '2h');

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Select both tags
    await page.getByRole('button', { name: 'Tags' }).click();
    await page.getByRole('option').filter({ hasText: tag1 }).click();
    await page.getByRole('option').filter({ hasText: tag2 }).click();

    await Promise.all([page.keyboard.press('Escape'), waitForReportingUpdate(page)]);

    // Verify badge shows count of 2
    await expect(page.getByRole('button', { name: 'Tags' }).getByText('2')).toBeVisible();
});

test('test that deselecting a tag removes the filter', async ({ page }) => {
    const tag1 = 'TagDeselect ' + Math.floor(Math.random() * 10000);

    await createTimeEntryWithTag(page, tag1, '1h');

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Select tag
    await page.getByRole('button', { name: 'Tags' }).click();
    await page.getByRole('option').filter({ hasText: tag1 }).click();
    await Promise.all([page.keyboard.press('Escape'), waitForReportingUpdate(page)]);

    await expect(page.getByRole('button', { name: 'Tags' }).getByText('1')).toBeVisible();

    // Deselect tag
    await page.getByRole('button', { name: 'Tags' }).click();
    await page.getByRole('option').filter({ hasText: tag1 }).click();
    await page.keyboard.press('Escape');

    await expect(page.getByRole('button', { name: 'Tags' }).getByText(/^\d+$/)).not.toBeVisible();
});

test('test that creating a tag inline from the reporting filter works', async ({ page }) => {
    const projectName = 'TagCreateProj ' + Math.floor(Math.random() * 10000);
    const newTag = 'InlineTag ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createTimeEntryWithProject(page, projectName, '1h');

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Open tag dropdown and create a new tag
    await page.getByRole('button', { name: 'Tags' }).click();
    await page.getByText('Create new tag').click();
    await page.getByPlaceholder('Tag Name').fill(newTag);

    await Promise.all([
        page.getByRole('button', { name: 'Create Tag' }).click(),
        page.waitForResponse(
            (response) => response.url().includes('/tags') && response.status() === 201
        ),
    ]);

    // The new tag should now be selected in the dropdown (badge should show 1)
    await expect(page.getByRole('button', { name: 'Tags' }).getByText('1')).toBeVisible();
});

// ──────────────────────────────────────────────────
// Billable Select Tests
// ──────────────────────────────────────────────────

test('test that billable status filtering works in reporting', async ({ page }) => {
    // Create billable and non-billable time entries
    await createTimeEntryWithBillableStatus(page, true, '1h');
    await createTimeEntryWithBillableStatus(page, false, '2h');

    // Go to reporting and filter by billable
    await goToReporting(page);

    await page.getByRole('combobox').filter({ hasText: 'Billable' }).click();
    await Promise.all([
        page.getByRole('option', { name: 'Billable', exact: true }).click(),
        waitForReportingUpdate(page),
    ]);

    await expect(page.getByTestId('reporting_view').getByText('1h 00min').first()).toBeVisible();
});

test('test that billable filter can switch between all three states', async ({ page }) => {
    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    const billableSelect = page.getByRole('combobox').filter({ hasText: 'Billable' });

    // Switch to Billable
    await billableSelect.click();
    await Promise.all([
        page.getByRole('option', { name: 'Billable', exact: true }).click(),
        waitForReportingUpdate(page),
    ]);

    // Switch to Non Billable
    await billableSelect.click();
    await Promise.all([
        page.getByRole('option', { name: 'Non Billable', exact: true }).click(),
        waitForReportingUpdate(page),
    ]);

    // Verify "Non Billable" is displayed
    await expect(billableSelect).toContainText('Non Billable');

    // Switch back to Both (cached by TanStack Query, no new API request)
    await billableSelect.click();
    await page.getByRole('option', { name: 'Both' }).click();
    await expect(billableSelect).toContainText('Billable');
});

// ──────────────────────────────────────────────────
// Rounding Controls Tests
// ──────────────────────────────────────────────────

test('test that rounding can be enabled', async ({ page }) => {
    const projectName = 'RoundingProj ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createTimeEntryWithProject(page, projectName, '1h 7min');

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Verify rounding is off by default
    await expect(page.getByRole('button', { name: /Rounding off/ })).toBeVisible();

    // Open rounding controls and enable rounding
    await page.getByRole('button', { name: /Rounding off/ }).click();

    const reportUpdatePromise = waitForReportingUpdate(page);
    await page.getByRole('switch', { name: 'Enable Rounding' }).click();
    await reportUpdatePromise;

    // Close the popover by clicking elsewhere
    await page.keyboard.press('Escape');

    // Verify button text changed to "on"
    await expect(page.getByRole('button', { name: /Rounding on/ })).toBeVisible();
});

// ──────────────────────────────────────────────────
// Export Tests
// ──────────────────────────────────────────────────

test('test that export dropdown shows all format options', async ({ page }) => {
    const projectName = 'Export Test ' + Math.floor(Math.random() * 10000);
    await createProject(page, projectName);
    await createTimeEntryWithProject(page, projectName, '1h');

    // Go to reporting page
    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Click the export button
    await page.getByRole('button', { name: 'Export' }).click();

    // Verify all 4 format options are visible
    await expect(page.getByRole('menuitem', { name: /Export as PDF/i })).toBeVisible();
    await expect(page.getByRole('menuitem', { name: /Export as Excel/i })).toBeVisible();
    await expect(page.getByRole('menuitem', { name: /Export as CSV/i })).toBeVisible();
    await expect(page.getByRole('menuitem', { name: /Export as ODS/i })).toBeVisible();
});

test('test that CSV export triggers download', async ({ page }) => {
    const projectName = 'CSV Export ' + Math.floor(Math.random() * 10000);
    await createProject(page, projectName);
    await createTimeEntryWithProject(page, projectName, '1h');

    // Go to reporting page
    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Click export and select CSV, wait for the export API response with a download URL
    await page.getByRole('button', { name: 'Export' }).click();
    const [exportResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/aggregate/export') &&
                response.status() === 200
        ),
        page.getByRole('menuitem', { name: /Export as CSV/i }).click(),
    ]);

    // Verify the API returned a download URL
    const responseBody = await exportResponse.json();
    expect(responseBody.download_url).toBeTruthy();

    // Verify the export success modal appeared
    await expect(page.getByText('Export Successful!')).toBeVisible();

    // Verify the download URL is accessible and returns CSV content
    const downloadResponse = await page.request.get(responseBody.download_url);
    expect(downloadResponse.ok()).toBeTruthy();
    const contentType = downloadResponse.headers()['content-type'];
    expect(contentType).toContain('csv');
});

// ──────────────────────────────────────────────────
// Group By Tests
// ──────────────────────────────────────────────────

test('test that group by select changes report grouping', async ({ page }) => {
    const projectName = 'GroupBy Test ' + Math.floor(Math.random() * 10000);
    await createProject(page, projectName);
    await createTimeEntryWithProject(page, projectName, '1h');

    // Go to reporting page
    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Find the "Group by" selects within the reporting table
    const groupBySelects = page.locator('[data-testid="reporting_view"]').getByRole('combobox');

    // Click the first group by select to change grouping
    await groupBySelects.filter({ hasText: 'Project' }).first().click();

    // Select "Members" option and wait for the table query to update (has sub_group param)
    const [aggregateResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/aggregate') &&
                response.url().includes('sub_group') &&
                response.status() === 200
        ),
        page.getByRole('option', { name: 'Members' }).click(),
    ]);

    // Verify the API request contains the correct group parameter
    const requestUrl = new URL(aggregateResponse.url());
    expect(requestUrl.searchParams.get('group')).toBe('user');

    // Verify the grouping changed (the select should now show "Members")
    await expect(groupBySelects.filter({ hasText: 'Members' }).first()).toBeVisible();
});

test('test that setting group by to current sub group triggers sub group fallback', async ({
    page,
}) => {
    const projectName = 'Fallback Test ' + Math.floor(Math.random() * 10000);
    await createProject(page, projectName);
    await createTimeEntryWithProject(page, projectName, '1h');

    // Go to reporting page
    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Find the "Group by" selects within the reporting table
    const groupBySelects = page.locator('[data-testid="reporting_view"]').getByRole('combobox');

    // Default state: group=Project, subGroup=Tasks
    // Change group to "Tasks" (which is the current sub group)
    await groupBySelects.filter({ hasText: 'Projects' }).first().click();

    const [aggregateResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/aggregate') &&
                response.url().includes('sub_group') &&
                response.status() === 200
        ),
        page.getByRole('option', { name: 'Tasks' }).click(),
    ]);

    // Verify the API request has group=task and sub_group changed away from task
    const requestUrl = new URL(aggregateResponse.url());
    expect(requestUrl.searchParams.get('group')).toBe('task');
    expect(requestUrl.searchParams.get('sub_group')).not.toBe('task');

    // The group should now be "Tasks"
    await expect(groupBySelects.filter({ hasText: 'Tasks' }).first()).toBeVisible();

    // The sub group should have fallen back to a different value (not "Tasks")
    await expect(groupBySelects.filter({ hasText: 'Members' }).first()).toBeVisible();
});
