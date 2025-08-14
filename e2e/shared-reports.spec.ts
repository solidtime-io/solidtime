import { expect, Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';

async function goToSharedReports(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/reporting/shared');
}

async function goToReporting(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/reporting');
}

async function createTimeEntryWithProject(page: Page, projectName: string, duration: string) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/projects');
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(projectName);
    await page.getByRole('dialog').getByRole('button', { name: 'Create Project' }).click();
    await page.getByText(projectName).waitFor({ state: 'visible' });

    await page.goto(PLAYWRIGHT_BASE_URL + '/time');
    await page.getByRole('button', { name: 'Manual time entry' }).click();
    await page.getByTestId('time_entry_description').fill(`Time entry for ${projectName}`);
    await page.getByRole('button', { name: 'No Project' }).click();
    await page.getByText(projectName).click();
    await page.locator('[role="dialog"] input[name="Duration"]').fill(duration);
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');
    await Promise.all([
        page.getByRole('button', { name: 'Create Time Entry' }).click(),
        page.waitForResponse(
            (response) => response.url().includes('/time-entries') && response.status() === 201
        ),
    ]);
}

async function createTimeEntryWithTag(page: Page, tagName: string, duration: string) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/time');
    await page.getByRole('button', { name: 'Manual time entry' }).click();
    await page.getByTestId('time_entry_description').fill(`Time entry with tag ${tagName}`);
    await page.getByRole('button', { name: 'Tags' }).click();
    await page.getByText('Create new tag').click();
    await page.getByPlaceholder('Tag Name').fill(tagName);
    await page.getByRole('button', { name: 'Create Tag' }).click();
    await page.waitForLoadState('networkidle');
    await page.locator('[role="dialog"] input[name="Duration"]').fill(duration);
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');
    await page.getByRole('button', { name: 'Create Time Entry' }).click();
}

async function createTimeEntryWithBillableStatus(
    page: Page,
    isBillable: boolean,
    duration: string
) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/time');
    await page.getByRole('button', { name: 'Manual time entry' }).click();
    await page
        .getByTestId('time_entry_description')
        .fill(`Time entry ${isBillable ? 'billable' : 'non-billable'}`);
    await page.getByRole('button', { name: 'Non-Billable' }).click();
    if (!isBillable) {
        await page.getByRole('option', { name: 'Non Billable', exact: true }).click();
    } else {
        await page.getByRole('option', { name: 'Billable', exact: true }).click();
    }
    await page.locator('[role="dialog"] input[name="Duration"]').fill(duration);
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');
    await page.getByRole('button', { name: 'Create Time Entry' }).click();
}

async function createReport(
    page: Page,
    reportName: string,
    options: {
        projectFilter?: string;
        tagFilter?: string;
        billableFilter?: 'billable' | 'non-billable' | 'all';
        timeRange?: { start: string; end: string };
    } = {}
) {
    await goToReporting(page);
    await page.waitForLoadState('networkidle');

    // Apply filters if specified
    if (options.projectFilter) {
        await page.getByRole('button', { name: 'Project' }).nth(0).click();
        await page.getByText(options.projectFilter).click();
        await page.keyboard.press('Escape');
        await page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/aggregate') && response.status() === 200
        );
    }

    if (options.tagFilter) {
        await page.getByRole('button', { name: 'Tags' }).click();
        await page.getByText(options.tagFilter).click();
        await page.keyboard.press('Escape');
        await page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/aggregate') && response.status() === 200
        );
    }

    if (options.billableFilter && options.billableFilter !== 'all') {
        await page.getByRole('button', { name: 'Billable' }).click();
        if (options.billableFilter === 'billable') {
            await page.getByRole('option', { name: 'Billable', exact: true }).click();
        } else {
            await page.getByRole('option', { name: 'Non Billable', exact: true }).click();
        }
        await page.keyboard.press('Escape');
        await page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/aggregate') && response.status() === 200
        );
    }

    // Set custom time range if specified
    if (options.timeRange) {
        await page.getByRole('button', { name: 'This Week' }).click();
        await page.getByRole('option', { name: 'Custom Range' }).click();
        await page.locator('input[name="startDate"]').fill(options.timeRange.start);
        await page.locator('input[name="endDate"]').fill(options.timeRange.end);
        await page.getByRole('button', { name: 'Apply' }).click();
        await page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/aggregate') && response.status() === 200
        );
    }

    await page.waitForLoadState('networkidle');

    // Save the report
    await page.getByRole('button', { name: 'Save Report' }).click();
    await page.getByLabel('Report Name').fill(reportName);
    await page.getByRole('dialog').getByRole('button', { name: 'Create Report' }).click();
    await page.waitForLoadState('networkidle');
}

