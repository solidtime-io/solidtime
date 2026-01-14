import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';

async function goToTimeOverview(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/time');
}

async function goToReporting(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/reporting');
}

async function goToReportingDetailed(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/reporting/detailed');
}

async function createTimeEntryWithProject(page: Page, projectName: string, duration: string) {
    // First create the project through the Projects page
    await page.goto(PLAYWRIGHT_BASE_URL + '/projects');
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(projectName);
    await page.getByRole('dialog').getByRole('button', { name: 'Create Project' }).click();

    // Wait for the project to be created and visible in the list
    await page.getByText(projectName).waitFor({ state: 'visible' });

    // Then create the time entry
    await goToTimeOverview(page);

    // Open the dropdown menu and click "Manual time entry"
    await page.getByRole('button', { name: 'Time entry actions' }).click();
    await page.getByRole('menuitem', { name: 'Manual time entry' }).click();

    // Fill in the time entry details
    await page
        .getByRole('dialog')
        .getByRole('textbox', { name: 'Description' })
        .fill(`Time entry for ${projectName}`);

    await page.getByRole('button', { name: 'No Project' }).click();
    await page.getByText(projectName).click();

    // Set duration
    await page.locator('[role="dialog"] input[name="Duration"]').fill(duration);
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');

    // Submit the time entry
    await Promise.all([
        page.getByRole('button', { name: 'Create Time Entry' }).click(),
        page.waitForResponse(
            (response) => response.url().includes('/time-entries') && response.status() === 201
        ),
    ]);
}

async function createTimeEntryWithTag(page: Page, tagName: string, duration: string) {
    await goToTimeOverview(page);

    // Open the dropdown menu and click "Manual time entry"
    await page.getByRole('button', { name: 'Time entry actions' }).click();
    await page.getByRole('menuitem', { name: 'Manual time entry' }).click();

    // Fill in the time entry details
    await page
        .getByRole('dialog')
        .getByRole('textbox', { name: 'Description' })
        .fill(`Time entry with tag ${tagName}`);

    // Add tag
    await page.getByRole('button', { name: 'Tags' }).click();
    await page.getByText('Create new tag').click();
    await page.getByPlaceholder('Tag Name').fill(tagName);
    await page.getByRole('button', { name: 'Create Tag' }).click();
    await page.waitForLoadState('networkidle');

    // Set duration
    await page.locator('[role="dialog"] input[name="Duration"]').fill(duration);
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');

    // Submit the time entry
    await page.getByRole('button', { name: 'Create Time Entry' }).click();
}

async function createTimeEntryWithBillableStatus(
    page: Page,
    isBillable: boolean,
    duration: string
) {
    await goToTimeOverview(page);

    // Open the dropdown menu and click "Manual time entry"
    await page.getByRole('button', { name: 'Time entry actions' }).click();
    await page.getByRole('menuitem', { name: 'Manual time entry' }).click();

    // Fill in the time entry details
    await page
        .getByRole('dialog')
        .getByRole('textbox', { name: 'Description' })
        .fill(`Time entry ${isBillable ? 'billable' : 'non-billable'}`);

    // Set billable status
    await page.getByRole('button', { name: 'Non-Billable' }).click();
    if (!isBillable) {
        await page.getByRole('option', { name: 'Non Billable', exact: true }).click();
    } else {
        await page.getByRole('option', { name: 'Billable', exact: true }).click();
    }

    // Set duration
    await page.locator('[role="dialog"] input[name="Duration"]').fill(duration);
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');

    // Submit the time entry
    await page.getByRole('button', { name: 'Create Time Entry' }).click();
}

