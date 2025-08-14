import { expect, Page, Browser } from '@playwright/test';
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

async function createUnauthenticatedPage(browser: Browser): Promise<Page> {
    const context = await browser.newContext();
    const page = await context.newPage();
    return page;
}

test('access public shared report without authentication', async ({ page, browser }) => {
    const projectName = 'Public Access Project ' + Math.floor(Math.random() * 10000);
    const reportName = 'Public Access Report ' + Math.floor(Math.random() * 10000);

    // Create test data with authenticated user
    await createTimeEntryWithProject(page, projectName, '2h 30min');

    // Create and make report public
    await createReport(page, reportName, { projectFilter: projectName });
    const publicUrl = await makeReportPublic(page, reportName);

    // Create unauthenticated page
    const unauthenticatedPage = await createUnauthenticatedPage(browser);

    // Access the public report URL
    await unauthenticatedPage.goto(publicUrl);
    await unauthenticatedPage.waitForLoadState('networkidle');

    // Verify the report is accessible and displays data
    await expect(unauthenticatedPage.getByText(reportName)).toBeVisible();
    await expect(unauthenticatedPage.getByText(projectName)).toBeVisible();
    await expect(unauthenticatedPage.getByText('2h 30min')).toBeVisible();

    // Verify no authentication elements are present
    await expect(unauthenticatedPage.getByRole('button', { name: 'Login' })).not.toBeVisible();
    await expect(unauthenticatedPage.getByRole('button', { name: 'Register' })).not.toBeVisible();

    await unauthenticatedPage.close();
});

test('access public shared report with project filter shows filtered data', async ({
    page,
    browser,
}) => {
    const projectName = 'Filtered Project ' + Math.floor(Math.random() * 10000);
    const otherProjectName = 'Other Project ' + Math.floor(Math.random() * 10000);
    const reportName = 'Filtered Report ' + Math.floor(Math.random() * 10000);

    // Create test data for two projects
    await createTimeEntryWithProject(page, projectName, '1h 30min');
    await createTimeEntryWithProject(page, otherProjectName, '45min');

    // Create and make report public with project filter
    await createReport(page, reportName, { projectFilter: projectName });
    const publicUrl = await makeReportPublic(page, reportName);

    // Create unauthenticated page
    const unauthenticatedPage = await createUnauthenticatedPage(browser);

    // Access the public report URL
    await unauthenticatedPage.goto(publicUrl);
    await unauthenticatedPage.waitForLoadState('networkidle');

    // Verify only filtered project data is shown
    await expect(unauthenticatedPage.getByText(projectName)).toBeVisible();
    await expect(unauthenticatedPage.getByText(otherProjectName)).not.toBeVisible();
    await expect(unauthenticatedPage.getByText('1h 30min')).toBeVisible();
    await expect(unauthenticatedPage.getByText('45min')).not.toBeVisible();

    await unauthenticatedPage.close();
});

test('access public shared report with tag filter shows filtered data', async ({
    page,
    browser,
}) => {
    const tagName = 'PublicTag' + Math.floor(Math.random() * 10000);
    const otherTagName = 'PrivateTag' + Math.floor(Math.random() * 10000);
    const reportName = 'Tag Filtered Report ' + Math.floor(Math.random() * 10000);

    // Create test data for two tags
    await createTimeEntryWithTag(page, tagName, '2h');
    await createTimeEntryWithTag(page, otherTagName, '1h');

    // Create and make report public with tag filter
    await createReport(page, reportName, { tagFilter: tagName });
    const publicUrl = await makeReportPublic(page, reportName);

    // Create unauthenticated page
    const unauthenticatedPage = await createUnauthenticatedPage(browser);

    // Access the public report URL
    await unauthenticatedPage.goto(publicUrl);
    await unauthenticatedPage.waitForLoadState('networkidle');

    // Verify only filtered tag data is shown
    await expect(unauthenticatedPage.getByText(tagName)).toBeVisible();
    await expect(unauthenticatedPage.getByText(otherTagName)).not.toBeVisible();
    await expect(unauthenticatedPage.getByText('2h 00min')).toBeVisible();
    await expect(unauthenticatedPage.getByText('1h 00min')).not.toBeVisible();

    await unauthenticatedPage.close();
});

