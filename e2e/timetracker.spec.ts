import {expect, test} from '../playwright/fixtures';
import {PLAYWRIGHT_BASE_URL} from '../playwright/config';

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

function newTimeEntryResponse(page) {
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

async function assertThatTimerIsStopped(page) {
    await page.locator(
        '[data-testid="dashboard_timer"] [data-testid="timer_button"].bg-accent-300/70'
    );
}

test('test that starting and stopping a timer without description and project works',
    async ({
               page,
           }) => {
        await goToDashboard(page);
        const newTimeEntryPromise = newTimeEntryResponse(page);
        await startOrStopTimerWithButton(page);
        await newTimeEntryPromise;
        await assertThatTimerHasStarted(page);
        await page.waitForTimeout(1500);
        const [request] = await Promise.all([
            page.waitForResponse(async (response) => {
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
            }),
            startOrStopTimerWithButton(page)
        ]);
        await assertThatTimerIsStopped(page);
    });

test('test that starting and stopping a timer with a description works',
    async ({
               page,
           }) => {
        await goToDashboard(page);
        await page
            .getByTestId('time_entry_description')
            .fill('New Time Entry Description');
        const [request] = await Promise.all([
            page.waitForResponse(async (response) => {
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
            }),
            startOrStopTimerWithButton(page)
        ]);
        await assertThatTimerHasStarted(page);
        await page.waitForTimeout(1500);
        await Promise.all([
            page.waitForResponse(async (response) => {
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
            }),
            await startOrStopTimerWithButton(page)
        ])
        await assertThatTimerIsStopped(page);
    });

test('test that starting and updating the description while running works', async ({
                                                                                       page,
                                                                                   }) => {
    await goToDashboard(page);

    await Promise.all([
            page.waitForResponse(async (response) => {
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
            }),
            startOrStopTimerWithButton(page)
        ]
    )
    await assertThatTimerHasStarted(page);
    await page.waitForTimeout(500);
    await page
        .getByTestId('time_entry_description')
        .fill('New Time Entry Description');

    await Promise.all([
        page.waitForResponse(async (response) => {
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
        }),
        page.getByTestId('time_entry_description').press('Tab')
    ])
    await page.waitForTimeout(500);
    const endTimerResponsePromise =
        await Promise.all([
            page.waitForResponse(async (response) => {
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
            }),
            await startOrStopTimerWithButton(page)
        ])
    await assertThatTimerIsStopped(page);
});

test('test that starting and updating the time while running works', async ({
                                                                                page,
                                                                            }) => {
    await goToDashboard(page);
    const [createResponse] = await Promise.all([
        page.waitForResponse(async (response) => {
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
        }),
        await startOrStopTimerWithButton(page)]);
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
                (await response.json()).data.duration === null &&
                (await response.json()).data.user_id !== null &&
                JSON.stringify((await response.json()).data.tags) ===
                JSON.stringify([])
            );
        }),
        page.getByTestId('time_entry_time').press('Tab')
    ]);

    await expect(page.getByTestId('time_entry_time')).toHaveValue(/00:20/);
    await page.waitForTimeout(500);
    await Promise.all([
        page.waitForResponse(async (response) => {
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
        }),
        startOrStopTimerWithButton(page)
    ])
    await assertThatTimerIsStopped(page);
});

test('test that entering a time starts the timer on blur', async ({page}) => {
    await goToDashboard(page);
    await page.getByTestId('time_entry_time').fill('20min');
    await Promise.all([page.waitForResponse(async (response) => {
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
    }),
        page.getByTestId('time_entry_time').press('Tab')
    ])
    await assertThatTimerHasStarted(page);

    await Promise.all([
        page.waitForResponse(async (response) => {
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
        }),
        startOrStopTimerWithButton(page)
    ])
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
        page.waitForResponse(async (response) => {
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
        }),
        page.getByTestId('time_entry_time').press('Enter')
    ])
    await assertThatTimerHasStarted(page);
    await Promise.all([
        page.waitForResponse(async (response) => {
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
        }),
        startOrStopTimerWithButton(page)
    ])
    await assertThatTimerIsStopped(page);
});

test('test that adding a new tag works', async ({page}) => {
    const newTagName = 'New Tag' + Math.floor(Math.random() * 10000);
    await goToDashboard(page);
    await page.getByTestId('tag_dropdown').click();
    await page.getByTestId('tag_dropdown_search').fill(newTagName);

    await Promise.all([
        page.waitForResponse(async (response) => {
            return (
                response.status() === 201 &&
                (await response.headerValue('Content-Type')) ===
                'application/json' &&
                (await response.json()).data.name === newTagName
            );
        }),
        page.getByTestId('tag_dropdown_search').press('Enter')
    ])

    await expect(page.getByTestId('tag_dropdown_search')).toHaveValue('');
    await expect(page.getByRole('option', {name: newTagName})).toBeVisible();
});

test('test that adding a new tag when the timer is running', async ({
                                                                        page,
                                                                    }) => {
    const newTagName = 'New Tag' + Math.floor(Math.random() * 10000);
    await goToDashboard(page);
    await Promise.all([
        newTimeEntryResponse(page),
        startOrStopTimerWithButton(page)
    ])
    await assertThatTimerHasStarted(page);
    await page.getByTestId('tag_dropdown').click();
    await page.getByTestId('tag_dropdown_search').fill(newTagName);
    const [tagCreateResponse] = await Promise.all([
        page.waitForResponse(async (response) => {
            return (
                response.status() === 201 &&
                (await response.headerValue('Content-Type')) ===
                'application/json' &&
                (await response.json()).data.name === newTagName
            );
        }),
        page.getByTestId('tag_dropdown_search').press('Enter')
    ])
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
    await expect(page.getByTestId('tag_dropdown_search')).toHaveValue('');
    await expect(page.getByRole('option', {name: newTagName})).toBeVisible();
    await page.getByTestId('tag_dropdown_search').press('Escape');
    await page.waitForTimeout(1000);

    await Promise.all([
        page.waitForResponse(async (response) => {
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
        }),
        startOrStopTimerWithButton(page)
    ])
    await assertThatTimerIsStopped(page);
});

// test that adding a new tag when the timer is running

// test that search is working
