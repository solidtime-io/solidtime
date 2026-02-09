import { expect, test } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import {
    assertThatTimerHasStarted,
    assertThatTimerIsStopped,
    newTimeEntryResponse,
    startOrStopTimerWithButton,
    stoppedTimeEntryResponse,
} from './utils/currentTimeEntry';
import type { Page } from '@playwright/test';
import { newTagResponse } from './utils/tags';

// Date picker button name patterns for different date formats
const DATE_DISPLAY_PATTERN = /^\d{4}-\d{2}-\d{2}$|^\d{2}\/\d{2}\/\d{4}$|^\d{2}\.\d{2}\.\d{4}$/;

async function goToDashboard(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
}

test('test that starting and stopping a timer without description and project works', async ({
    page,
}) => {
    await goToDashboard(page);
    await Promise.all([newTimeEntryResponse(page), startOrStopTimerWithButton(page)]);
    await assertThatTimerHasStarted(page);
    await page.waitForTimeout(1500);
    await Promise.all([stoppedTimeEntryResponse(page), startOrStopTimerWithButton(page)]);
    await assertThatTimerIsStopped(page);
});

test('test that starting and stopping a timer with a description works', async ({ page }) => {
    await goToDashboard(page);
    // Wait for the description input to be editable before filling
    await expect(page.getByTestId('time_entry_description')).toBeEditable();
    await page.getByTestId('time_entry_description').fill('New Time Entry Description');
    await Promise.all([
        newTimeEntryResponse(page, {
            description: 'New Time Entry Description',
        }),
        startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerHasStarted(page);
    await page.waitForTimeout(1500);
    await Promise.all([
        stoppedTimeEntryResponse(page, {
            description: 'New Time Entry Description',
        }),
        await startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerIsStopped(page);
});

test('test that starting the time entry starts the live timer and that it keeps running after reload', async ({
    page,
}) => {
    await goToDashboard(page);

    await Promise.all([newTimeEntryResponse(page), startOrStopTimerWithButton(page)]);
    await assertThatTimerHasStarted(page);
    const beforeTimerValue = await page.getByTestId('time_entry_time').inputValue();
    await page.waitForTimeout(2000);
    const afterWaitTimeValue = await page.getByTestId('time_entry_time').inputValue();
    expect(afterWaitTimeValue).not.toEqual(beforeTimerValue);
    await page.reload();
    await expect(page.getByTestId('time_entry_time')).toBeVisible();

    const afterReloadTimerValue = await page.getByTestId('time_entry_time').inputValue();
    await page.waitForTimeout(2000);
    const afterReloadAfterWaitTimerValue = await page.getByTestId('time_entry_time').inputValue();
    expect(afterReloadTimerValue).not.toEqual(afterReloadAfterWaitTimerValue);
});

test('test that starting and updating the description while running works', async ({ page }) => {
    await goToDashboard(page);

    await Promise.all([newTimeEntryResponse(page), startOrStopTimerWithButton(page)]);
    await assertThatTimerHasStarted(page);
    await expect(page.getByTestId('time_entry_description')).toBeEditable();
    await page.getByTestId('time_entry_description').fill('New Time Entry Description');

    await Promise.all([
        newTimeEntryResponse(page, {
            status: 200,
            description: 'New Time Entry Description',
        }),
        page.getByTestId('time_entry_description').press('Tab'),
    ]);
    await Promise.all([
        stoppedTimeEntryResponse(page, {
            description: 'New Time Entry Description',
        }),
        startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerIsStopped(page);
});

test('test that starting and updating the time while running works', async ({ page }) => {
    await goToDashboard(page);
    const [createResponse] = await Promise.all([
        newTimeEntryResponse(page),
        await startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerHasStarted(page);
    await expect(page.getByTestId('time_entry_time')).toBeEditable();
    await page.getByTestId('time_entry_time').fill('20min');

    await Promise.all([
        page.waitForResponse(async (response) => {
            return (
                response.url().includes('/time-entries') &&
                response.status() === 200 &&
                (await response.headerValue('Content-Type')) === 'application/json' &&
                (await response.json()).data.id !== null &&
                (await response.json()).data.start !== null &&
                (await response.json()).data.start !== (await createResponse.json()).data.start &&
                (await response.json()).data.end === null &&
                (await response.json()).data.project_id === null &&
                (await response.json()).data.description === '' &&
                (await response.json()).data.task_id === null &&
                (await response.json()).data.user_id !== null &&
                JSON.stringify((await response.json()).data.tags) === JSON.stringify([])
            );
        }),
        page.getByTestId('time_entry_time').press('Enter'),
    ]);

    await expect(page.getByTestId('time_entry_time')).toHaveValue(/00:20/);
    await Promise.all([stoppedTimeEntryResponse(page), startOrStopTimerWithButton(page)]);
    await assertThatTimerIsStopped(page);
});

test('test that entering a human readable time starts the timer on blur', async ({ page }) => {
    await goToDashboard(page);
    await page.getByTestId('time_entry_time').fill('20min');
    await Promise.all([
        newTimeEntryResponse(page),
        page.getByTestId('time_entry_time').press('Tab'),
    ]);
    await expect(page.getByTestId('time_entry_time')).toHaveValue(/00:20:/);
    await assertThatTimerHasStarted(page);

    await Promise.all([stoppedTimeEntryResponse(page), startOrStopTimerWithButton(page)]);
    await assertThatTimerIsStopped(page);
});

test('test that entering a number in the time range starts the timer on blur', async ({ page }) => {
    await goToDashboard(page);
    await page.getByTestId('time_entry_time').fill('5');
    await Promise.all([
        newTimeEntryResponse(page),
        page.getByTestId('time_entry_time').press('Tab'),
    ]);
    await expect(page.getByTestId('time_entry_time')).toHaveValue(/00:05:/);
    await assertThatTimerHasStarted(page);

    await Promise.all([stoppedTimeEntryResponse(page), startOrStopTimerWithButton(page)]);
    await assertThatTimerIsStopped(page);
});

test('test that entering a value with the format hh:mm in the time range starts the timer on blur', async ({
    page,
}) => {
    await goToDashboard(page);
    await page.getByTestId('time_entry_time').fill('12:30');
    await Promise.all([
        newTimeEntryResponse(page),
        page.getByTestId('time_entry_time').press('Tab'),
    ]);
    await expect(page.getByTestId('time_entry_time')).toHaveValue(/12:30:/);
    await assertThatTimerHasStarted(page);

    await Promise.all([stoppedTimeEntryResponse(page), startOrStopTimerWithButton(page)]);
    await assertThatTimerIsStopped(page);
});

test('test that entering a random value in the time range does not start the timer on blur', async ({
    page,
}) => {
    await goToDashboard(page);
    await page.getByTestId('time_entry_time').fill('asdasdasd');
    await page.getByTestId('time_entry_time').press('Tab');
    await assertThatTimerIsStopped(page);
});

test('test that entering a time starts the timer on enter', async ({ page }) => {
    await goToDashboard(page);
    await page.getByTestId('time_entry_time').fill('20min');
    await Promise.all([
        newTimeEntryResponse(page),
        page.getByTestId('time_entry_time').press('Enter'),
    ]);
    await assertThatTimerHasStarted(page);
    await Promise.all([stoppedTimeEntryResponse(page), startOrStopTimerWithButton(page)]);
    await assertThatTimerIsStopped(page);
});

test('test that adding a new tag works', async ({ page }) => {
    const newTagName = 'New Tag' + Math.floor(Math.random() * 10000);
    await goToDashboard(page);

    await page.getByTestId('tag_dropdown').click();
    await page.getByText('Create new tag').click();
    await page.getByPlaceholder('Tag Name').fill(newTagName);

    await Promise.all([
        newTagResponse(page, { name: newTagName }),
        page.getByRole('button', { name: 'Create Tag' }).click(),
    ]);

    // Wait for tags query refetch after invalidation
    await page.waitForResponse(
        (response) => response.url().includes('/tags') && response.status() === 200
    );

    await page.getByTestId('tag_dropdown').click();
    await expect(page.getByRole('option', { name: newTagName })).toBeVisible();
});

test('test that adding a new tag when the timer is running', async ({ page }) => {
    const newTagName = 'New Tag' + Math.floor(Math.random() * 10000);
    await goToDashboard(page);
    await Promise.all([newTimeEntryResponse(page), startOrStopTimerWithButton(page)]);
    await assertThatTimerHasStarted(page);
    await page.getByTestId('tag_dropdown').click();
    await page.getByText('Create new tag').click();
    await page.getByPlaceholder('Tag Name').fill(newTagName);

    const [tagCreateResponse] = await Promise.all([
        newTagResponse(page, { name: newTagName }),
        page.getByRole('button', { name: 'Create Tag' }).click(),
    ]);
    const tagId = (await tagCreateResponse.json()).data.id;
    await newTimeEntryResponse(page, { status: 200, tags: [tagId] });
    await page.getByTestId('tag_dropdown').click();
    await expect(page.getByRole('option', { name: newTagName })).toBeVisible();
    await page.getByTestId('tag_dropdown_search').press('Escape');
    await expect(page.getByTestId('tag_dropdown_search')).not.toBeVisible();

    await Promise.all([
        stoppedTimeEntryResponse(page, { tags: [tagId] }),
        startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerIsStopped(page);
});

test('test that setting an end time with a different date via the timetracker range selector works', async ({
    page,
}) => {
    await goToDashboard(page);

    // Start a timer
    await Promise.all([newTimeEntryResponse(page), startOrStopTimerWithButton(page)]);
    await assertThatTimerHasStarted(page);

    // Open the time range dropdown by clicking on the time display
    await page.getByTestId('time_entry_time').click();
    const rangeStart = page.getByTestId('time_entry_range_start');
    await expect(rangeStart).toBeVisible();

    // Click "Set End Time" button
    await page.getByRole('button', { name: 'Set End Time' }).click();

    // The end time picker should now be visible with a Confirm button
    const rangeEnd = page.getByTestId('time_entry_range_end');
    await expect(rangeEnd).toBeVisible();
    const confirmButton = page.getByRole('button', { name: 'Confirm' });
    await expect(confirmButton).toBeVisible();

    // Click the end date picker to change the date
    const endDatePickers = page.getByRole('button', { name: DATE_DISPLAY_PATTERN });
    // The second date picker is the end date (first is the start date)
    const endDatePicker = endDatePickers.nth(1);
    await expect(endDatePicker).toBeVisible();
    await endDatePicker.click();

    // Calendar should appear
    const calendarGrid = page.getByRole('grid');
    await expect(calendarGrid).toBeVisible({ timeout: 5000 });

    // Navigate to the next month and select a day to ensure end > start
    await page.getByRole('button', { name: /Next/i }).click();
    await page.getByRole('gridcell').filter({ hasText: /^15$/ }).first().click();

    // The dropdown should still be open after selecting a date (not auto-closed)
    await expect(rangeEnd).toBeVisible();
    await expect(confirmButton).toBeVisible();

    // Click Confirm to finalize and verify the API call
    const [updateResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        confirmButton.click(),
    ]);
    const updateBody = await updateResponse.json();
    expect(updateBody.data.start).toBeTruthy();
    expect(updateBody.data.end).toBeTruthy();
});

test('test that timer starts on enter with description', async ({ page }) => {
    await goToDashboard(page);
    await expect(page.getByTestId('time_entry_description')).toBeEditable();
    await page.getByTestId('time_entry_description').fill('Start on Enter');

    await Promise.all([
        newTimeEntryResponse(page, { description: 'Start on Enter' }),
        page.getByTestId('time_entry_description').press('Enter'),
    ]);
    await assertThatTimerHasStarted(page);

    await Promise.all([
        stoppedTimeEntryResponse(page, { description: 'Start on Enter' }),
        startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerIsStopped(page);
});

test('test that timer started on dashboard is visible on time page', async ({ page }) => {
    await goToDashboard(page);

    // Start timer on dashboard
    await expect(page.getByTestId('time_entry_description')).toBeEditable();
    await page.getByTestId('time_entry_description').fill('Sync test');
    await Promise.all([
        newTimeEntryResponse(page, { description: 'Sync test' }),
        startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerHasStarted(page);

    // Navigate to time page
    await page.goto(PLAYWRIGHT_BASE_URL + '/time');

    // Timer should still be running (the timer button should be red/active)
    await expect(
        page.locator('[data-testid="dashboard_timer"] [data-testid="timer_button"]')
    ).toHaveClass(/bg-red-400\/80/);

    // Stop the timer
    await Promise.all([
        stoppedTimeEntryResponse(page, { description: 'Sync test' }),
        startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerIsStopped(page);
});

test('test that adding a project and tag before starting timer works', async ({ page }) => {
    const newTagName = 'TimerTag ' + Math.floor(Math.random() * 10000);
    await goToDashboard(page);

    // Create and select a tag first
    await page.getByTestId('tag_dropdown').click();
    await page.getByText('Create new tag').click();
    await page.getByPlaceholder('Tag Name').fill(newTagName);

    const [tagCreateResponse] = await Promise.all([
        newTagResponse(page, { name: newTagName }),
        page.getByRole('button', { name: 'Create Tag' }).click(),
    ]);
    const tagId = (await tagCreateResponse.json()).data.id;

    // Wait for tags query refetch (tag is auto-selected after creation)
    await page.waitForResponse(
        (response) => response.url().includes('/tags') && response.status() === 200
    );

    // Fill description and start
    await page.getByTestId('time_entry_description').fill('Entry with tag');
    await Promise.all([
        newTimeEntryResponse(page, { description: 'Entry with tag', tags: [tagId] }),
        startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerHasStarted(page);

    await Promise.all([
        stoppedTimeEntryResponse(page, { description: 'Entry with tag', tags: [tagId] }),
        startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerIsStopped(page);
});