async function makeReportPublic(page: Page, reportName: string): Promise<string> {
    await goToSharedReports(page);
    await page.waitForLoadState('networkidle');

    // Find the report row and click the edit button
    const reportRow = page.locator('tr').filter({ hasText: reportName });
    await reportRow.getByRole('button', { name: 'Edit' }).click();

    // Make the report public
    await page.getByRole('switch', { name: 'Make report public' }).click();

    // Wait for the API response
    await page.waitForResponse(
        (response) => response.url().includes('/reports/') && response.status() === 200
    );

    // Save the changes
    await page.getByRole('button', { name: 'Save' }).click();
    await page.waitForLoadState('networkidle');

    // Get the public URL
    const copyButton = reportRow.getByRole('button', { name: 'Copy URL' });
    await copyButton.click();

    // Extract the URL from clipboard or from the button's data attribute
    const publicUrl = await page.evaluate(() => navigator.clipboard.readText());

    return publicUrl;
}

test('create shared report with project filter', async ({ page }) => {
    const projectName = 'Shared Report Project ' + Math.floor(Math.random() * 10000);
    const reportName = 'Project Report ' + Math.floor(Math.random() * 10000);

    // Create test data
    await createTimeEntryWithProject(page, projectName, '2h');
    await createTimeEntryWithProject(page, 'Other Project', '1h');

    // Create a report with project filter
    await createReport(page, reportName, { projectFilter: projectName });

    // Make the report public
    const publicUrl = await makeReportPublic(page, reportName);

    // Verify the report appears in shared reports list
    await expect(page.getByText(reportName)).toBeVisible();
    await expect(page.getByText('Public')).toBeVisible();

    expect(publicUrl).toContain('/shared-report#');
});

test('create shared report with tag filter', async ({ page }) => {
    const tagName = 'SharedTag' + Math.floor(Math.random() * 10000);
    const reportName = 'Tag Report ' + Math.floor(Math.random() * 10000);

    // Create test data
    await createTimeEntryWithTag(page, tagName, '1h 30min');
    await createTimeEntryWithTag(page, 'OtherTag', '45min');

    // Create a report with tag filter
    await createReport(page, reportName, { tagFilter: tagName });

    // Make the report public
    const publicUrl = await makeReportPublic(page, reportName);

    // Verify the report appears in shared reports list
    await expect(page.getByText(reportName)).toBeVisible();
    await expect(page.getByText('Public')).toBeVisible();

    expect(publicUrl).toContain('/shared-report#');
});

test('create shared report with billable filter', async ({ page }) => {
    const reportName = 'Billable Report ' + Math.floor(Math.random() * 10000);

    // Create test data
    await createTimeEntryWithBillableStatus(page, true, '2h');
    await createTimeEntryWithBillableStatus(page, false, '1h');

    // Create a report with billable filter
    await createReport(page, reportName, { billableFilter: 'billable' });

    // Make the report public
    const publicUrl = await makeReportPublic(page, reportName);

    // Verify the report appears in shared reports list
    await expect(page.getByText(reportName)).toBeVisible();
    await expect(page.getByText('Public')).toBeVisible();

    expect(publicUrl).toContain('/shared-report#');
});

test('create shared report with custom time range', async ({ page }) => {
    const projectName = 'TimeRange Project ' + Math.floor(Math.random() * 10000);
    const reportName = 'TimeRange Report ' + Math.floor(Math.random() * 10000);

    // Create test data
    await createTimeEntryWithProject(page, projectName, '3h');

    // Create a report with custom time range (last 30 days)
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - 30);
    const endDate = new Date();

    await createReport(page, reportName, {
        projectFilter: projectName,
        timeRange: {
            start: startDate.toISOString().split('T')[0],
            end: endDate.toISOString().split('T')[0],
        },
    });

    // Make the report public
    const publicUrl = await makeReportPublic(page, reportName);

    // Verify the report appears in shared reports list
    await expect(page.getByText(reportName)).toBeVisible();
    await expect(page.getByText('Public')).toBeVisible();

    expect(publicUrl).toContain('/shared-report#');
});

