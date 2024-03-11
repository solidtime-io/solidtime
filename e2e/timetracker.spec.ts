import { expect, test } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';

async function goToDashboard(page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
}

async function startOrStopTimerWithButton(page) {
    await page
        .locator('[data-testid="dashboard_timer"] [data-testid="timer_button"]')
        .click();
}

async function assertThatTimerHasStarted(page) {
    await page.locator(
        '[data-testid="dashboard_timer"] [data-testid="timer_button"].bg-red-400/80'
    );
}

async function assertNewTimeEntryResponse(page) {
    await page.waitForResponse(async (response) => {
        return (
            response.status() === 201 &&
            (await response.headerValue('Content-Type')) ===
                'application/json' &&
            (await response.json()).data.id !== null &&
            (await response.json()).data.start !== null &&
            (await response.json()).data.end === null &&
            (await response.json()).data.project_id === null &&
            (await response.json()).data.description === '' &&
            (await response.json()).data.task_id === null &&
            (await response.json()).data.duration === null &&
            (await response.json()).data.user_id !== null &&
            JSON.stringify((await response.json()).data.tags) ===
                JSON.stringify([])
        );
    });
}

async function assertThatTimerIsStoped(page) {
    await page.locator(
        '[data-testid="dashboard_timer"] [data-testid="timer_button"].bg-accent-300/70'
    );
}

test('test that starting and stopping a timer without description and project works', async ({
    page,
}) => {
    await goToDashboard(page);
    await startOrStopTimerWithButton(page);
    await assertNewTimeEntryResponse(page);
    await assertThatTimerHasStarted(page);
    await page.waitForTimeout(1500);
    await startOrStopTimerWithButton(page);
    await page.waitForResponse(async (response) => {
        return (
            response.status() === 200 &&
            (await response.headerValue('Content-Type')) ===
                'application/json' &&
            (await response.json()).data.id !== null &&
            (await response.json()).data.start !== null &&
            (await response.json()).data.end !== null &&
            (await response.json()).data.project_id === null &&
            (await response.json()).data.description === '' &&
            (await response.json()).data.task_id === null &&
            (await response.json()).data.duration !== null &&
            (await response.json()).data.user_id !== null &&
            JSON.stringify((await response.json()).data.tags) ===
                JSON.stringify([])
        );
    });
    await assertThatTimerIsStoped(page);
});

test('test that starting and stopping a timer with a description works', async ({
    page,
}) => {
    await goToDashboard(page);
    await page
        .getByTestId('time_entry_description')
        .fill('New Time Entry Description');
    await startOrStopTimerWithButton(page);
    await page.waitForResponse(async (response) => {
        return (
            response.status() === 201 &&
            (await response.headerValue('Content-Type')) ===
                'application/json' &&
            (await response.json()).data.id !== null &&
            (await response.json()).data.start !== null &&
            (await response.json()).data.end === null &&
            (await response.json()).data.project_id === null &&
            (await response.json()).data.description ===
                'New Time Entry Description' &&
            (await response.json()).data.task_id === null &&
            (await response.json()).data.duration === null &&
            (await response.json()).data.user_id !== null &&
            JSON.stringify((await response.json()).data.tags) ===
                JSON.stringify([])
        );
    });
    await assertThatTimerHasStarted(page);
    await page.waitForTimeout(500);
    await startOrStopTimerWithButton(page);
    await page.waitForResponse(async (response) => {
        return (
            response.status() === 200 &&
            (await response.headerValue('Content-Type')) ===
                'application/json' &&
            (await response.json()).data.id !== null &&
            (await response.json()).data.start !== null &&
            (await response.json()).data.end !== null &&
            (await response.json()).data.project_id === null &&
            (await response.json()).data.description ===
                'New Time Entry Description' &&
            (await response.json()).data.task_id === null &&
            (await response.json()).data.duration !== null &&
            (await response.json()).data.user_id !== null &&
            JSON.stringify((await response.json()).data.tags) ===
                JSON.stringify([])
        );
    });
    await assertThatTimerIsStoped(page);
});