test('access public shared report with billable filter shows filtered data', async ({
    page,
    browser,
}) => {
    const reportName = 'Billable Filtered Report ' + Math.floor(Math.random() * 10000);

    // Create test data for billable and non-billable entries
    await createTimeEntryWithBillableStatus(page, true, '3h');
    await createTimeEntryWithBillableStatus(page, false, '1h 30min');

    // Create and make report public with billable filter
    await createReport(page, reportName, { billableFilter: 'billable' });
    const publicUrl = await makeReportPublic(page, reportName);

    // Create unauthenticated page
    const unauthenticatedPage = await createUnauthenticatedPage(browser);

    // Access the public report URL
    await unauthenticatedPage.goto(publicUrl);
    await unauthenticatedPage.waitForLoadState('networkidle');

    // Verify only billable data is shown
    await expect(unauthenticatedPage.getByText('3h 00min')).toBeVisible();
    await expect(unauthenticatedPage.getByText('1h 30min')).not.toBeVisible();

    await unauthenticatedPage.close();
});

test('access public shared report with custom time range shows filtered data', async ({
    page,
    browser,
}) => {
    const projectName = 'TimeRange Project ' + Math.floor(Math.random() * 10000);
    const reportName = 'TimeRange Report ' + Math.floor(Math.random() * 10000);

    // Create test data
    await createTimeEntryWithProject(page, projectName, '2h 15min');

    // Create and make report public with custom time range
    const startDate = new Date();
    startDate.setDate(startDate.getDate() - 7);
    const endDate = new Date();

    await createReport(page, reportName, {
        projectFilter: projectName,
        timeRange: {
            start: startDate.toISOString().split('T')[0],
            end: endDate.toISOString().split('T')[0],
        },
    });
    const publicUrl = await makeReportPublic(page, reportName);

    // Create unauthenticated page
    const unauthenticatedPage = await createUnauthenticatedPage(browser);

    // Access the public report URL
    await unauthenticatedPage.goto(publicUrl);
    await unauthenticatedPage.waitForLoadState('networkidle');

    // Verify the data is shown within the time range
    await expect(unauthenticatedPage.getByText(projectName)).toBeVisible();
    await expect(unauthenticatedPage.getByText('2h 15min')).toBeVisible();

    await unauthenticatedPage.close();
});

test('access public shared report with multiple filters shows correctly filtered data', async ({
    page,
    browser,
}) => {
    const projectName = 'MultiFilter Project ' + Math.floor(Math.random() * 10000);
    const tagName = 'MultiTag' + Math.floor(Math.random() * 10000);
    const reportName = 'MultiFilter Report ' + Math.floor(Math.random() * 10000);

    // Create test data
    await createTimeEntryWithProject(page, projectName, '1h');

    // Create a time entry with project, tag, and billable status
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

    await page.locator('[role="dialog"] input[name="Duration"]').fill('2h 30min');
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');
    await page.getByRole('button', { name: 'Create Time Entry' }).click();

    // Create and make report public with multiple filters
    await createReport(page, reportName, {
        projectFilter: projectName,
        tagFilter: tagName,
        billableFilter: 'billable',
    });
    const publicUrl = await makeReportPublic(page, reportName);

    // Create unauthenticated page
    const unauthenticatedPage = await createUnauthenticatedPage(browser);

    // Access the public report URL
    await unauthenticatedPage.goto(publicUrl);
    await unauthenticatedPage.waitForLoadState('networkidle');

    // Verify the filtered data is shown
    await expect(unauthenticatedPage.getByText(projectName)).toBeVisible();
    await expect(unauthenticatedPage.getByText(tagName)).toBeVisible();
    await expect(unauthenticatedPage.getByText('2h 30min')).toBeVisible();

    await unauthenticatedPage.close();
});

