import { expect, Page, Browser } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';

async function goToSharedReports(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/reporting/shared');
}

async function goToReporting(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/reporting');
}

async function createTimeEntryWithProject(
    page: Page,
    projectName: string,
    duration: string,
    description: string = ''
) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/projects');
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(projectName);
    await page.getByRole('dialog').getByRole('button', { name: 'Create Project' }).click();
    await page.getByText(projectName).waitFor({ state: 'visible' });

    await page.goto(PLAYWRIGHT_BASE_URL + '/time');
    await page.getByRole('button', { name: 'Manual time entry' }).click();
    await page
        .getByTestId('time_entry_description')
        .fill(description || `Time entry for ${projectName}`);
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

async function createTimeEntryWithTag(
    page: Page,
    tagName: string,
    duration: string,
    description: string = ''
) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/time');
    await page.getByRole('button', { name: 'Manual time entry' }).click();
    await page
        .getByTestId('time_entry_description')
        .fill(description || `Time entry with tag ${tagName}`);
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
    duration: string,
    description: string = ''
) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/time');
    await page.getByRole('button', { name: 'Manual time entry' }).click();
    await page
        .getByTestId('time_entry_description')
        .fill(description || `Time entry ${isBillable ? 'billable' : 'non-billable'}`);
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

async function createUnauthenticatedPage(browser: Browser): Promise<Page> {
    const context = await browser.newContext();
    const page = await context.newPage();
    return page;
}

test('verify shared report data accuracy with project filter', async ({ page, browser }) => {
    const projectName = 'Accuracy Project ' + Math.floor(Math.random() * 10000);
    const otherProjectName = 'Other Accuracy Project ' + Math.floor(Math.random() * 10000);
    const reportName = 'Accuracy Report ' + Math.floor(Math.random() * 10000);

    // Create test data with specific durations
    await createTimeEntryWithProject(page, projectName, '2h 30min', 'Task 1');
    await createTimeEntryWithProject(page, projectName, '1h 15min', 'Task 2');
    await createTimeEntryWithProject(page, otherProjectName, '3h', 'Other task');

    // Create and make report public with project filter
    await createReport(page, reportName, { projectFilter: projectName });
    const publicUrl = await makeReportPublic(page, reportName);

    // Verify data in authenticated reporting view
    await goToReporting(page);
    await page.getByRole('button', { name: 'Project' }).nth(0).click();
    await page.getByText(projectName).click();
    await page.keyboard.press('Escape');
    await page.waitForResponse(
        (response) =>
            response.url().includes('/time-entries/aggregate') && response.status() === 200
    );

    // Note expected total: 2h 30min + 1h 15min = 3h 45min
    await expect(page.getByText('3h 45min')).toBeVisible();

    // Verify same data in public view
    const unauthenticatedPage = await createUnauthenticatedPage(browser);
    await unauthenticatedPage.goto(publicUrl);
    await unauthenticatedPage.waitForLoadState('networkidle');

    // Verify total time matches
    await expect(unauthenticatedPage.getByText('3h 45min')).toBeVisible();
    await expect(unauthenticatedPage.getByText(projectName)).toBeVisible();
    await expect(unauthenticatedPage.getByText('Task 1')).toBeVisible();
    await expect(unauthenticatedPage.getByText('Task 2')).toBeVisible();
    await expect(unauthenticatedPage.getByText(otherProjectName)).not.toBeVisible();

    await unauthenticatedPage.close();
});

