import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import { expect } from '@playwright/test';
import type { Locator, Page } from '@playwright/test';
import {
    assertThatTimerHasStarted,
    assertThatTimerIsStopped,
    newTimeEntryResponse,
    startOrStopTimerWithButton,
    stoppedTimeEntryResponse,
} from './utils/currentTimeEntry';
import { createProject, createBillableProject, createBareTimeEntry } from './utils/reporting';

// Date picker button name patterns for different date formats
// Matches: "Pick a date", "YYYY-MM-DD", "DD/MM/YYYY", "DD.MM.YYYY", "MM/DD/YYYY", "DD-MM-YYYY", "MM-DD-YYYY"
const DATE_PICKER_BUTTON_PATTERN =
    /^Pick a date$|^\d{4}-\d{2}-\d{2}$|^\d{2}\/\d{2}\/\d{4}$|^\d{2}\.\d{2}\.\d{4}$/;
// Same pattern but without "Pick a date" - for when we expect an actual date to be displayed
const DATE_DISPLAY_PATTERN = /^\d{4}-\d{2}-\d{2}$|^\d{2}\/\d{2}\/\d{4}$|^\d{2}\.\d{2}\.\d{4}$/;

/**
 * Extracts day of month from an ISO timestamp string
 */
function getDayFromTimestamp(timestamp: string): number {
    return new Date(timestamp).getUTCDate();
}

/**
 * Extracts month (1-indexed) from an ISO timestamp string
 */
function getMonthFromTimestamp(timestamp: string): number {
    return new Date(timestamp).getUTCMonth() + 1;
}

async function goToTimeOverview(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/time');
}

async function goToOrganizationSettings(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
    await page.locator('[data-testid="organization_switcher"]:visible').click();
    await page.getByText('Organization Settings').click();
}

async function createEmptyTimeEntry(page: Page) {
    await Promise.all([
        newTimeEntryResponse(page),
        startOrStopTimerWithButton(page),
        assertThatTimerHasStarted(page),
    ]);
    await page.waitForTimeout(1500);
    await Promise.all([
        stoppedTimeEntryResponse(page),
        startOrStopTimerWithButton(page),
        assertThatTimerIsStopped(page),
        page.waitForResponse(
            (response) => response.url().includes('/time-entries') && response.status() === 200
        ),
    ]);
}

test('test that starting and stopping an empty time entry shows a new time entry in the overview', async ({
    page,
}) => {
    await Promise.all([
        goToTimeOverview(page),
        page.waitForResponse(
            (response) => response.url().includes('/time-entries') && response.status() === 200
        ),
    ]);
    await page.waitForTimeout(100);

    // check that there are not testid time_entry_row elements on the page
    const timeEntryRows = page.locator('[data-testid="time_entry_row"]');
    const initialTimeEntryCount = await timeEntryRows.count();
    await createEmptyTimeEntry(page);

    await expect(timeEntryRows).toHaveCount(initialTimeEntryCount + 1);
});

// Test that description update works

async function assertThatTimeEntryRowIsStopped(newTimeEntry: Locator) {
    await expect(newTimeEntry.getByTestId('timer_button')).toHaveClass(/bg-quaternary/);
}

test('test that updating a description of a time entry in the overview works on blur', async ({
    page,
}) => {
    await goToTimeOverview(page);
    const timeEntryRows = page.locator('[data-testid="time_entry_row"]');
    await createEmptyTimeEntry(page);
    const newTimeEntry = timeEntryRows.first();
    await assertThatTimeEntryRowIsStopped(newTimeEntry);

    const newDescription = Math.floor(Math.random() * 1000000).toString();
    const descriptionElement = newTimeEntry.getByTestId('time_entry_description');
    await descriptionElement.fill(newDescription);
    await Promise.all([
        descriptionElement.press('Tab'),
        page.waitForResponse(async (response) => {
            return (
                response.status() === 200 &&
                (await response.headerValue('Content-Type')) === 'application/json' &&
                (await response.json()).data.id !== null &&
                (await response.json()).data.start !== null &&
                (await response.json()).data.end !== null &&
                (await response.json()).data.project_id === null &&
                (await response.json()).data.description == newDescription &&
                (await response.json()).data.task_id === null &&
                (await response.json()).data.duration !== null &&
                (await response.json()).data.user_id !== null &&
                JSON.stringify((await response.json()).data.tags) === JSON.stringify([])
            );
        }),
    ]);
});

