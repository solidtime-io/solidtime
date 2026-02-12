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
import {
    createProjectViaApi,
    createBillableProjectViaApi,
    createBareTimeEntryViaApi,
    createTimeEntryViaApi,
    updateOrganizationCurrencyViaWeb,
} from './utils/api';

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
    // Wait for the timer to accumulate some duration so the stopped entry has duration > 0
    await expect(page.getByTestId('time_entry_time')).not.toHaveValue('00:00:00');
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
    // check that there are not testid time_entry_row elements on the page
    const timeEntryRows = page.locator('[data-testid="time_entry_row"]');
    const initialTimeEntryCount = await timeEntryRows.count();
    await createEmptyTimeEntry(page);

    await expect(timeEntryRows).toHaveCount(initialTimeEntryCount + 1);
});

// Test that description update works

async function assertThatTimeEntryRowIsStopped(newTimeEntry: Locator) {
    await expect(newTimeEntry.getByTestId('timer_button').first()).toHaveClass(/bg-quaternary/);
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
    const descriptionElement = newTimeEntry.getByTestId('time_entry_description').first();
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
    const descriptionElement = newTimeEntry.getByTestId('time_entry_description').first();
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

    await newTimeEntry.getByTestId('time_entry_tag_dropdown').first().click();
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

    await expect(newTimeEntry.getByText(newTagName).first()).toBeVisible();
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
    const timeEntryRangeElement = newTimeEntry.getByTestId('time_entry_range_selector');
    await expect(timeEntryRangeElement).toBeVisible();
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
    const timeEntryDurationInput = newTimeEntry.locator('input[name="Duration"]').first();
    await expect(timeEntryDurationInput).toBeEditable();
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
    const startButton = newTimeEntry.getByTestId('timer_button').first();
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

    // Wait for the timer to accumulate some duration
    await expect(page.getByTestId('time_entry_time')).not.toHaveValue('00:00:00');
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
    ]);
    await assertThatTimerIsStopped(page);
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
    ctx,
}) => {
    await createBareTimeEntryViaApi(ctx, 'Date edit test', '1h');
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
    ctx,
}) => {
    await createBareTimeEntryViaApi(ctx, 'Date range test', '1h');
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
    ctx,
}) => {
    await createBareTimeEntryViaApi(ctx, 'End date range test', '1h');
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

test('test that date picker displays date in organization date format', async ({ page, ctx }) => {
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
    await createBareTimeEntryViaApi(ctx, 'Date format test', '1h');
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

test('test that billable icon shows dollar sign for USD currency on time entry row', async ({
    page,
    ctx,
}) => {
    await updateOrganizationCurrencyViaWeb(ctx, 'USD');
    await goToTimeOverview(page);
    await createEmptyTimeEntry(page);
    const timeEntryRow = page.locator('[data-testid="time_entry_row"]').first();
    const billableButton = timeEntryRow.getByRole('button', { name: 'Non Billable' }).first();
    await expect(billableButton).toBeVisible();
    await expect(billableButton.locator('svg')).toHaveAttribute('viewBox', '0 0 8 14');
});

test('test that billable icon shows euro sign for EUR currency on time entry row', async ({
    page,
    ctx,
}) => {
    await updateOrganizationCurrencyViaWeb(ctx, 'EUR');
    await goToTimeOverview(page);
    await createEmptyTimeEntry(page);
    const timeEntryRow = page.locator('[data-testid="time_entry_row"]').first();
    const billableButton = timeEntryRow.getByRole('button', { name: 'Non Billable' }).first();
    await expect(billableButton).toBeVisible();
    await expect(billableButton.locator('svg')).toHaveAttribute('viewBox', '0 0 12 12');
});

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

test('test that resetting project selection in mass update modal does not update project', async ({
    page,
    ctx,
}) => {
    const projectName = 'Mass Update Reset Project ' + Math.floor(1 + Math.random() * 10000);
    await createProjectViaApi(ctx, { name: projectName });

    // Create a time entry with the project assigned
    await createBareTimeEntryViaApi(ctx, 'Mass update reset test', '1h');
    await goToTimeOverview(page);

    // Assign project to the time entry
    const timeEntryRow = page.locator('[data-testid="time_entry_row"]').first();
    await expect(timeEntryRow).toBeVisible();
    await timeEntryRow.getByRole('button', { name: 'No Project' }).click();
    await page.getByRole('option', { name: projectName }).click();
    await expect(timeEntryRow.getByRole('button', { name: projectName })).toBeVisible();

    // Now open mass update modal
    await page.getByLabel('Select All').click();
    await expect(page.getByText('1 selected')).toBeVisible();
    await page.getByRole('button', { name: 'Edit' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // The project dropdown should show "Select project..." (initial unset state)
    const projectDropdown = page
        .getByRole('dialog')
        .getByRole('button', { name: 'Select project...' });
    await expect(projectDropdown).toBeVisible();

    // Select the project, then click the reset (X) button
    await projectDropdown.click();
    await page.getByRole('option', { name: projectName }).click();

    // Now the dropdown shows the project name, click the X to reset
    await expect(page.getByRole('dialog').getByRole('button', { name: projectName })).toBeVisible();

    // Find and click the reset button (the X icon next to the dropdown)
    await page.getByRole('dialog').getByTestId('project_reset_button').click();

    // After reset, it should show "Select project..." again (not "No Project")
    await expect(
        page.getByRole('dialog').getByRole('button', { name: 'Select project...' })
    ).toBeVisible();

    // Submit the mass update - need to make at least one change for the API to accept it
    // Change billable status to keep it unchanged by selecting the "Keep current" option
    // Actually, we need to verify the reset behavior, so let's just change billable to trigger the request
    await page
        .getByRole('dialog')
        .getByRole('combobox')
        .filter({ hasText: 'Set billable status' })
        .click();
    await page.getByRole('option', { name: 'Billable', exact: true }).click();

    await page.getByRole('button', { name: 'Update Time Entries' }).click();

    // Wait for dialog to close
    await expect(page.getByRole('dialog')).not.toBeVisible();

    // Verify the time entry still has the original project (was not changed to "No Project")
    await expect(
        page
            .locator('[data-testid="time_entry_row"]')
            .first()
            .getByRole('button', { name: projectName })
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
    ctx,
}) => {
    const billableProjectName = 'Billable Row Project ' + Math.floor(1 + Math.random() * 10000);
    const nonBillableProjectName =
        'NonBillable Row Project ' + Math.floor(1 + Math.random() * 10000);
    await createProjectViaApi(ctx, { name: nonBillableProjectName });
    await createBillableProjectViaApi(ctx, { name: billableProjectName });
    await createBareTimeEntryViaApi(ctx, 'Test billable row', '1h');

    await goToTimeOverview(page);
    const timeEntryRow = page.locator('[data-testid="time_entry_row"]').first();

    // Assign the non-billable project first
    await timeEntryRow.getByRole('button', { name: 'No Project' }).click();
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page.getByRole('option', { name: nonBillableProjectName }).click(),
    ]);

    // Now switch to the billable project
    await timeEntryRow.getByRole('button', { name: nonBillableProjectName }).click();
    const [updateResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page.getByRole('option', { name: billableProjectName }).click(),
    ]);
    const responseBody = await updateResponse.json();
    expect(responseBody.data.billable).toBe(true);
});

test('test that changing project on a time entry row from billable to non-billable updates billable status', async ({
    page,
    ctx,
}) => {
    const billableProjectName = 'Billable Row Rev Project ' + Math.floor(1 + Math.random() * 10000);
    const nonBillableProjectName =
        'NonBillable Row Rev Project ' + Math.floor(1 + Math.random() * 10000);
    await createBillableProjectViaApi(ctx, { name: billableProjectName });
    await createProjectViaApi(ctx, { name: nonBillableProjectName });
    await createBareTimeEntryViaApi(ctx, 'Test billable row reverse', '1h');

    await goToTimeOverview(page);
    const timeEntryRow = page.locator('[data-testid="time_entry_row"]').first();

    // Assign the billable project first
    await timeEntryRow.getByRole('button', { name: 'No Project' }).click();
    const [firstResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page.getByRole('option', { name: billableProjectName }).click(),
    ]);
    const firstBody = await firstResponse.json();
    expect(firstBody.data.billable).toBe(true);

    // Now switch to the non-billable project
    await timeEntryRow.getByRole('button', { name: billableProjectName }).click();
    const [updateResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page.getByRole('option', { name: nonBillableProjectName }).click(),
    ]);
    const responseBody = await updateResponse.json();
    expect(responseBody.data.billable).toBe(false);
});

