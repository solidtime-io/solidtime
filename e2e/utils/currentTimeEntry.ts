import { Page } from '@playwright/test';

export async function startOrStopTimerWithButton(page: Page) {
    await page
        .locator('[data-testid="dashboard_timer"] [data-testid="timer_button"]')
        .click();
}

export async function assertThatTimerHasStarted(page: Page) {
    await page.locator(
        '[data-testid="dashboard_timer"] [data-testid="timer_button"].bg-red-400/80'
    );
}

export function newTimeEntryResponse(page: Page) {
    return page.waitForResponse(async (response) => {
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

export async function assertThatTimerIsStopped(page: Page) {
    await page.locator(
        '[data-testid="dashboard_timer"] [data-testid="timer_button"].bg-accent-300/70'
    );
}

export async function stoppedTimeEntryResponse(page: Page) {
    return page.waitForResponse(async (response) => {
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
}