test('test that updating a description of a time entry in the overview works on enter', async ({
    page,
}) => {
    await goToTimeOverview(page);
    const timeEntryRows = page.locator('[data-testid="time_entry_row"]');
    await createEmptyTimeEntry(page);

    const newTimeEntry = timeEntryRows.first();
    await assertThatTimeEntryRowIsStopped(newTimeEntry);
    const newDescription = Math.floor(Math.random() * 1000000).toString();
    const descriptionElement = newTimeEntry.getByTestId('time_entry_description');
    await descriptionElement.fill(newDescription);
    await Promise.all([
        descriptionElement.press('Enter'),
        page.waitForResponse(async (response) => {
            return (
                response.status() === 200 &&
                (await response.headerValue('Content-Type')) === 'application/json' &&
                (await response.json()).data.id !== null &&
                (await response.json()).data.start !== null &&
                (await response.json()).data.end !== null &&
                (await response.json()).data.project_id === null &&
                (await response.json()).data.description == newDescription &&
                (await response.json()).data.task_id === null &&
                (await response.json()).data.duration !== null &&
                (await response.json()).data.user_id !== null &&
                JSON.stringify((await response.json()).data.tags) === JSON.stringify([])
            );
        }),
    ]);
});

test('test that adding a new tag to an existing time entry works', async ({ page }) => {
    await goToTimeOverview(page);
    const timeEntryRows = page.locator('[data-testid="time_entry_row"]');
    await createEmptyTimeEntry(page);

    const newTimeEntry = timeEntryRows.first();
    await assertThatTimeEntryRowIsStopped(newTimeEntry);
    const newTagName = Math.floor(Math.random() * 1000000).toString();

    await newTimeEntry.getByTestId('time_entry_tag_dropdown').click();
    await page.getByText('Create new tag').click();
    await page.getByPlaceholder('Tag Name').fill(newTagName);

    const [tagReponse] = await Promise.all([
        page.waitForResponse(async (response) => {
            return (
                response.status() === 201 &&
                (await response.headerValue('Content-Type')) === 'application/json' &&
                (await response.json()).data.name === newTagName
            );
        }),
        page.getByRole('button', { name: 'Create Tag' }).click(),
    ]);

    await page.waitForResponse(async (response) => {
        return (
            response.status() === 200 &&
            (await response.headerValue('Content-Type')) === 'application/json' &&
            (await response.json()).data.id !== null &&
            (await response.json()).data.start !== null &&
            (await response.json()).data.end !== null &&
            JSON.stringify((await response.json()).data.tags) ===
                JSON.stringify([(await tagReponse.json()).data.id])
        );
    });

    await expect(newTimeEntry.getByText(newTagName)).toBeVisible();
});

// Test that Start / End Time Update Works
test('test that updating a the start of an existing time entry in the overview works on enter', async ({
    page,
}) => {
    await goToTimeOverview(page);
    const timeEntryRows = page.locator('[data-testid="time_entry_row"]');
    await createEmptyTimeEntry(page);

    const newTimeEntry = timeEntryRows.first();
    await assertThatTimeEntryRowIsStopped(newTimeEntry);
    await page.waitForTimeout(1500);
    const timeEntryRangeElement = newTimeEntry.getByTestId('time_entry_range_selector');
    await timeEntryRangeElement.click();
    await page.getByTestId('time_entry_range_start').first().fill('1');
    await Promise.all([
        page.waitForResponse(async (response) => {
            return (
                response.status() === 200 &&
                (await response.headerValue('Content-Type')) === 'application/json' &&
                (await response.json()).data.id !== null &&
                // TODO! Actually check the value
                (await response.json()).data.start !== null &&
                (await response.json()).data.end !== null
            );
        }),
        page.getByTestId('time_entry_range_end').press('Enter'),
    ]);
});