test('verify shared report data accuracy with tag filter', async ({ page, browser }) => {
    const tagName = 'AccuracyTag' + Math.floor(Math.random() * 10000);
    const otherTagName = 'OtherTag' + Math.floor(Math.random() * 10000);
    const reportName = 'Tag Accuracy Report ' + Math.floor(Math.random() * 10000);

    // Create test data with specific durations
    await createTimeEntryWithTag(page, tagName, '1h 30min', 'Tagged task 1');
    await createTimeEntryWithTag(page, tagName, '2h 15min', 'Tagged task 2');
    await createTimeEntryWithTag(page, otherTagName, '45min', 'Other tagged task');

    // Create and make report public with tag filter
    await createReport(page, reportName, { tagFilter: tagName });
    const publicUrl = await makeReportPublic(page, reportName);

    // Verify data in authenticated reporting view
    await goToReporting(page);
    await page.getByRole('button', { name: 'Tags' }).click();
    await page.getByText(tagName).click();
    await page.keyboard.press('Escape');
    await page.waitForResponse(
        (response) =>
            response.url().includes('/time-entries/aggregate') && response.status() === 200
    );

    // Note expected total: 1h 30min + 2h 15min = 3h 45min
    await expect(page.getByText('3h 45min')).toBeVisible();

    // Verify same data in public view
    const unauthenticatedPage = await createUnauthenticatedPage(browser);
    await unauthenticatedPage.goto(publicUrl);
    await unauthenticatedPage.waitForLoadState('networkidle');

    // Verify total time matches
    await expect(unauthenticatedPage.getByText('3h 45min')).toBeVisible();
    await expect(unauthenticatedPage.getByText(tagName)).toBeVisible();
    await expect(unauthenticatedPage.getByText('Tagged task 1')).toBeVisible();
    await expect(unauthenticatedPage.getByText('Tagged task 2')).toBeVisible();
    await expect(unauthenticatedPage.getByText(otherTagName)).not.toBeVisible();

    await unauthenticatedPage.close();
});

test('verify shared report data accuracy with billable filter', async ({ page, browser }) => {
    const reportName = 'Billable Accuracy Report ' + Math.floor(Math.random() * 10000);

    // Create test data with specific durations
    await createTimeEntryWithBillableStatus(page, true, '2h', 'Billable task 1');
    await createTimeEntryWithBillableStatus(page, true, '1h 30min', 'Billable task 2');
    await createTimeEntryWithBillableStatus(page, false, '45min', 'Non-billable task');

    // Create and make report public with billable filter
    await createReport(page, reportName, { billableFilter: 'billable' });
    const publicUrl = await makeReportPublic(page, reportName);

    // Verify data in authenticated reporting view
    await goToReporting(page);
    await page.getByRole('button', { name: 'Billable' }).click();
    await page.getByRole('option', { name: 'Billable', exact: true }).click();
    await page.keyboard.press('Escape');
    await page.waitForResponse(
        (response) =>
            response.url().includes('/time-entries/aggregate') && response.status() === 200
    );

    // Note expected total: 2h + 1h 30min = 3h 30min
    await expect(page.getByText('3h 30min')).toBeVisible();

    // Verify same data in public view
    const unauthenticatedPage = await createUnauthenticatedPage(browser);
    await unauthenticatedPage.goto(publicUrl);
    await unauthenticatedPage.waitForLoadState('networkidle');

    // Verify total time matches
    await expect(unauthenticatedPage.getByText('3h 30min')).toBeVisible();
    await expect(unauthenticatedPage.getByText('Billable task 1')).toBeVisible();
    await expect(unauthenticatedPage.getByText('Billable task 2')).toBeVisible();
    await expect(unauthenticatedPage.getByText('Non-billable task')).not.toBeVisible();

    await unauthenticatedPage.close();
});

test('verify shared report data accuracy with non-billable filter', async ({ page, browser }) => {
    const reportName = 'Non-Billable Accuracy Report ' + Math.floor(Math.random() * 10000);

    // Create test data with specific durations
    await createTimeEntryWithBillableStatus(page, false, '1h 45min', 'Non-billable task 1');
    await createTimeEntryWithBillableStatus(page, false, '2h 30min', 'Non-billable task 2');
    await createTimeEntryWithBillableStatus(page, true, '1h', 'Billable task');

    // Create and make report public with non-billable filter
    await createReport(page, reportName, { billableFilter: 'non-billable' });
    const publicUrl = await makeReportPublic(page, reportName);

    // Verify data in authenticated reporting view
    await goToReporting(page);
    await page.getByRole('button', { name: 'Billable' }).click();
    await page.getByRole('option', { name: 'Non Billable', exact: true }).click();
    await page.keyboard.press('Escape');
    await page.waitForResponse(
        (response) =>
            response.url().includes('/time-entries/aggregate') && response.status() === 200
    );

    // Note expected total: 1h 45min + 2h 30min = 4h 15min
    await expect(page.getByText('4h 15min')).toBeVisible();

    // Verify same data in public view
    const unauthenticatedPage = await createUnauthenticatedPage(browser);
    await unauthenticatedPage.goto(publicUrl);
    await unauthenticatedPage.waitForLoadState('networkidle');

    // Verify total time matches
    await expect(unauthenticatedPage.getByText('4h 15min')).toBeVisible();
    await expect(unauthenticatedPage.getByText('Non-billable task 1')).toBeVisible();
    await expect(unauthenticatedPage.getByText('Non-billable task 2')).toBeVisible();
    await expect(unauthenticatedPage.getByText('Billable task')).not.toBeVisible();

    await unauthenticatedPage.close();
});