/**
 * Tests for TimeEntryCreateModal functionality
 */

test('test that natural language duration input works in create modal', async ({ page }) => {
    await goToTimeOverview(page);

    // Open the create modal
    await page.getByRole('button', { name: 'Time entry actions' }).click();
    await page.getByRole('menuitem', { name: 'Manual time entry' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // Set description
    await page
        .getByRole('dialog')
        .getByRole('textbox', { name: 'Description' })
        .fill('Duration test entry');

    // Test natural language duration input "2h 30m"
    const durationInput = page.locator('[role="dialog"] input[name="Duration"]');
    await durationInput.fill('2h 30m');
    await durationInput.press('Tab');

    // Verify the duration was parsed correctly (should show "2h 30min")
    await expect(durationInput).toHaveValue('2h 30min');

    // Submit and verify the duration in the response (2h 30m = 9000 seconds)
    const [createResponse] = await Promise.all([
        page.waitForResponse(
            (response) => response.url().includes('/time-entries') && response.status() === 201
        ),
        page.getByRole('button', { name: 'Create Time Entry' }).click(),
    ]);
    const createBody = await createResponse.json();
    expect(createBody.data.duration).toBe(9000);
});

test('test that decimal duration input works in create modal', async ({ page }) => {
    await goToTimeOverview(page);

    // Open the create modal
    await page.getByRole('button', { name: 'Time entry actions' }).click();
    await page.getByRole('menuitem', { name: 'Manual time entry' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // Set description
    await page
        .getByRole('dialog')
        .getByRole('textbox', { name: 'Description' })
        .fill('Decimal duration test');

    // Test decimal duration input "1.5h" (should be interpreted as 1.5 hours = 90 minutes)
    // Note: parse-duration library requires a unit suffix for decimal values
    const durationInput = page.locator('[role="dialog"] input[name="Duration"]');
    await durationInput.fill('1.5h');
    await durationInput.press('Tab');

    // Verify the duration was parsed correctly (should show "1h 30min")
    await expect(durationInput).toHaveValue('1h 30min');

    // Submit and verify the duration in the response (1.5h = 5400 seconds)
    const [createResponse] = await Promise.all([
        page.waitForResponse(
            (response) => response.url().includes('/time-entries') && response.status() === 201
        ),
        page.getByRole('button', { name: 'Create Time Entry' }).click(),
    ]);
    const createBody = await createResponse.json();
    expect(createBody.data.duration).toBe(5400);
});

test('test that project selection works in create modal', async ({ page, ctx }) => {
    const projectName = 'Create Modal Project ' + Math.floor(1 + Math.random() * 10000);
    await createProjectViaApi(ctx, { name: projectName });

    await goToTimeOverview(page);

    // Open the create modal
    await page.getByRole('button', { name: 'Time entry actions' }).click();
    await page.getByRole('menuitem', { name: 'Manual time entry' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // Set description
    await page
        .getByRole('dialog')
        .getByRole('textbox', { name: 'Description' })
        .fill('Project selection test');

    // Select project
    await page.getByRole('dialog').getByRole('button', { name: 'No Project' }).click();
    await page.getByRole('option', { name: projectName }).click();

    // Verify project is selected
    await expect(page.getByRole('dialog').getByRole('button', { name: projectName })).toBeVisible();

    // Set duration
    await page.locator('[role="dialog"] input[name="Duration"]').fill('1h');
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');

    // Submit and verify project_id is set in response
    const [createResponse] = await Promise.all([
        page.waitForResponse(
            (response) => response.url().includes('/time-entries') && response.status() === 201
        ),
        page.getByRole('button', { name: 'Create Time Entry' }).click(),
    ]);
    const createBody = await createResponse.json();
    expect(createBody.data.project_id).not.toBeNull();
});

test('test that tag selection works in create modal', async ({ page }) => {
    await goToTimeOverview(page);

    // Open the create modal
    await page.getByRole('button', { name: 'Time entry actions' }).click();
    await page.getByRole('menuitem', { name: 'Manual time entry' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // Set description
    await page
        .getByRole('dialog')
        .getByRole('textbox', { name: 'Description' })
        .fill('Tag selection test');

    // Open tags dropdown
    await page.getByRole('dialog').getByRole('button', { name: 'Tags' }).click();

    // Create a new tag
    const tagName = 'TestTag' + Math.floor(1 + Math.random() * 10000);
    await page.getByText('Create new tag').click();
    await page.getByPlaceholder('Tag Name').fill(tagName);
    const [tagResponse] = await Promise.all([
        page.waitForResponse(
            (response) => response.url().includes('/tags') && response.status() === 201
        ),
        page.getByRole('button', { name: 'Create Tag' }).click(),
    ]);
    const tagBody = await tagResponse.json();
    const tagId = tagBody.data.id;

    // Verify tag button now shows "1 Tag"
    await expect(page.getByRole('dialog').getByRole('button', { name: '1 Tag' })).toBeVisible();

    // Set duration
    await page.locator('[role="dialog"] input[name="Duration"]').fill('1h');
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');

    // Submit and verify tags array contains the created tag
    const [createResponse] = await Promise.all([
        page.waitForResponse(
            (response) => response.url().includes('/time-entries') && response.status() === 201
        ),
        page.getByRole('button', { name: 'Create Time Entry' }).click(),
    ]);
    const createBody = await createResponse.json();
    expect(createBody.data.tags).toContain(tagId);
});

test('test that tags dropdown does not show No Tag option in create modal', async ({ page }) => {
    await goToTimeOverview(page);

    // Open the create modal
    await page.getByRole('button', { name: 'Time entry actions' }).click();
    await page.getByRole('menuitem', { name: 'Manual time entry' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // Open tags dropdown
    await page.getByRole('dialog').getByRole('button', { name: 'Tags' }).click();

    // Verify "No Tag" option is not visible
    await expect(page.getByText('No Tag')).not.toBeVisible();
});

test('test that start time picker works in create modal', async ({ page }) => {
    await goToTimeOverview(page);

    // Open the create modal
    await page.getByRole('button', { name: 'Time entry actions' }).click();
    await page.getByRole('menuitem', { name: 'Manual time entry' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // Set description
    await page
        .getByRole('dialog')
        .getByRole('textbox', { name: 'Description' })
        .fill('Time picker test');

    // Set duration first (so it doesn't recalculate start time when we set it)
    await page.locator('[role="dialog"] input[name="Duration"]').fill('1h');
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');

    // Find the start time input (first time_picker_input in the modal)
    const modal = page.getByRole('dialog');
    const startTimeInput = modal.getByTestId('time_picker_input').first();
    await startTimeInput.fill('09:30');
    await startTimeInput.press('Tab');

    // Verify the time picker input shows the correct value
    await expect(startTimeInput).toHaveValue('09:30');

    // Submit and verify the time entry was created
    const [createResponse] = await Promise.all([
        page.waitForResponse(
            (response) => response.url().includes('/time-entries') && response.status() === 201
        ),
        page.getByRole('button', { name: 'Create Time Entry' }).click(),
    ]);
    const createBody = await createResponse.json();
    // The start time should contain 09:30 in the timestamp
    expect(createBody.data.start).toMatch(/09:30/);
});

test('test that end time picker works in create modal', async ({ page }) => {
    await goToTimeOverview(page);

    // Open the create modal
    await page.getByRole('button', { name: 'Time entry actions' }).click();
    await page.getByRole('menuitem', { name: 'Manual time entry' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // Set description
    await page
        .getByRole('dialog')
        .getByRole('textbox', { name: 'Description' })
        .fill('End time picker test');

    // Find the end time input (second time_picker_input in the modal)
    const modal = page.getByRole('dialog');
    const endTimeInput = modal.getByTestId('time_picker_input').nth(1);
    await endTimeInput.fill('17:45');
    await endTimeInput.press('Tab');

    // Set duration (this will adjust based on the times)
    await page.locator('[role="dialog"] input[name="Duration"]').fill('1h');
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');

    // Submit and verify end time contains 17:45
    const [createResponse] = await Promise.all([
        page.waitForResponse(
            (response) => response.url().includes('/time-entries') && response.status() === 201
        ),
        page.getByRole('button', { name: 'Create Time Entry' }).click(),
    ]);
    const createBody = await createResponse.json();
    // The end time should be set (we filled duration after, so it recalculates)
    expect(createBody.data.end).toBeTruthy();
});

test('test that changing project in edit modal from non-billable to billable updates billable status', async ({
    page,
    ctx,
}) => {
    const billableProjectName = 'Billable Modal Project ' + Math.floor(1 + Math.random() * 10000);
    await createBillableProjectViaApi(ctx, { name: billableProjectName });
    await createBareTimeEntryViaApi(ctx, 'Test billable modal', '1h');

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
    ctx,
}) => {
    const billableProjectName = 'Billable Persist Project ' + Math.floor(1 + Math.random() * 10000);
    await createBillableProjectViaApi(ctx, { name: billableProjectName });
    await createBareTimeEntryViaApi(ctx, 'Test persist billable override', '1h');

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
    ctx,
}) => {
    const billableProjectName =
        'Billable Modal Rev Project ' + Math.floor(1 + Math.random() * 10000);
    const nonBillableProjectName =
        'NonBillable Modal Rev Project ' + Math.floor(1 + Math.random() * 10000);
    await createBillableProjectViaApi(ctx, { name: billableProjectName });
    await createProjectViaApi(ctx, { name: nonBillableProjectName });
    await createBareTimeEntryViaApi(ctx, 'Test billable modal reverse', '1h');

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

// =============================================
// Mass Delete Tests
// =============================================

test('test that mass deleting time entries works', async ({ page, ctx }) => {
    const description = 'Mass delete ' + Math.floor(1 + Math.random() * 10000);
    await createBareTimeEntryViaApi(ctx, description, '30min');

    await goToTimeOverview(page);

    const timeEntryRows = page.locator('[data-testid="time_entry_row"]');
    await expect(timeEntryRows.first()).toBeVisible({ timeout: 10000 });

    // Select all time entries using the checkbox
    await page.getByLabel('Select All').click();
    await expect(page.getByText('selected')).toBeVisible();

    // Verify the time entry is visible before deleting
    const entryRow = timeEntryRows.filter({ hasText: description });
    await expect(entryRow).toBeVisible();

    // Click delete button in mass action bar (no confirmation dialog)
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries') && response.request().method() === 'DELETE'
        ),
        page.getByRole('button', { name: 'Delete' }).click(),
    ]);

    // Verify the time entry is no longer visible
    await expect(entryRow).not.toBeVisible();
});

// =============================================
// Delete Single Time Entry Test
// =============================================

test('test that deleting a single time entry via actions menu works', async ({ page, ctx }) => {
    const description = 'Delete single entry ' + Math.floor(1 + Math.random() * 10000);
    await createBareTimeEntryViaApi(ctx, description, '1h');

    await goToTimeOverview(page);

    const timeEntryRow = page
        .locator('[data-testid="time_entry_row"]')
        .filter({ hasText: description });
    await expect(timeEntryRow).toBeVisible({ timeout: 10000 });

    // Open actions menu and click Delete
    await timeEntryRow.getByRole('button', { name: 'Actions for the time entry' }).first().click();
    await expect(page.getByTestId('time_entry_delete')).toBeVisible();
    // The dropdown delete uses the bulk delete endpoint (DELETE /time-entries?ids=...)
    // which returns 200 with a JSON body, not the single endpoint returning 204
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries') &&
                response.request().method() === 'DELETE' &&
                response.status() === 200
        ),
        page.getByTestId('time_entry_delete').click(),
    ]);

    // Verify the time entry is no longer visible
    await expect(timeEntryRow).not.toBeVisible();
});

// =============================================
// Multiple Time Entries Test
// =============================================

test('test that time entries page loads multiple entries created via API', async ({
    page,
    ctx,
}) => {
    for (let i = 0; i < 5; i++) {
        await createBareTimeEntryViaApi(ctx, `Batch entry ${i + 1}`, '30min');
    }

    await goToTimeOverview(page);

    const timeEntryRows = page.locator('[data-testid="time_entry_row"]');
    await expect(timeEntryRows.first()).toBeVisible();
    const count = await timeEntryRows.count();
    expect(count).toBeGreaterThanOrEqual(5);
});

// =============================================
// Employee Permission Tests
// =============================================

test.describe('Employee Time Entry Isolation', () => {
    test('employee can only see their own time entries on the time page', async ({
        ctx,
        employee,
    }) => {
        // Owner creates a time entry
        const ownerDescription = 'OwnerWork ' + Math.floor(Math.random() * 10000);
        await createBareTimeEntryViaApi(ctx, ownerDescription, '1h');

        // Create a time entry for the employee using the owner's context
        const employeeDescription = 'EmpWork ' + Math.floor(Math.random() * 10000);
        await createTimeEntryViaApi(
            { ...ctx, memberId: employee.memberId },
            { description: employeeDescription, duration: '30min' }
        );

        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/time');
        await expect(
            employee.page
                .getByTestId('dashboard_timer')
                .getByTestId('timer_button')
                .and(employee.page.locator(':visible'))
        ).toBeVisible({ timeout: 10000 });

        // Employee's time entry IS visible
        const employeeRow = employee.page
            .locator('[data-testid="time_entry_row"]')
            .filter({ hasText: employeeDescription });
        await expect(employeeRow).toBeVisible({ timeout: 10000 });

        // Owner's time entry is NOT visible
        const ownerRow = employee.page
            .locator('[data-testid="time_entry_row"]')
            .filter({ hasText: ownerDescription });
        await expect(ownerRow).not.toBeVisible();
    });

    test('employee can edit their own time entry', async ({ ctx, employee }) => {
        const description = 'EmpEditEntry ' + Math.floor(Math.random() * 10000);
        await createTimeEntryViaApi(
            { ...ctx, memberId: employee.memberId },
            { description, duration: '1h' }
        );

        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/time');
        const timeEntryRow = employee.page
            .locator('[data-testid="time_entry_row"]')
            .filter({ hasText: description });
        await expect(timeEntryRow).toBeVisible({ timeout: 10000 });

        // Update description
        const updatedDescription = 'Updated ' + description;
        const descriptionInput = timeEntryRow.getByTestId('time_entry_description').first();
        await descriptionInput.fill(updatedDescription);
        await Promise.all([
            employee.page.waitForResponse(
                (response) =>
                    response.url().includes('/time-entries') &&
                    response.request().method() === 'PUT' &&
                    response.status() === 200
            ),
            descriptionInput.press('Tab'),
        ]);

        // Verify updated description
        await expect(timeEntryRow.getByTestId('time_entry_description').first()).toHaveValue(
            updatedDescription
        );
    });

    test('employee can delete their own time entry', async ({ ctx, employee }) => {
        const description = 'EmpDeleteEntry ' + Math.floor(Math.random() * 10000);
        await createTimeEntryViaApi(
            { ...ctx, memberId: employee.memberId },
            { description, duration: '1h' }
        );

        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/time');
        const timeEntryRow = employee.page
            .locator('[data-testid="time_entry_row"]')
            .filter({ hasText: description });
        await expect(timeEntryRow).toBeVisible({ timeout: 10000 });

        // Delete via actions menu
        await timeEntryRow
            .getByRole('button', { name: 'Actions for the time entry' })
            .first()
            .click();
        await Promise.all([
            employee.page.waitForResponse(
                (response) =>
                    response.url().includes('/time-entries') &&
                    response.request().method() === 'DELETE'
            ),
            employee.page.getByTestId('time_entry_delete').click(),
        ]);

        // Verify entry is gone
        await expect(timeEntryRow).not.toBeVisible();
    });
});