test('test that updating a the duration in the overview works on blur', async ({ page }) => {
    await goToTimeOverview(page);
    const timeEntryRows = page.locator('[data-testid="time_entry_row"]');
    await createEmptyTimeEntry(page);

    const newTimeEntry = timeEntryRows.first();
    await assertThatTimeEntryRowIsStopped(newTimeEntry);
    await page.waitForTimeout(1500);
    const timeEntryDurationInput = newTimeEntry.locator('input[name="Duration"]');
    await timeEntryDurationInput.fill('20min');

    await Promise.all([
        page.waitForResponse(async (response) => {
            return (
                response.status() === 200 &&
                (await response.headerValue('Content-Type')) === 'application/json' &&
                (await response.json()).data.id !== null &&
                // TODO! Actually check the value
                (await response.json()).data.start !== null &&
                (await response.json()).data.end !== null
            );
        }),
        timeEntryDurationInput.press('Tab'),
    ]);

    await expect(timeEntryDurationInput).toHaveValue('0h 20min');
});

// Test that start stop button stops running timer
test('test that starting a time entry from the overview works', async ({ page }) => {
    await goToTimeOverview(page);
    const timeEntryRows = page.locator('[data-testid="time_entry_row"]');
    await createEmptyTimeEntry(page);

    const newTimeEntry = timeEntryRows.first();
    const startButton = newTimeEntry.getByTestId('timer_button');
    await expect(startButton).toHaveClass(/bg-quaternary/);

    await Promise.all([
        page.waitForResponse(async (response) => {
            return (
                response.status() === 200 &&
                (await response.headerValue('Content-Type')) === 'application/json' &&
                (await response.json()).data.id !== null &&
                (await response.json()).data.start !== null &&
                (await response.json()).data.end !== null
            );
        }),
        startButton.click(),
    ]);

    await assertThatTimerHasStarted(page);

    await page.waitForTimeout(1500);
    await Promise.all([
        page.waitForResponse(async (response) => {
            return (
                response.status() === 200 &&
                (await response.headerValue('Content-Type')) === 'application/json' &&
                (await response.json()).data.id !== null &&
                (await response.json()).data.start !== null &&
                (await response.json()).data.end !== null
            );
        }),
        startOrStopTimerWithButton(page),
        assertThatTimerIsStopped(page),
    ]);
});

test('test that deleting a time entry from the overview works', async ({ page }) => {
    await goToTimeOverview(page);
    const timeEntryRows = page.locator('[data-testid="time_entry_row"]');
    await createEmptyTimeEntry(page);
    await expect(timeEntryRows).toHaveCount(1);

    const newTimeEntry = timeEntryRows.first();
    const actionsDropdown = newTimeEntry
        .getByRole('button', { name: 'Actions for the time entry' })
        .first();
    await actionsDropdown.click();
    const deleteButton = page.getByText('Delete');
    await deleteButton.click();
    await expect(timeEntryRows).toHaveCount(0);
});

test.skip('test that load more works when the end of page is reached', async ({ page }) => {
    // this test is flaky when you do not need to scroll
    await Promise.all([
        goToTimeOverview(page),
        page.waitForResponse(
            (response) => response.url().includes('/time-entries') && response.status() === 200
        ),
    ]);

    await page.waitForTimeout(200);
    await Promise.all([
        page.evaluate(() => window.scrollTo(0, document.body.scrollHeight)),
        page.waitForResponse(async (response) => {
            return (
                response.status() === 200 &&
                response.url().includes('before') &&
                (await response.headerValue('Content-Type')) === 'application/json' &&
                JSON.stringify((await response.json()).data) === JSON.stringify([])
            );
        }),
    ]);

    // assert that "All time entries are loaded!" is visible on page
    await expect(page.locator('body')).toHaveText(/All time entries are loaded!/);
});

// TODO: Test that updating the time entry start / end times works while it is running

// TODO: Test for project update

// TODO: Test for resume button click works with project / task

// TODO: Test that time entries are loaded at the end of the page

// TODO: Test Grouped time entries by description/project

// Date Update Tests