test('create shared report with multiple filters', async ({ page }) => {
    const projectName = 'MultiFilter Project ' + Math.floor(Math.random() * 10000);
    const tagName = 'MultiTag' + Math.floor(Math.random() * 10000);
    const reportName = 'MultiFilter Report ' + Math.floor(Math.random() * 10000);

    // Create test data
    await createTimeEntryWithProject(page, projectName, '2h');

    // Create a time entry with both project and tag
    await page.goto(PLAYWRIGHT_BASE_URL + '/time');
    await page.getByRole('button', { name: 'Manual time entry' }).click();
    await page.getByTestId('time_entry_description').fill('Multi-filter entry');

    // Set project
    await page.getByRole('button', { name: 'No Project' }).click();
    await page.getByText(projectName).click();

    // Set tag
    await page.getByRole('button', { name: 'Tags' }).click();
    await page.getByText('Create new tag').click();
    await page.getByPlaceholder('Tag Name').fill(tagName);
    await page.getByRole('button', { name: 'Create Tag' }).click();
    await page.waitForLoadState('networkidle');

    // Set as billable
    await page.getByRole('button', { name: 'Non-Billable' }).click();
    await page.getByRole('option', { name: 'Billable', exact: true }).click();

    await page.locator('[role="dialog"] input[name="Duration"]').fill('1h 30min');
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');
    await page.getByRole('button', { name: 'Create Time Entry' }).click();

    // Create a report with multiple filters
    await createReport(page, reportName, {
        projectFilter: projectName,
        tagFilter: tagName,
        billableFilter: 'billable',
    });

    // Make the report public
    const publicUrl = await makeReportPublic(page, reportName);

    // Verify the report appears in shared reports list
    await expect(page.getByText(reportName)).toBeVisible();
    await expect(page.getByText('Public')).toBeVisible();

    expect(publicUrl).toContain('/shared-report#');
});

test('toggle report visibility from public to private', async ({ page }) => {
    const projectName = 'Toggle Project ' + Math.floor(Math.random() * 10000);
    const reportName = 'Toggle Report ' + Math.floor(Math.random() * 10000);

    // Create test data
    await createTimeEntryWithProject(page, projectName, '1h');

    // Create a report
    await createReport(page, reportName, { projectFilter: projectName });

    // Make the report public
    await makeReportPublic(page, reportName);

    // Verify it's public
    await expect(page.getByText('Public')).toBeVisible();

    // Make it private again
    const reportRow = page.locator('tr').filter({ hasText: reportName });
    await reportRow.getByRole('button', { name: 'Edit' }).click();
    await page.getByRole('switch', { name: 'Make report public' }).click();
    await page.waitForResponse(
        (response) => response.url().includes('/reports/') && response.status() === 200
    );
    await page.getByRole('button', { name: 'Save' }).click();
    await page.waitForLoadState('networkidle');

    // Verify it's now private
    await expect(page.getByText('Private')).toBeVisible();
    await expect(page.getByText('Public')).not.toBeVisible();
});

test('edit shared report name and description', async ({ page }) => {
    const projectName = 'Edit Project ' + Math.floor(Math.random() * 10000);
    const reportName = 'Original Report ' + Math.floor(Math.random() * 10000);
    const updatedName = 'Updated Report ' + Math.floor(Math.random() * 10000);
    const description = 'This is an updated description';

    // Create test data
    await createTimeEntryWithProject(page, projectName, '1h');

    // Create a report
    await createReport(page, reportName, { projectFilter: projectName });

    // Make the report public
    await makeReportPublic(page, reportName);

    // Edit the report
    const reportRow = page.locator('tr').filter({ hasText: reportName });
    await reportRow.getByRole('button', { name: 'Edit' }).click();

    await page.getByLabel('Report Name').fill(updatedName);
    await page.getByLabel('Description').fill(description);
    await page.getByRole('button', { name: 'Save' }).click();
    await page.waitForLoadState('networkidle');

    // Verify the changes
    await expect(page.getByText(updatedName)).toBeVisible();
    await expect(page.getByText(reportName)).not.toBeVisible();
});

test('delete shared report', async ({ page }) => {
    const projectName = 'Delete Project ' + Math.floor(Math.random() * 10000);
    const reportName = 'Delete Report ' + Math.floor(Math.random() * 10000);

    // Create test data
    await createTimeEntryWithProject(page, projectName, '1h');

    // Create a report
    await createReport(page, reportName, { projectFilter: projectName });

    // Make the report public
    await makeReportPublic(page, reportName);

    // Delete the report
    const reportRow = page.locator('tr').filter({ hasText: reportName });
    await reportRow.getByRole('button', { name: 'Delete' }).click();
    await page.getByRole('button', { name: 'Delete Report' }).click();
    await page.waitForLoadState('networkidle');

    // Verify the report is deleted
    await expect(page.getByText(reportName)).not.toBeVisible();
});
