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
import {
    createProjectViaApi,
    createTaskViaApi,
    createClientViaApi,
    archiveProjectViaApi,
    markTaskDoneViaApi,
    updateOrganizationCurrencyViaWeb,
} from './utils/api';

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

test('test that billable icon shows dollar sign for USD currency', async ({ page, ctx }) => {
    await updateOrganizationCurrencyViaWeb(page, ctx, 'USD');
    await goToDashboard(page);
    await page.waitForLoadState('networkidle');
    const billableButton = page.getByRole('button', { name: 'Non Billable' }).first();
    await expect(billableButton).toBeVisible();
    await expect(billableButton.locator('svg')).toHaveAttribute('viewBox', '0 0 8 14');
});

test('test that billable icon shows euro sign for EUR currency', async ({ page, ctx }) => {
    await updateOrganizationCurrencyViaWeb(page, ctx, 'EUR');
    await goToDashboard(page);
    await page.waitForLoadState('networkidle');
    const billableButton = page.getByRole('button', { name: 'Non Billable' }).first();
    await expect(billableButton).toBeVisible();
    await expect(billableButton.locator('svg')).toHaveAttribute('viewBox', '0 0 12 12');
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
    const calendarGrid = page.getByRole('gridcell').first();
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
        page
            .getByTestId('dashboard_timer')
            .getByTestId('timer_button')
            .and(page.locator(':visible'))
    ).toHaveClass(/bg-red-400\/80/);

    // Stop the timer
    await Promise.all([
        stoppedTimeEntryResponse(page, { description: 'Sync test' }),
        startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerIsStopped(page);
});

test('test that creating a new project from the time tracker dropdown prefills the search text', async ({
    page,
    ctx,
}) => {
    const existingProjectName = 'Existing Project ' + Math.floor(Math.random() * 10000);
    const searchText = 'PrefillProject ' + Math.floor(Math.random() * 10000);

    // Create a project so the dropdown renders (not the "Add new project" button)
    await createProjectViaApi(ctx, { name: existingProjectName });
    await goToDashboard(page);

    // Open the project dropdown
    await page.getByRole('button', { name: 'No Project' }).click();

    // Type a search term that won't match any existing project
    await page.getByTestId('client_dropdown_search').fill(searchText);

    // Click "Create new Project"
    await page.getByText('Create new Project').click();

    // Verify the project name input is pre-filled with the search text
    await expect(page.getByLabel('Project name')).toHaveValue(searchText);

    // Complete project creation to verify full flow works
    await Promise.all([
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects') &&
                response.request().method() === 'POST' &&
                response.status() === 201 &&
                (await response.json()).data.name === searchText
        ),
        page.getByRole('button', { name: 'Create Project' }).click(),
    ]);

    // The project dropdown should now show the newly created project
    await expect(page.getByRole('button', { name: searchText })).toBeVisible();
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

// ──────────────────────────────────────────────────
// Project / Task selector dropdown
// Regression coverage for the virtualized + lookup-map refactor of
// TimeTrackerProjectTaskDropdown. The dropdown only (re)filters on open and on search
// change, so we wait for the dashboard prefetch to settle before opening it.
// ──────────────────────────────────────────────────

test.describe('Project Task Dropdown', () => {
    test.describe.configure({ timeout: 60_000 });

    test('test that a project far down a long list can be found via search and selected', async ({
        page,
        ctx,
    }) => {
        // Seed enough projects that the target sits outside the initially rendered window.
        const seed = Math.floor(Math.random() * 100000);
        const prefix = `VirtProj ${seed} `;
        await Promise.all(
            Array.from({ length: 30 }, (_, i) =>
                createProjectViaApi(ctx, { name: prefix + String(i).padStart(2, '0') })
            )
        );
        const target = prefix + '27';

        await goToDashboard(page);
        await page.waitForLoadState('networkidle');

        await page.getByRole('button', { name: 'No Project' }).click();
        await page.getByTestId('client_dropdown_search').fill(target);
        await page.getByRole('option').filter({ hasText: target }).click();

        // The trigger now reflects the selected project.
        await expect(page.getByRole('button', { name: target })).toBeVisible();
    });

    test('test that expanding a project and selecting a task works', async ({ page, ctx }) => {
        const seed = Math.floor(Math.random() * 100000);
        const projectName = `ExpandProj ${seed}`;
        const taskName = `ExpandTask ${seed}`;
        const project = await createProjectViaApi(ctx, { name: projectName });
        await createTaskViaApi(ctx, { name: taskName, project_id: project.id });

        await goToDashboard(page);
        await page.waitForLoadState('networkidle');

        await page.getByRole('button', { name: 'No Project' }).click();
        const projectOption = page.getByRole('option').filter({ hasText: projectName });
        await expect(projectOption).toBeVisible();

        // Expand the project's tasks via the "N Tasks" button, then select the task.
        await projectOption.getByText(/Tasks/).click();
        await page.getByText(taskName, { exact: true }).click();

        // Scoped to the trigger button: the closing dropdown also contains the name while animating out.
        await expect(
            page.getByRole('button', { name: `${projectName} ${taskName}` })
        ).toBeVisible();
    });

    test('test that keyboard navigation selects a project', async ({ page, ctx }) => {
        const seed = Math.floor(Math.random() * 100000);
        const projectName = `KbProj ${seed}`;
        await createProjectViaApi(ctx, { name: projectName });

        await goToDashboard(page);
        await page.waitForLoadState('networkidle');

        await page.getByRole('button', { name: 'No Project' }).click();
        const search = page.getByTestId('client_dropdown_search');
        // On open the search is focused and "No Project" is highlighted.
        await expect(search).toBeFocused();

        // Arrow down from "No Project" to the project, then select it with Enter.
        await search.press('ArrowDown');
        await search.press('Enter');

        await expect(page.getByRole('button', { name: projectName })).toBeVisible();
    });

    test('test that search filters the dropdown by project and client name', async ({
        page,
        ctx,
    }) => {
        const seed = Math.floor(Math.random() * 100000);
        const clientName = `FilterClient ${seed}`;
        const alphaProject = `AlphaProj ${seed}`;
        const betaProject = `BetaProj ${seed}`;
        const client = await createClientViaApi(ctx, { name: clientName });
        await createProjectViaApi(ctx, { name: alphaProject, client_id: client.id });
        await createProjectViaApi(ctx, { name: betaProject });

        await goToDashboard(page);
        await page.waitForLoadState('networkidle');

        await page.getByRole('button', { name: 'No Project' }).click();
        const search = page.getByTestId('client_dropdown_search');
        const alphaOption = page.getByRole('option').filter({ hasText: alphaProject });
        const betaOption = page.getByRole('option').filter({ hasText: betaProject });

        // Both projects are visible before filtering.
        await expect(alphaOption).toBeVisible();
        await expect(betaOption).toBeVisible();

        // Project-name search shows only the matching project.
        await search.fill('AlphaProj');
        await expect(alphaOption).toBeVisible();
        await expect(betaOption).not.toBeVisible();

        // Client-name search shows the project that belongs to that client.
        await search.fill(clientName);
        await expect(alphaOption).toBeVisible();
        await expect(betaOption).not.toBeVisible();
    });

    test("test that searching by task name surfaces the task's project", async ({ page, ctx }) => {
        const seed = Math.floor(Math.random() * 100000);
        const projectWithTask = `TaskSearchProj ${seed}`;
        const taskName = `Findable Task ${seed}`;
        const unrelatedProject = `Unrelated Proj ${seed}`;
        const project = await createProjectViaApi(ctx, { name: projectWithTask });
        await createTaskViaApi(ctx, { name: taskName, project_id: project.id });
        await createProjectViaApi(ctx, { name: unrelatedProject });

        await goToDashboard(page);
        await page.waitForLoadState('networkidle');

        await page.getByRole('button', { name: 'No Project' }).click();
        await page.getByTestId('client_dropdown_search').fill(taskName);

        // The project owning the task is shown (with the task), the unrelated project is not.
        await expect(page.getByRole('option').filter({ hasText: projectWithTask })).toBeVisible();
        await expect(page.getByText(taskName, { exact: true })).toBeVisible();
        await expect(
            page.getByRole('option').filter({ hasText: unrelatedProject })
        ).not.toBeVisible();
    });

    test('test that archived projects are hidden from the dropdown', async ({ page, ctx }) => {
        const seed = Math.floor(Math.random() * 100000);
        const activeProject = `ActiveProj ${seed}`;
        const archivedProject = `ArchivedProj ${seed}`;
        await createProjectViaApi(ctx, { name: activeProject });
        const toArchive = await createProjectViaApi(ctx, { name: archivedProject });
        await archiveProjectViaApi(ctx, toArchive);

        await goToDashboard(page);
        await page.waitForLoadState('networkidle');

        await page.getByRole('button', { name: 'No Project' }).click();

        // Wait for the list to load, then confirm the archived project is filtered out.
        await expect(page.getByRole('option').filter({ hasText: activeProject })).toBeVisible();
        await expect(
            page.getByRole('option').filter({ hasText: archivedProject })
        ).not.toBeVisible();
    });

    test('test that done tasks are hidden when expanding a project', async ({ page, ctx }) => {
        const seed = Math.floor(Math.random() * 100000);
        const projectName = `DoneTaskProj ${seed}`;
        const activeTask = `Active Task ${seed}`;
        const doneTask = `Done Task ${seed}`;
        const project = await createProjectViaApi(ctx, { name: projectName });
        await createTaskViaApi(ctx, { name: activeTask, project_id: project.id });
        const taskToFinish = await createTaskViaApi(ctx, {
            name: doneTask,
            project_id: project.id,
        });
        await markTaskDoneViaApi(ctx, taskToFinish);

        await goToDashboard(page);
        await page.waitForLoadState('networkidle');

        await page.getByRole('button', { name: 'No Project' }).click();
        const projectOption = page.getByRole('option').filter({ hasText: projectName });
        await expect(projectOption).toBeVisible();
        await projectOption.getByText(/Tasks/).click();

        // Only the active task shows; the done task is filtered out.
        await expect(page.getByText(activeTask, { exact: true })).toBeVisible();
        await expect(page.getByText(doneTask, { exact: true })).not.toBeVisible();
    });

    test('test that keyboard navigation can expand a project and select a task', async ({
        page,
        ctx,
    }) => {
        const seed = Math.floor(Math.random() * 100000);
        const projectName = `KbTaskProj ${seed}`;
        const taskName = `KbTask ${seed}`;
        const project = await createProjectViaApi(ctx, { name: projectName });
        await createTaskViaApi(ctx, { name: taskName, project_id: project.id });

        await goToDashboard(page);
        await page.waitForLoadState('networkidle');

        await page.getByRole('button', { name: 'No Project' }).click();
        const search = page.getByTestId('client_dropdown_search');
        await expect(search).toBeFocused();

        // No Project is highlighted on open: down to the project, right to expand its tasks,
        // down to the task, Enter to select it.
        await search.press('ArrowDown');
        await search.press('ArrowRight');
        await search.press('ArrowDown');
        await search.press('Enter');

        // Scoped to the trigger button: the closing dropdown also contains the name while animating out.
        await expect(
            page.getByRole('button', { name: `${projectName} ${taskName}` })
        ).toBeVisible();
    });

    test('test that pressing space selects the highlighted project', async ({ page, ctx }) => {
        const seed = Math.floor(Math.random() * 100000);
        const projectName = `SpaceProj ${seed}`;
        await createProjectViaApi(ctx, { name: projectName });

        await goToDashboard(page);
        await page.waitForLoadState('networkidle');

        await page.getByRole('button', { name: 'No Project' }).click();
        const search = page.getByTestId('client_dropdown_search');
        await expect(search).toBeFocused();

        // Arrow down from "No Project" to the project, then the space shortcut selects it.
        await search.press('ArrowDown');
        await search.press('Space');

        await expect(page.getByRole('button', { name: projectName })).toBeVisible();
    });
});