test('test that project filtering works in reporting', async ({ page }) => {
    const project1 = 'Test Project 1 ' + Math.floor(Math.random() * 10000);
    const project2 = 'Test Project 2 ' + Math.floor(Math.random() * 10000);

    // Create time entries for both projects
    await createTimeEntryWithProject(page, project1, '1h');
    await createTimeEntryWithProject(page, project2, '2h');

    // Go to reporting and filter by project1
    await goToReporting(page);
    await page.getByRole('button', { name: 'Projects' }).first().click();
    await page.getByRole('option').filter({ hasText: project1 }).click();

    await Promise.all([
        // escape
        page.keyboard.press('Escape'),
        // wait for API request to finish
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/aggregate') && response.status() === 200
        ),
    ]);
    await page.waitForLoadState('networkidle');

    // Verify only project1 time entries are shown
    await expect(page.getByTestId('reporting_view').getByText(project1)).toBeVisible();
    await expect(page.getByTestId('reporting_view').getByText(project2)).not.toBeVisible();
});

test('test that tag filtering works in reporting', async ({ page }) => {
    const tag1 = 'Test Tag 1 ' + Math.floor(Math.random() * 10000);
    const tag2 = 'Test Tag 2 ' + Math.floor(Math.random() * 10000);

    // Create time entries with different tags
    await createTimeEntryWithTag(page, tag1, '1h');
    await createTimeEntryWithTag(page, tag2, '2h');

    // Go to reporting and filter by tag1
    await goToReporting(page);
    // wait for all requests to finish
    await page.waitForLoadState('networkidle');

    await page.getByRole('button', { name: 'Tags' }).click();
    await page.getByText(tag1).click();

    await Promise.all([
        // escape
        page.keyboard.press('Escape'),
        // wait for API request to finish
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/aggregate') && response.status() === 200
        ),
    ]);

    // Verify only time entries with tag1 are shown
    await expect(page.getByTestId('reporting_view').getByText('1h 00min').first()).toBeVisible();
});

test('test that billable status filtering works in reporting', async ({ page }) => {
    // Create billable and non-billable time entries
    await createTimeEntryWithBillableStatus(page, true, '1h');
    await createTimeEntryWithBillableStatus(page, false, '2h');

    // Go to reporting and filter by billable
    await goToReporting(page);

    await page.getByRole('button', { name: 'Billable' }).click();
    await page.getByRole('option', { name: 'Billable', exact: true }).click();

    await Promise.all([
        // escape
        page.keyboard.press('Escape'),
        // wait for API request to finish
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/aggregate') && response.status() === 200
        ),
    ]);
    await page.waitForLoadState('networkidle');

    await expect(page.getByTestId('reporting_view').getByText('1h 00min').first()).toBeVisible();
});

test('test that detailed view shows time entries correctly', async ({ page }) => {
    const projectName = 'Detailed View Project ' + Math.floor(Math.random() * 10000);

    // Create a time entry
    await createTimeEntryWithProject(page, projectName, '1h');

    // Go to detailed reporting view
    await goToReportingDetailed(page);

    // Verify the time entry is shown with all details
    await expect(page.getByText(projectName, { exact: true })).toBeVisible();
    await expect(page.locator('input[name="Duration"]')).toHaveValue('1h 00min');
    await expect(page.getByText('Time entry for ' + projectName, { exact: true })).toBeVisible();
});

test('test that updating duration in detailed view works correctly', async ({ page }) => {
    const projectName = 'Duration Update Project ' + Math.floor(Math.random() * 10000);
    const initialDuration = '1h';
    const updatedDuration = '2h 30min';

    // Create a time entry with initial duration
    await createTimeEntryWithProject(page, projectName, initialDuration);

    // Go to detailed reporting view
    await goToReportingDetailed(page);

    // Find and update the duration
    const durationInput = page.locator('input[name="Duration"]').first();
    await durationInput.click();
    await durationInput.fill(updatedDuration);
    await durationInput.press('Enter');

    // Wait for the update to be processed
    await page.waitForLoadState('networkidle');

    // Verify the new duration is displayed
    await expect(durationInput).toHaveValue(updatedDuration);
});

// TODO: test that date range filtering works in reporting