test('verify shared report data accuracy with multiple filters', async ({ page, browser }) => {
    const projectName = 'MultiAccuracy Project ' + Math.floor(Math.random() * 10000);
    const tagName = 'MultiAccuracyTag' + Math.floor(Math.random() * 10000);
    const reportName = 'MultiAccuracy Report ' + Math.floor(Math.random() * 10000);

    // Create test data
    await createTimeEntryWithProject(page, projectName, '1h', 'Project only');

    // Create a time entry with project, tag, and billable status
    await page.goto(PLAYWRIGHT_BASE_URL + '/time');
    await page.getByRole('button', { name: 'Manual time entry' }).click();
    await page.getByTestId('time_entry_description').fill('Multi-filter matched entry');

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

    await page.locator('[role="dialog"] input[name="Duration"]').fill('2h 30min');
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');
    await page.getByRole('button', { name: 'Create Time Entry' }).click();

    // Create another entry that won't match all filters
    await page.goto(PLAYWRIGHT_BASE_URL + '/time');
    await page.getByRole('button', { name: 'Manual time entry' }).click();
    await page.getByTestId('time_entry_description').fill('Partial match entry');

    // Set same project but different tag and non-billable
    await page.getByRole('button', { name: 'No Project' }).click();
    await page.getByText(projectName).click();

    await page.getByRole('button', { name: 'Tags' }).click();
    await page.getByText('Create new tag').click();
    await page.getByPlaceholder('Tag Name').fill('DifferentTag');
    await page.getByRole('button', { name: 'Create Tag' }).click();
    await page.waitForLoadState('networkidle');

    await page.locator('[role="dialog"] input[name="Duration"]').fill('1h 15min');
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');
    await page.getByRole('button', { name: 'Create Time Entry' }).click();

    // Create and make report public with multiple filters
    await createReport(page, reportName, {
        projectFilter: projectName,
        tagFilter: tagName,
        billableFilter: 'billable',
    });
    const publicUrl = await makeReportPublic(page, reportName);

    // Verify data in authenticated reporting view
    await goToReporting(page);
    await page.getByRole('button', { name: 'Project' }).nth(0).click();
    await page.getByText(projectName).click();
    await page.keyboard.press('Escape');
    await page.waitForResponse(
        (response) =>
            response.url().includes('/time-entries/aggregate') && response.status() === 200
    );

    await page.getByRole('button', { name: 'Tags' }).click();
    await page.getByText(tagName).click();
    await page.keyboard.press('Escape');
    await page.waitForResponse(
        (response) =>
            response.url().includes('/time-entries/aggregate') && response.status() === 200
    );

    await page.getByRole('button', { name: 'Billable' }).click();
    await page.getByRole('option', { name: 'Billable', exact: true }).click();
    await page.keyboard.press('Escape');
    await page.waitForResponse(
        (response) =>
            response.url().includes('/time-entries/aggregate') && response.status() === 200
    );

    // Should only show the entry that matches all filters (2h 30min)
    await expect(page.getByText('2h 30min')).toBeVisible();

    // Verify same data in public view
    const unauthenticatedPage = await createUnauthenticatedPage(browser);
    await unauthenticatedPage.goto(publicUrl);
    await unauthenticatedPage.waitForLoadState('networkidle');

    // Verify only the matching entry is shown
    await expect(unauthenticatedPage.getByText('2h 30min')).toBeVisible();
    await expect(unauthenticatedPage.getByText('Multi-filter matched entry')).toBeVisible();
    await expect(unauthenticatedPage.getByText('Project only')).not.toBeVisible();
    await expect(unauthenticatedPage.getByText('Partial match entry')).not.toBeVisible();

    await unauthenticatedPage.close();
});