test('test that updating the start date of a time entry via the edit modal works', async ({
    page,
}) => {
    await createBareTimeEntry(page, 'Date edit test', '1h');
    await goToTimeOverview(page);

    const timeEntryRows = page.locator('[data-testid="time_entry_row"]');
    const newTimeEntry = timeEntryRows.first();

    // Open edit modal via the actions dropdown
    const actionsDropdown = newTimeEntry
        .getByRole('button', { name: 'Actions for the time entry' })
        .first();
    await actionsDropdown.click();
    await page.getByTestId('time_entry_edit').click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // Click the start date picker (first date picker button in the Start section)
    const startDatePicker = page
        .getByRole('dialog')
        .getByRole('button', { name: DATE_PICKER_BUTTON_PATTERN })
        .first();
    await startDatePicker.click();

    // Navigate to the previous month and select the 15th
    await page.getByRole('button', { name: /Previous/i }).click();
    await page.getByRole('gridcell').filter({ hasText: /^15$/ }).first().click();

    // Get current month to calculate expected month after going to previous
    const now = new Date();
    const expectedMonth = now.getMonth() === 0 ? 12 : now.getMonth(); // Previous month (1-indexed)

    // Submit the update and verify the response has correct date
    const [updateResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page.getByRole('button', { name: 'Update Time Entry' }).click(),
    ]);
    const updateBody = await updateResponse.json();
    expect(updateBody.data.start).toBeTruthy();
    expect(updateBody.data.end).toBeTruthy();
    // Verify the day was changed to 15th
    expect(getDayFromTimestamp(updateBody.data.start)).toBe(15);
    // Verify the month is the previous month
    expect(getMonthFromTimestamp(updateBody.data.start)).toBe(expectedMonth);
});

