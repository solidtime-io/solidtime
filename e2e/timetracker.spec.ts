import { expect, test } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import {
    assertThatTimerHasStarted,
    assertThatTimerIsStopped,
    newTimeEntryResponse,
    startOrStopTimerWithButton,
    stoppedTimeEntryResponse,
} from './utils/currentTimeEntry';
import { Page } from '@playwright/test';
import { newTagResponse } from './utils/tags';

async function goToDashboard(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
}

test('test that starting and stopping a timer without description and project works', async ({
    page,
}) => {
    await goToDashboard(page);
    await Promise.all([
        newTimeEntryResponse(page),
        startOrStopTimerWithButton(page),
        assertThatTimerHasStarted(page),
    ]);
    await page.waitForTimeout(1500);
    await Promise.all([
        stoppedTimeEntryResponse(page),
        startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerIsStopped(page);
});

test('test that starting and stopping a timer with a description works', async ({
    page,
}) => {
    await goToDashboard(page);
    // TODO: Fix flakyness by disabling description input field until timer is loaded
    await page.waitForTimeout(500);
    await page
        .getByTestId('time_entry_description')
        .fill('New Time Entry Description');
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

    await Promise.all([
        newTimeEntryResponse(page),
        startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerHasStarted(page);
    await page.waitForTimeout(500);
    const beforeTimerValue = await page
        .getByTestId('time_entry_time')
        .inputValue();
    await page.waitForTimeout(2000);
    const afterWaitTimeValue = await page
        .getByTestId('time_entry_time')
        .inputValue();
    expect(afterWaitTimeValue).not.toEqual(beforeTimerValue);
    await page.reload();
    await page.waitForTimeout(500);

    const afterReloadTimerValue = await page
        .getByTestId('time_entry_time')
        .inputValue();
    await page.waitForTimeout(2000);
    const afterReloadAfterWaitTimerValue = await page
        .getByTestId('time_entry_time')
        .inputValue();
    expect(afterReloadTimerValue).not.toEqual(afterReloadAfterWaitTimerValue);
});

test('test that starting and updating the description while running works', async ({
    page,
}) => {
    await goToDashboard(page);

    await Promise.all([
        newTimeEntryResponse(page),
        startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerHasStarted(page);
    await page.waitForTimeout(500);
    await page
        .getByTestId('time_entry_description')
        .fill('New Time Entry Description');

    await Promise.all([
        newTimeEntryResponse(page, {
            status: 200,
            description: 'New Time Entry Description',
        }),
        page.getByTestId('time_entry_description').press('Tab'),
    ]);
    await page.waitForTimeout(500);
    await Promise.all([
        stoppedTimeEntryResponse(page, {
            description: 'New Time Entry Description',
        }),
        startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerIsStopped(page);
});

test('test that starting and updating the time while running works', async ({
    page,
}) => {
    await goToDashboard(page);
    const [createResponse] = await Promise.all([
        newTimeEntryResponse(page),
        await startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerHasStarted(page);
    await page.waitForTimeout(500);
    await page.getByTestId('time_entry_time').fill('20min');

    await Promise.all([
        page.waitForResponse(async (response) => {
            return (
                response.status() === 200 &&
                (await response.headerValue('Content-Type')) ===
                    'application/json' &&
                (await response.json()).data.id !== null &&
                (await response.json()).data.start !== null &&
                (await response.json()).data.start !==
                    (await createResponse.json()).data.start &&
                (await response.json()).data.end === null &&
                (await response.json()).data.project_id === null &&
                (await response.json()).data.description === '' &&
                (await response.json()).data.task_id === null &&
                (await response.json()).data.user_id !== null &&
                JSON.stringify((await response.json()).data.tags) ===
                    JSON.stringify([])
            );
        }),
        page.getByTestId('time_entry_time').press('Enter'),
    ]);

    await expect(page.getByTestId('time_entry_time')).toHaveValue(/00:20/);
    await page.waitForTimeout(500);
    await Promise.all([
        stoppedTimeEntryResponse(page),
        startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerIsStopped(page);
});

test('test that entering a human readable time starts the timer on blur', async ({
    page,
}) => {
    await goToDashboard(page);
    await page.getByTestId('time_entry_time').fill('20min');
    await Promise.all([
        newTimeEntryResponse(page),
        page.getByTestId('time_entry_time').press('Tab'),
    ]);
    await expect(page.getByTestId('time_entry_time')).toHaveValue(/00:20:/);
    await assertThatTimerHasStarted(page);

    await Promise.all([
        stoppedTimeEntryResponse(page),
        startOrStopTimerWithButton(page),
    ]);
    await page.locator(
        '[data-testid="dashboard_timer"] [data-testid="timer_button"].bg-accent-300/70'
    );
});

test('test that entering a number in the time range starts the timer on blur', async ({
    page,
}) => {
    await goToDashboard(page);
    await page.getByTestId('time_entry_time').fill('5');
    await Promise.all([
        newTimeEntryResponse(page),
        page.getByTestId('time_entry_time').press('Tab'),
    ]);
    await expect(page.getByTestId('time_entry_time')).toHaveValue(/00:05:/);
    await assertThatTimerHasStarted(page);

    await Promise.all([
        stoppedTimeEntryResponse(page),
        startOrStopTimerWithButton(page),
    ]);
    await page.locator(
        '[data-testid="dashboard_timer"] [data-testid="timer_button"].bg-accent-300/70'
    );
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

    await Promise.all([
        stoppedTimeEntryResponse(page),
        startOrStopTimerWithButton(page),
    ]);
    await page.locator(
        '[data-testid="dashboard_timer"] [data-testid="timer_button"].bg-accent-300/70'
    );
});

test('test that entering a random value in the time range does not start the timer on blur', async ({
    page,
}) => {
    await goToDashboard(page);
    await page.getByTestId('time_entry_time').fill('asdasdasd');
    await page.getByTestId('time_entry_time').press('Tab'),
        await page.locator(
            '[data-testid="dashboard_timer"] [data-testid="timer_button"].bg-accent-300/70'
        );
});

test('test that entering a time starts the timer on enter', async ({
    page,
}) => {
    await goToDashboard(page);
    await page.getByTestId('time_entry_time').fill('20min');
    await Promise.all([
        newTimeEntryResponse(page),
        page.getByTestId('time_entry_time').press('Enter'),
    ]);
    await assertThatTimerHasStarted(page);
    await Promise.all([
        stoppedTimeEntryResponse(page),
        startOrStopTimerWithButton(page),
    ]);
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

    await page.getByTestId('tag_dropdown').click();
    await expect(page.getByRole('option', { name: newTagName })).toBeVisible();
});

test('test that adding a new tag when the timer is running', async ({
    page,
}) => {
    const newTagName = 'New Tag' + Math.floor(Math.random() * 10000);
    await goToDashboard(page);
    await Promise.all([
        newTimeEntryResponse(page),
        startOrStopTimerWithButton(page),
    ]);
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
    await page.waitForTimeout(1000);

    await Promise.all([
        stoppedTimeEntryResponse(page, { tags: [tagId] }),
        startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerIsStopped(page);
});

// test that search is working

// test that adding a tag and project and starting the timer afterwards works and sets the project and tag correctly

// test that changing the project works

// test that sidebar timetracker starts and stops timer

// test that sidebar timetracker changes state when tmer on dashboard is started

// test billable toggle

// TODO: Test that project can be created in the time tracker row

// Add Test that time tracker starts on enter with description