test('test that starting and updating the description while running works', async ({
    page,
}) => {
    await goToDashboard(page);
    await startOrStopTimerWithButton(page);
    await page.waitForResponse(async (response) => {
        return (
            response.status() === 201 &&
            (await response.headerValue('Content-Type')) ===
                'application/json' &&
            (await response.json()).data.id !== null &&
            (await response.json()).data.start !== null &&
            (await response.json()).data.end === null &&
            (await response.json()).data.project_id === null &&
            (await response.json()).data.description === '' &&
            (await response.json()).data.task_id === null &&
            (await response.json()).data.duration === null &&
            (await response.json()).data.user_id !== null &&
            JSON.stringify((await response.json()).data.tags) ===
                JSON.stringify([])
        );
    });
    await assertThatTimerHasStarted(page);
    await page.waitForTimeout(500);
    await page
        .getByTestId('time_entry_description')
        .fill('New Time Entry Description');
    await page.getByTestId('time_entry_description').press('Tab');
    await page.waitForResponse(async (response) => {
        return (
            response.status() === 200 &&
            (await response.headerValue('Content-Type')) ===
                'application/json' &&
            (await response.json()).data.id !== null &&
            (await response.json()).data.start !== null &&
            (await response.json()).data.end === null &&
            (await response.json()).data.project_id === null &&
            (await response.json()).data.description ===
                'New Time Entry Description' &&
            (await response.json()).data.task_id === null &&
            (await response.json()).data.duration === null &&
            (await response.json()).data.user_id !== null &&
            JSON.stringify((await response.json()).data.tags) ===
                JSON.stringify([])
        );
    });
    await page.waitForTimeout(500);
    await startOrStopTimerWithButton(page);
    await page.waitForResponse(async (response) => {
        return (
            response.status() === 200 &&
            (await response.headerValue('Content-Type')) ===
                'application/json' &&
            (await response.json()).data.id !== null &&
            (await response.json()).data.start !== null &&
            (await response.json()).data.end !== null &&
            (await response.json()).data.project_id === null &&
            (await response.json()).data.description ===
                'New Time Entry Description' &&
            (await response.json()).data.task_id === null &&
            (await response.json()).data.duration !== null &&
            (await response.json()).data.user_id !== null &&
            JSON.stringify((await response.json()).data.tags) ===
                JSON.stringify([])
        );
    });
    await assertThatTimerIsStoped(page);
});

test('test that starting and updating the time while running works', async ({
    page,
}) => {
    await goToDashboard(page);
    await startOrStopTimerWithButton(page);
    const createResponse = await page.waitForResponse(async (response) => {
        return (
            response.status() === 201 &&
            (await response.headerValue('Content-Type')) ===
                'application/json' &&
            (await response.json()).data.id !== null &&
            (await response.json()).data.start !== null &&
            (await response.json()).data.end === null &&
            (await response.json()).data.project_id === null &&
            (await response.json()).data.description === '' &&
            (await response.json()).data.task_id === null &&
            (await response.json()).data.duration === null &&
            (await response.json()).data.user_id !== null &&
            JSON.stringify((await response.json()).data.tags) ===
                JSON.stringify([])
        );
    });
    await assertThatTimerHasStarted(page);
    await page.waitForTimeout(500);
    await page.getByTestId('time_entry_time').fill('20min');
    await page.getByTestId('time_entry_time').press('Tab');
    await page.waitForResponse(async (response) => {
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
            (await response.json()).data.duration === null &&
            (await response.json()).data.user_id !== null &&
            JSON.stringify((await response.json()).data.tags) ===
                JSON.stringify([])
        );
    });
    await expect(page.getByTestId('time_entry_time')).toHaveValue(/00:20/);
    await page.waitForTimeout(500);
    await startOrStopTimerWithButton(page);
    await page.waitForResponse(async (response) => {
        return (
            response.status() === 200 &&
            (await response.headerValue('Content-Type')) ===
                'application/json' &&
            (await response.json()).data.id !== null &&
            (await response.json()).data.start !== null &&
            (await response.json()).data.end !== null &&
            (await response.json()).data.project_id === null &&
            (await response.json()).data.description === '' &&
            (await response.json()).data.task_id === null &&
            (await response.json()).data.duration !== null &&
            (await response.json()).data.user_id !== null &&
            JSON.stringify((await response.json()).data.tags) ===
                JSON.stringify([])
        );
    });
    await assertThatTimerIsStoped(page);
});

test('test that entering a time starts the timer on blur', async ({ page }) => {
    await goToDashboard(page);
    await page.getByTestId('time_entry_time').fill('20min');
    await page.getByTestId('time_entry_time').press('Tab');
    await page.waitForResponse(async (response) => {
        return (
            response.status() === 201 &&
            (await response.headerValue('Content-Type')) ===
                'application/json' &&
            (await response.json()).data.id !== null &&
            (await response.json()).data.start !== null &&
            (await response.json()).data.end === null &&
            (await response.json()).data.project_id === null &&
            (await response.json()).data.description === '' &&
            (await response.json()).data.task_id === null &&
            (await response.json()).data.duration === null &&
            (await response.json()).data.user_id !== null &&
            JSON.stringify((await response.json()).data.tags) ===
                JSON.stringify([])
        );
    });
    await assertThatTimerHasStarted(page);
    await startOrStopTimerWithButton(page);
    await page.waitForResponse(async (response) => {
        return (
            response.status() === 200 &&
            (await response.headerValue('Content-Type')) ===
                'application/json' &&
            (await response.json()).data.id !== null &&
            (await response.json()).data.start !== null &&
            (await response.json()).data.end !== null &&
            (await response.json()).data.project_id === null &&
            (await response.json()).data.description === '' &&
            (await response.json()).data.task_id === null &&
            (await response.json()).data.duration !== null &&
            (await response.json()).data.user_id !== null &&
            JSON.stringify((await response.json()).data.tags) ===
                JSON.stringify([])
        );
    });
    await page.locator(
        '[data-testid="dashboard_timer"] [data-testid="timer_button"].bg-accent-300/70'
    );
});