test('test that setting a date in the create modal works', async ({ page }) => {
    await goToTimeOverview(page);

    // Get today's date to compare later
    const today = new Date();

    // Open create modal
    await page.getByRole('button', { name: 'Time entry actions' }).click();
    await page.getByRole('menuitem', { name: 'Manual time entry' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // Set description
    await page
        .getByRole('dialog')
        .getByRole('textbox', { name: 'Description' })
        .fill('Date picker test entry');

    // Set duration first (to ensure the form is valid)
    await page.locator('[role="dialog"] input[name="Duration"]').fill('1h');
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');

    // Click the start date picker
    const startDatePicker = page
        .getByRole('dialog')
        .getByRole('button', { name: DATE_PICKER_BUTTON_PATTERN })
        .first();
    await startDatePicker.click();

    // Wait for calendar to appear
    const calendarGrid = page.getByRole('grid');
    await expect(calendarGrid).toBeVisible({ timeout: 5000 });

    // Navigate to previous month and select the 15th (a day that's always in the middle of the month)
    await page.getByRole('button', { name: /Previous/i }).click();
    await page.getByRole('gridcell', { name: '15' }).getByRole('button').click();

    // Wait for calendar to close
    await expect(calendarGrid).not.toBeVisible();

    // Get current month to calculate expected month after going to previous
    const expectedMonth = today.getMonth() === 0 ? 12 : today.getMonth(); // Previous month (1-indexed)

    // Submit and verify creation succeeds with correct date
    const [createResponse] = await Promise.all([
        page.waitForResponse(
            (response) => response.url().includes('/time-entries') && response.status() === 201
        ),
        page.getByRole('button', { name: 'Create Time Entry' }).click(),
    ]);
    const createBody = await createResponse.json();
    expect(createBody.data.start).toBeTruthy();
    // Verify the day was set to 15th
    expect(getDayFromTimestamp(createBody.data.start)).toBe(15);
    // Verify the month is the previous month
    expect(getMonthFromTimestamp(createBody.data.start)).toBe(expectedMonth);
});

test('test that updating the date via the time entry row range selector works', async ({
    page,
}) => {
    await createBareTimeEntry(page, 'Date range test', '1h');
    await goToTimeOverview(page);

    const timeEntryRows = page.locator('[data-testid="time_entry_row"]');
    const newTimeEntry = timeEntryRows.first();
    await expect(newTimeEntry).toBeVisible();

    // Open the time range popover
    const timeEntryRangeElement = newTimeEntry.getByTestId('time_entry_range_selector');
    await timeEntryRangeElement.click();

    // Verify the range selector dropdown is open
    const rangeStart = page.getByTestId('time_entry_range_start');
    await expect(rangeStart).toBeVisible();

    // Click the start date picker button within the range selector
    const startDatePicker = page.getByRole('button', { name: DATE_DISPLAY_PATTERN }).first();
    await expect(startDatePicker).toBeVisible();
    await startDatePicker.click();

    // Wait for the calendar to appear and select a day
    const calendarGrid = page.getByRole('grid');
    await expect(calendarGrid).toBeVisible({ timeout: 5000 });

    // Navigate to previous month and select the 5th
    await page.getByRole('button', { name: /Previous/i }).click();
    await page.getByRole('gridcell').filter({ hasText: /^5$/ }).first().click();

    // Get current month to calculate expected month after going to previous
    const now = new Date();
    const expectedMonth = now.getMonth() === 0 ? 12 : now.getMonth(); // Previous month (1-indexed)

    // Verify the time entry update API call succeeds with correct date
    const updateResponse = await page.waitForResponse(async (response) => {
        return (
            response.status() === 200 &&
            response.request().method() === 'PUT' &&
            (await response.headerValue('Content-Type')) === 'application/json'
        );
    });
    const updateBody = await updateResponse.json();
    expect(updateBody.data.start).toBeTruthy();
    // Verify the day was changed to 5th
    expect(getDayFromTimestamp(updateBody.data.start)).toBe(5);
    // Verify the month is the previous month
    expect(getMonthFromTimestamp(updateBody.data.start)).toBe(expectedMonth);
});

test('test that updating the end date via the time entry row range selector works', async ({
    page,
}) => {
    await createBareTimeEntry(page, 'End date range test', '1h');
    await goToTimeOverview(page);

    const timeEntryRows = page.locator('[data-testid="time_entry_row"]');
    const newTimeEntry = timeEntryRows.first();
    await expect(newTimeEntry).toBeVisible();

    // Open the time range popover
    const timeEntryRangeElement = newTimeEntry.getByTestId('time_entry_range_selector');
    await timeEntryRangeElement.click();

    // Verify the range selector dropdown is open
    const rangeEnd = page.getByTestId('time_entry_range_end');
    await expect(rangeEnd).toBeVisible();

    // Click the end date picker button (second date picker)
    const datePickers = page.getByRole('button', { name: DATE_DISPLAY_PATTERN });
    const endDatePicker = datePickers.nth(1);
    await expect(endDatePicker).toBeVisible();
    await endDatePicker.click();

    // Wait for the calendar to appear
    const calendarGrid = page.getByRole('grid');
    await expect(calendarGrid).toBeVisible({ timeout: 5000 });

    // Navigate to next month and select the 20th (to ensure end > start)
    await page.getByRole('button', { name: /Next/i }).click();
    await page.getByRole('gridcell').filter({ hasText: /^20$/ }).first().click();

    // Get current month to calculate expected month after going to next
    const now = new Date();
    const expectedMonth = now.getMonth() === 11 ? 1 : now.getMonth() + 2; // Next month (1-indexed)

    // Verify the time entry update API call succeeds with correct date
    const updateResponse = await page.waitForResponse(async (response) => {
        return (
            response.status() === 200 &&
            response.request().method() === 'PUT' &&
            (await response.headerValue('Content-Type')) === 'application/json'
        );
    });
    const updateBody = await updateResponse.json();
    expect(updateBody.data.end).toBeTruthy();
    // Verify the day was changed to 20th
    expect(getDayFromTimestamp(updateBody.data.end)).toBe(20);
    // Verify the month is the next month
    expect(getMonthFromTimestamp(updateBody.data.end)).toBe(expectedMonth);
});

test('test that date picker displays date in organization date format', async ({ page }) => {
    // First change the organization date format to DD/MM/YYYY
    await goToOrganizationSettings(page);
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

    // Create a time entry and open the edit modal
    await createBareTimeEntry(page, 'Date format test', '1h');
    await goToTimeOverview(page);

    const timeEntryRows = page.locator('[data-testid="time_entry_row"]');
    const newTimeEntry = timeEntryRows.first();
    await expect(newTimeEntry).toBeVisible();

    // Open edit modal
    const actionsDropdown = newTimeEntry
        .getByRole('button', { name: 'Actions for the time entry' })
        .first();
    await actionsDropdown.click();
    await page.getByTestId('time_entry_edit').click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // Verify the date picker shows the date in DD/MM/YYYY format
    const datePicker = page
        .getByRole('dialog')
        .getByRole('button', { name: /^\d{2}\/\d{2}\/\d{4}$/ })
        .first();
    await expect(datePicker).toBeVisible();
});

// TODO: Test that project can be created in the time entry row

test('test that editing billable status via the edit modal works', async ({ page }) => {
    await goToTimeOverview(page);
    await createEmptyTimeEntry(page);

    const timeEntryRows = page.locator('[data-testid="time_entry_row"]');
    const newTimeEntry = timeEntryRows.first();
    await assertThatTimeEntryRowIsStopped(newTimeEntry);

    // Open edit modal via the actions dropdown
    const actionsDropdown = newTimeEntry
        .getByRole('button', { name: 'Actions for the time entry' })
        .first();
    await actionsDropdown.click();
    await page.getByTestId('time_entry_edit').click();

    // Verify the edit dialog is visible
    await expect(page.getByRole('dialog')).toBeVisible();

    // Change billable status to Billable
    await page
        .getByRole('dialog')
        .getByRole('combobox')
        .filter({ hasText: 'Non-Billable' })
        .click();
    await page.getByRole('option', { name: 'Billable', exact: true }).click();

    // Save the time entry and verify the response has billable=true
    const [updateResponse] = await Promise.all([
        page.waitForResponse(
            (response) => response.url().includes('/time-entries') && response.status() === 200
        ),
        page.getByRole('button', { name: 'Update Time Entry' }).click(),
    ]);
    const updateBody = await updateResponse.json();
    expect(updateBody.data.billable).toBe(true);

    // Verify the dialog closed
    await expect(page.getByRole('dialog')).not.toBeVisible();

    // Re-open the edit modal and verify it now shows "Billable"
    await actionsDropdown.click();
    await page.getByTestId('time_entry_edit').click();
    await expect(page.getByRole('dialog')).toBeVisible();
    await expect(
        page.getByRole('dialog').getByRole('combobox').filter({ hasText: 'Billable' })
    ).toBeVisible();
});

test('test that mass update billable status works', async ({ page }) => {
    await goToTimeOverview(page);
    await createEmptyTimeEntry(page);

    const timeEntryRows = page.locator('[data-testid="time_entry_row"]');
    await assertThatTimeEntryRowIsStopped(timeEntryRows.first());

    // Select the time entry via the "Select All" checkbox
    await page.getByLabel('Select All').click();
    await expect(page.getByText('1 selected')).toBeVisible();

    // Open mass update modal via the Edit button in the mass action row
    await page.getByRole('button', { name: 'Edit' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // Change billable status to Billable
    await page
        .getByRole('dialog')
        .getByRole('combobox')
        .filter({ hasText: 'Set billable status' })
        .click();
    await page.getByRole('option', { name: 'Billable', exact: true }).click();

    // Submit the mass update
    const [massUpdateResponse] = await Promise.all([
        page.waitForResponse(
            (response) => response.url().includes('/time-entries') && response.status() === 200
        ),
        page.getByRole('button', { name: 'Update Time Entries' }).click(),
    ]);
    const massUpdateBody = await massUpdateResponse.json();
    expect(massUpdateBody.success.length).toBeGreaterThan(0);
    expect(massUpdateBody.error.length).toBe(0);

    // Verify dialog closes
    await expect(page.getByRole('dialog')).not.toBeVisible();

    // Verify the UI reflects the billable status by re-opening the edit modal
    const actionsDropdown = page
        .locator('[data-testid="time_entry_row"]')
        .first()
        .getByRole('button', { name: 'Actions' });
    await actionsDropdown.click();
    await page.getByTestId('time_entry_edit').click();
    await expect(page.getByRole('dialog')).toBeVisible();
    await expect(
        page.getByRole('dialog').getByRole('combobox').filter({ hasText: 'Billable' })
    ).toBeVisible();
});

test('test that setting billable status via the create modal works', async ({ page }) => {
    await goToTimeOverview(page);

    // Open the dropdown menu and click "Manual time entry"
    await page.getByRole('button', { name: 'Time entry actions' }).click();
    await page.getByRole('menuitem', { name: 'Manual time entry' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // Set description
    await page
        .getByRole('dialog')
        .getByRole('textbox', { name: 'Description' })
        .fill('Billable create test');

    // Change billable status to Billable
    await page
        .getByRole('dialog')
        .getByRole('combobox')
        .filter({ hasText: 'Non-Billable' })
        .click();
    await page.getByRole('option', { name: 'Billable', exact: true }).click();

    // Set duration
    await page.locator('[role="dialog"] input[name="Duration"]').fill('1h');
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');

    // Submit and verify the time entry was created with billable=true
    await Promise.all([
        page.getByRole('button', { name: 'Create Time Entry' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/time-entries') &&
                response.status() === 201 &&
                (await response.json()).data.billable === true
        ),
    ]);
});

/**
 * The following tests verify that changing the project on a time entry
 * updates the billable status to match the new project's is_billable setting.
 *
 * Issue: https://github.com/solidtime-io/solidtime/issues/981
 */

test('test that changing project on a time entry row from non-billable to billable updates billable status', async ({
    page,
}) => {
    const billableProjectName = 'Billable Row Project ' + Math.floor(1 + Math.random() * 10000);
    const nonBillableProjectName =
        'NonBillable Row Project ' + Math.floor(1 + Math.random() * 10000);

    await createProject(page, nonBillableProjectName);
    await createBillableProject(page, billableProjectName);
    await createBareTimeEntry(page, 'Test billable row', '1h');

    await goToTimeOverview(page);
    const timeEntryRow = page.locator('[data-testid="time_entry_row"]').first();

    // Assign the non-billable project first
    await timeEntryRow.getByRole('button', { name: 'No Project' }).click();
    await page.getByRole('option', { name: nonBillableProjectName }).click();
    await page.waitForResponse(
        (response) =>
            response.url().includes('/time-entries/') &&
            response.request().method() === 'PUT' &&
            response.status() === 200
    );

    // Now switch to the billable project
    await timeEntryRow.getByRole('button', { name: nonBillableProjectName }).click();
    await page.getByRole('option', { name: billableProjectName }).click();

    const updateResponse = await page.waitForResponse(
        (response) =>
            response.url().includes('/time-entries/') &&
            response.request().method() === 'PUT' &&
            response.status() === 200
    );
    const responseBody = await updateResponse.json();
    expect(responseBody.data.billable).toBe(true);
});

test('test that changing project on a time entry row from billable to non-billable updates billable status', async ({
    page,
}) => {
    const billableProjectName = 'Billable Row Rev Project ' + Math.floor(1 + Math.random() * 10000);
    const nonBillableProjectName =
        'NonBillable Row Rev Project ' + Math.floor(1 + Math.random() * 10000);

    await createBillableProject(page, billableProjectName);
    await createProject(page, nonBillableProjectName);
    await createBareTimeEntry(page, 'Test billable row reverse', '1h');

    await goToTimeOverview(page);
    const timeEntryRow = page.locator('[data-testid="time_entry_row"]').first();

    // Assign the billable project first
    await timeEntryRow.getByRole('button', { name: 'No Project' }).click();
    await page.getByRole('option', { name: billableProjectName }).click();
    const firstResponse = await page.waitForResponse(
        (response) =>
            response.url().includes('/time-entries/') &&
            response.request().method() === 'PUT' &&
            response.status() === 200
    );
    const firstBody = await firstResponse.json();
    expect(firstBody.data.billable).toBe(true);

    // Now switch to the non-billable project
    await timeEntryRow.getByRole('button', { name: billableProjectName }).click();
    await page.getByRole('option', { name: nonBillableProjectName }).click();

    const updateResponse = await page.waitForResponse(
        (response) =>
            response.url().includes('/time-entries/') &&
            response.request().method() === 'PUT' &&
            response.status() === 200
    );
    const responseBody = await updateResponse.json();
    expect(responseBody.data.billable).toBe(false);
});

test('test that changing project in edit modal from non-billable to billable updates billable status', async ({
    page,
}) => {
    const billableProjectName = 'Billable Modal Project ' + Math.floor(1 + Math.random() * 10000);

    await createBillableProject(page, billableProjectName);
    await createBareTimeEntry(page, 'Test billable modal', '1h');

    await goToTimeOverview(page);
    const timeEntryRow = page.locator('[data-testid="time_entry_row"]').first();

    // Open edit modal
    await timeEntryRow.getByRole('button', { name: 'Actions for the time entry' }).first().click();
    await page.getByRole('menuitem', { name: 'Edit' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // Verify initially non-billable
    await expect(
        page.getByRole('dialog').getByRole('combobox').filter({ hasText: 'Non-Billable' })
    ).toBeVisible();

    // Select the billable project
    await page.getByRole('dialog').getByRole('button', { name: 'No Project' }).click();
    await page.getByRole('option', { name: billableProjectName }).click();

    // Verify the billable dropdown updated to Billable
    await expect(
        page.getByRole('dialog').getByRole('combobox').filter({ hasText: 'Billable' })
    ).toBeVisible();

    // Save and verify
    const [updateResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page.getByRole('button', { name: 'Update Time Entry' }).click(),
    ]);
    const responseBody = await updateResponse.json();
    expect(responseBody.data.billable).toBe(true);
});

test('test that opening edit modal for a time entry with manually overridden billable status preserves that status', async ({
    page,
}) => {
    const billableProjectName = 'Billable Persist Project ' + Math.floor(1 + Math.random() * 10000);

    await createBillableProject(page, billableProjectName);
    await createBareTimeEntry(page, 'Test persist billable override', '1h');

    await goToTimeOverview(page);
    const timeEntryRow = page.locator('[data-testid="time_entry_row"]').first();

    // Open edit modal and assign the billable project
    await timeEntryRow.getByRole('button', { name: 'Actions for the time entry' }).first().click();
    await page.getByRole('menuitem', { name: 'Edit' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();

    await page.getByRole('dialog').getByRole('button', { name: 'No Project' }).click();
    await page.getByRole('option', { name: billableProjectName }).click();

    // Verify it auto-set to Billable
    await expect(
        page.getByRole('dialog').getByRole('combobox').filter({ hasText: 'Billable' })
    ).toBeVisible();

    // Now manually override billable to Non-Billable via the dropdown
    await page.getByRole('dialog').getByRole('combobox').filter({ hasText: 'Billable' }).click();
    await page.getByRole('option', { name: 'Non Billable' }).click();

    // Verify it shows Non-Billable now
    await expect(
        page.getByRole('dialog').getByRole('combobox').filter({ hasText: 'Non-Billable' })
    ).toBeVisible();

    // Save
    const [firstSaveResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page.getByRole('button', { name: 'Update Time Entry' }).click(),
    ]);
    const firstBody = await firstSaveResponse.json();
    expect(firstBody.data.billable).toBe(false);

    // Re-open the edit modal — the project_id watcher should NOT override billable back to true
    await timeEntryRow.getByRole('button', { name: 'Actions for the time entry' }).first().click();
    await page.getByRole('menuitem', { name: 'Edit' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // The billable dropdown should still show Non-Billable
    await expect(
        page.getByRole('dialog').getByRole('combobox').filter({ hasText: 'Non-Billable' })
    ).toBeVisible();

    // Save without changes and verify the response still has billable=false
    const [updateResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page.getByRole('button', { name: 'Update Time Entry' }).click(),
    ]);
    const responseBody = await updateResponse.json();
    expect(responseBody.data.billable).toBe(false);
});

test('test that changing project in edit modal from billable to non-billable updates billable status', async ({
    page,
}) => {
    const billableProjectName =
        'Billable Modal Rev Project ' + Math.floor(1 + Math.random() * 10000);
    const nonBillableProjectName =
        'NonBillable Modal Rev Project ' + Math.floor(1 + Math.random() * 10000);

    await createBillableProject(page, billableProjectName);
    await createProject(page, nonBillableProjectName);
    await createBareTimeEntry(page, 'Test billable modal reverse', '1h');

    await goToTimeOverview(page);
    const timeEntryRow = page.locator('[data-testid="time_entry_row"]').first();

    // Open edit modal
    await timeEntryRow.getByRole('button', { name: 'Actions for the time entry' }).first().click();
    await page.getByRole('menuitem', { name: 'Edit' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // First assign the billable project
    await page.getByRole('dialog').getByRole('button', { name: 'No Project' }).click();
    await page.getByRole('option', { name: billableProjectName }).click();

    // Verify billable status flipped to Billable
    await expect(
        page.getByRole('dialog').getByRole('combobox').filter({ hasText: 'Billable' })
    ).toBeVisible();

    // Now switch to the non-billable project
    await page.getByRole('dialog').getByRole('button', { name: billableProjectName }).click();
    await page.getByRole('option', { name: nonBillableProjectName }).click();

    // Verify billable status reverted to Non-Billable
    await expect(
        page.getByRole('dialog').getByRole('combobox').filter({ hasText: 'Non-Billable' })
    ).toBeVisible();

    // Save and verify
    const [updateResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page.getByRole('button', { name: 'Update Time Entry' }).click(),
    ]);
    const responseBody = await updateResponse.json();
    expect(responseBody.data.billable).toBe(false);
});