test('cannot access private shared report without authentication', async ({ page, browser }) => {
    const projectName = 'Private Project ' + Math.floor(Math.random() * 10000);
    const reportName = 'Private Report ' + Math.floor(Math.random() * 10000);

    // Create test data
    await createTimeEntryWithProject(page, projectName, '1h');

    // Create report but don't make it public
    await createReport(page, reportName, { projectFilter: projectName });

    // Try to access the shared reports page without authentication
    const unauthenticatedPage = await createUnauthenticatedPage(browser);
    await unauthenticatedPage.goto(PLAYWRIGHT_BASE_URL + '/reporting/shared');

    // Should redirect to login or show unauthorized
    await expect(unauthenticatedPage.getByRole('button', { name: 'Login' })).toBeVisible();

    await unauthenticatedPage.close();
});

test('cannot access public shared report with invalid share secret', async ({ page, browser }) => {
    const projectName = 'Invalid Secret Project ' + Math.floor(Math.random() * 10000);
    const reportName = 'Invalid Secret Report ' + Math.floor(Math.random() * 10000);

    // Create test data
    await createTimeEntryWithProject(page, projectName, '1h');

    // Create and make report public
    await createReport(page, reportName, { projectFilter: projectName });
    await makeReportPublic(page, reportName);

    // Create unauthenticated page
    const unauthenticatedPage = await createUnauthenticatedPage(browser);

    // Try to access with invalid share secret
    const invalidUrl = PLAYWRIGHT_BASE_URL + '/shared-report#invalid-secret-123';
    await unauthenticatedPage.goto(invalidUrl);

    // Should show error or not found
    await expect(unauthenticatedPage.getByText('Report not found')).toBeVisible();

    await unauthenticatedPage.close();
});

test('public shared report displays charts and visualizations', async ({ page, browser }) => {
    const projectName = 'Chart Project ' + Math.floor(Math.random() * 10000);
    const reportName = 'Chart Report ' + Math.floor(Math.random() * 10000);

    // Create test data
    await createTimeEntryWithProject(page, projectName, '4h');

    // Create and make report public
    await createReport(page, reportName, { projectFilter: projectName });
    const publicUrl = await makeReportPublic(page, reportName);

    // Create unauthenticated page
    const unauthenticatedPage = await createUnauthenticatedPage(browser);

    // Access the public report URL
    await unauthenticatedPage.goto(publicUrl);
    await unauthenticatedPage.waitForLoadState('networkidle');

    // Verify charts are displayed
    await expect(unauthenticatedPage.locator('canvas')).toBeVisible();

    // Verify summary statistics
    await expect(unauthenticatedPage.getByText('Total Time')).toBeVisible();
    await expect(unauthenticatedPage.getByText('4h 00min')).toBeVisible();

    await unauthenticatedPage.close();
});

test('public shared report shows correct report metadata', async ({ page, browser }) => {
    const projectName = 'Metadata Project ' + Math.floor(Math.random() * 10000);
    const reportName = 'Metadata Report ' + Math.floor(Math.random() * 10000);
    const description = 'This is a public report showing project data';

    // Create test data
    await createTimeEntryWithProject(page, projectName, '1h 45min');

    // Create report
    await createReport(page, reportName, { projectFilter: projectName });

    // Add description and make public
    await goToSharedReports(page);
    await page.waitForLoadState('networkidle');

    const reportRow = page.locator('tr').filter({ hasText: reportName });
    await reportRow.getByRole('button', { name: 'Edit' }).click();
    await page.getByLabel('Description').fill(description);
    await page.getByRole('switch', { name: 'Make report public' }).click();
    await page.waitForResponse(
        (response) => response.url().includes('/reports/') && response.status() === 200
    );
    await page.getByRole('button', { name: 'Save' }).click();
    await page.waitForLoadState('networkidle');

    // Get public URL
    const copyButton = reportRow.getByRole('button', { name: 'Copy URL' });
    await copyButton.click();
    const publicUrl = await page.evaluate(() => navigator.clipboard.readText());

    // Create unauthenticated page
    const unauthenticatedPage = await createUnauthenticatedPage(browser);

    // Access the public report URL
    await unauthenticatedPage.goto(publicUrl);
    await unauthenticatedPage.waitForLoadState('networkidle');

    // Verify report metadata
    await expect(unauthenticatedPage.getByText(reportName)).toBeVisible();
    await expect(unauthenticatedPage.getByText(description)).toBeVisible();

    await unauthenticatedPage.close();
});