test('test that entering a time starts the timer on enter', async ({
    page,
}) => {
    await goToDashboard(page);
    await page.getByTestId('time_entry_time').fill('20min');
    await page.getByTestId('time_entry_time').press('Enter');
    await page.waitForResponse(async (response) => {
        return (
            response.status() === 201 &&
            (await response.headerValue('Content-Type')) ===
                'application/json' &&
            (await response.json()).data.id !== null &&
            (await response.json()).data.start !== null &&
            (await response.json()).data.end === null &&
            (await response.json()).data.project_id === null &&
            (await response.json()).data.description === '' &&
            (await response.json()).data.task_id === null &&
            (await response.json()).data.duration === null &&
            (await response.json()).data.user_id !== null &&
            JSON.stringify((await response.json()).data.tags) ===
                JSON.stringify([])
        );
    });
    await assertThatTimerHasStarted(page);
    await startOrStopTimerWithButton(page);
    await page.waitForResponse(async (response) => {
        return (
            response.status() === 200 &&
            (await response.headerValue('Content-Type')) ===
                'application/json' &&
            (await response.json()).data.id !== null &&
            (await response.json()).data.start !== null &&
            (await response.json()).data.end !== null &&
            (await response.json()).data.project_id === null &&
            (await response.json()).data.description === '' &&
            (await response.json()).data.task_id === null &&
            (await response.json()).data.duration !== null &&
            (await response.json()).data.user_id !== null &&
            JSON.stringify((await response.json()).data.tags) ===
                JSON.stringify([])
        );
    });
    await assertThatTimerIsStoped(page);
});

test('test that adding a new tag works', async ({ page }) => {
    const newTagName = 'New Tag' + Math.floor(Math.random() * 10000);
    await goToDashboard(page);
    await page.getByTestId('tag_dropdown').click();
    await page.getByTestId('tag_dropdown_search').fill(newTagName);
    await page.getByTestId('tag_dropdown_search').press('Enter');
    await page.waitForResponse(async (response) => {
        return (
            response.status() === 201 &&
            (await response.headerValue('Content-Type')) ===
                'application/json' &&
            (await response.json()).data.name === newTagName
        );
    });
    await expect(page.getByTestId('tag_dropdown_search')).toHaveValue('');
    await expect(page.getByTestId('tag_dropdown_entries')).toHaveText(
        newTagName
    );
});

test('test that adding a new tag when the timer is running', async ({
    page,
}) => {
    const newTagName = 'New Tag' + Math.floor(Math.random() * 10000);
    await goToDashboard(page);
    await startOrStopTimerWithButton(page);
    await assertNewTimeEntryResponse(page);
    await assertThatTimerHasStarted(page);
    await page.getByTestId('tag_dropdown').click();
    await page.getByTestId('tag_dropdown_search').fill(newTagName);
    await page.getByTestId('tag_dropdown_search').press('Enter');
    const tagCreateResponse = await page.waitForResponse(async (response) => {
        return (
            response.status() === 201 &&
            (await response.headerValue('Content-Type')) ===
                'application/json' &&
            (await response.json()).data.name === newTagName
        );
    });
    await expect(page.getByTestId('tag_dropdown_search')).toHaveValue('');
    await expect(page.getByTestId('tag_dropdown_entries')).toHaveText(
        newTagName
    );

    await page.waitForResponse(async (response) => {
        return (
            response.status() === 200 &&
            (await response.headerValue('Content-Type')) ===
                'application/json' &&
            (await response.json()).data.id !== null &&
            (await response.json()).data.start !== null &&
            (await response.json()).data.end === null &&
            (await response.json()).data.project_id === null &&
            (await response.json()).data.description === '' &&
            (await response.json()).data.task_id === null &&
            (await response.json()).data.duration === null &&
            (await response.json()).data.user_id !== null &&
            JSON.stringify((await response.json()).data.tags) ===
                JSON.stringify([(await tagCreateResponse.json()).data.id])
        );
    });
    await page.getByTestId('tag_dropdown_search').press('Escape');
    await page.waitForTimeout(1000);
    await startOrStopTimerWithButton(page);
    await page.waitForResponse(async (response) => {
        return (
            response.status() === 200 &&
            (await response.headerValue('Content-Type')) ===
                'application/json' &&
            (await response.json()).data.id !== null &&
            (await response.json()).data.start !== null &&
            (await response.json()).data.end !== null &&
            (await response.json()).data.project_id === null &&
            (await response.json()).data.description === '' &&
            (await response.json()).data.task_id === null &&
            (await response.json()).data.duration !== null &&
            (await response.json()).data.user_id !== null &&
            JSON.stringify((await response.json()).data.tags) ===
                JSON.stringify([(await tagCreateResponse.json()).data.id])
        );
    });
    await assertThatTimerIsStoped(page);
});

// test that adding a new tag when the timer is running

// test that search is working