test('verify shared report data accuracy with time range filter', async ({ page, browser }) => {
    const projectName = 'TimeRange Accuracy Project ' + Math.floor(Math.random() * 10000);
    const reportName = 'TimeRange Accuracy Report ' + Math.floor(Math.random() * 10000);

    // Create test data within date range
    await createTimeEntryWithProject(page, projectName, '1h 30min', 'Within range 1');
    await createTimeEntryWithProject(page, projectName, '2h 15min', 'Within range 2');

    // Create and make report public with time range
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - 1);
    const endDate = new Date();
    endDate.setDate(endDate.getDate() + 1);

    await createReport(page, reportName, {
        projectFilter: projectName,
        timeRange: {
            start: startDate.toISOString().split('T')[0],
            end: endDate.toISOString().split('T')[0],
        },
    });
    const publicUrl = await makeReportPublic(page, reportName);

    // Verify data in authenticated reporting view
    await goToReporting(page);
    await page.getByRole('button', { name: 'Project' }).nth(0).click();
    await page.getByText(projectName).click();
    await page.keyboard.press('Escape');
    await page.waitForResponse(
        (response) =>
            response.url().includes('/time-entries/aggregate') && response.status() === 200
    );

    await page.getByRole('button', { name: 'This Week' }).click();
    await page.getByRole('option', { name: 'Custom Range' }).click();
    await page.locator('input[name="startDate"]').fill(startDate.toISOString().split('T')[0]);
    await page.locator('input[name="endDate"]').fill(endDate.toISOString().split('T')[0]);
    await page.getByRole('button', { name: 'Apply' }).click();
    await page.waitForResponse(
        (response) =>
            response.url().includes('/time-entries/aggregate') && response.status() === 200
    );

    // Note expected total: 1h 30min + 2h 15min = 3h 45min
    await expect(page.getByText('3h 45min')).toBeVisible();

    // Verify same data in public view
    const unauthenticatedPage = await createUnauthenticatedPage(browser);
    await unauthenticatedPage.goto(publicUrl);
    await unauthenticatedPage.waitForLoadState('networkidle');

    // Verify total time matches
    await expect(unauthenticatedPage.getByText('3h 45min')).toBeVisible();
    await expect(unauthenticatedPage.getByText('Within range 1')).toBeVisible();
    await expect(unauthenticatedPage.getByText('Within range 2')).toBeVisible();

    await unauthenticatedPage.close();
});

test('verify shared report shows zero data when no entries match filters', async ({
    page,
    browser,
}) => {
    const projectName = 'NoMatch Project ' + Math.floor(Math.random() * 10000);
    const tagName = 'NoMatchTag' + Math.floor(Math.random() * 10000);
    const reportName = 'NoMatch Report ' + Math.floor(Math.random() * 10000);

    // Create test data that won't match our filters
    await createTimeEntryWithProject(page, 'Other Project', '1h', 'Other entry');

    // Create and make report public with filters that won't match
    await createReport(page, reportName, {
        projectFilter: projectName, // This project doesn't exist
        tagFilter: tagName, // This tag doesn't exist
    });
    const publicUrl = await makeReportPublic(page, reportName);

    // Verify data in public view shows zero/empty results
    const unauthenticatedPage = await createUnauthenticatedPage(browser);
    await unauthenticatedPage.goto(publicUrl);
    await unauthenticatedPage.waitForLoadState('networkidle');

    // Verify no data is shown
    await expect(unauthenticatedPage.getByText('0h 00min')).toBeVisible();
    await expect(unauthenticatedPage.getByText('No data available')).toBeVisible();

    await unauthenticatedPage.close();
});
