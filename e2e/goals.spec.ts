import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import {
    createClientViaApi,
    createGoalViaApi,
    createProjectViaApi,
    createProjectMemberViaApi,
    setupTestContext,
    createRunningTimeEntryWithStartViaApi,
    createTagViaApi,
    createTaskViaApi,
    createTimeEntryViaApi,
    deleteClientViaApi,
    deleteProjectViaApi,
    deleteTagViaApi,
    deleteTaskViaApi,
    updateGoalViaApi,
    updateOrganizationSettingViaApi,
    updateUserProfileViaApi,
} from './utils/api';
import {
    selectTimezone,
    selectWeekStart,
    timezoneField,
    weekStartField,
} from './utils/userSettingsFields';

async function goToGoalsOverview(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/goals');
    await expect(page.getByTestId('goals_view')).toBeVisible();
}

function goalRow(page: Page, goalId: string) {
    return page.getByTestId('goal_row_' + goalId);
}

function goalResponse(page: Page, method: 'POST' | 'PUT' | 'DELETE', status: number) {
    return page.waitForResponse(
        (response) =>
            response.url().includes('/goals') &&
            response.request().method() === method &&
            response.status() === status
    );
}

test('test that the goals page shows an empty state and the sidebar link works', async ({
    page,
}) => {
    await page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
    await page.getByRole('link', { name: 'Goals' }).first().click();
    await expect(page).toHaveURL(/\/goals$/);
    await expect(page.getByTestId('goal_table')).toContainText('No goals found');
    await expect(page.getByRole('button', { name: 'Create your first goal' })).toBeVisible();
});

test('test that creating a goal via the modal works and shows zero progress', async ({ page }) => {
    const goalName = 'Deep work ' + Math.floor(1 + Math.random() * 10000);
    await goToGoalsOverview(page);
    await page.getByRole('button', { name: 'Create Goal' }).click();
    await page.getByPlaceholder('e.g. Deep work on Project X').fill(goalName);
    await page.getByTestId('duration_seconds_input').fill('10h');
    await page.getByTestId('duration_seconds_input').press('Tab');
    await page.getByRole('combobox', { name: 'Period' }).click();
    await page.getByRole('option', { name: 'per day' }).click();

    const [response] = await Promise.all([
        goalResponse(page, 'POST', 201),
        page.getByRole('button', { name: 'Create Goal', exact: true }).last().click(),
    ]);
    const body = await response.json();
    expect(body.data.name).toBe(goalName);
    expect(body.data.target_seconds).toBe(36000);
    expect(body.data.period).toBe('day');
    expect(body.data.comparison).toBe('at_least');
    expect(body.data.filters.time_entry_type).toBe('work');
    expect(body.data.type).toBe('personal');
    expect(body.data.is_archived).toBe(false);

    const row = goalRow(page, body.data.id);
    await expect(row).toBeVisible();
    await expect(row.getByTestId('goal_name')).toHaveText(goalName);
    await expect(row.getByTestId('goal_target_description')).toHaveText(
        'At least 10h 00min per day'
    );
    await expect(row.getByTestId('goal_tracked_time')).toHaveText('0h 00min');
    await expect(row).toContainText('of 10h 00min');
    await expect(row).toContainText('In progress');
});

test('test that the create goal modal validates name and target', async ({ page }) => {
    await goToGoalsOverview(page);
    await page.getByRole('button', { name: 'Create Goal' }).click();
    await page.getByRole('button', { name: 'Create Goal', exact: true }).last().click();
    await expect(page.getByRole('alert').filter({ hasText: 'name' })).toBeVisible();
    await expect(page.getByRole('alert').filter({ hasText: 'target time' })).toBeVisible();
});

test('test that goal progress reflects tracked time entries that match the filters', async ({
    page,
    ctx,
}) => {
    const project = await createProjectViaApi(ctx, { name: 'Goal Project', is_public: true });
    const otherProject = await createProjectViaApi(ctx, { name: 'Other Project', is_public: true });
    await createTimeEntryViaApi(ctx, { duration: '2h', projectId: project.id });
    await createTimeEntryViaApi(ctx, { duration: '1h 30min', projectId: otherProject.id });
    await createTimeEntryViaApi(ctx, { duration: '45min' });

    const projectGoal = await createGoalViaApi(ctx, {
        name: 'Project goal',
        target_seconds: 4 * 3600,
        period: 'day',
        filters: { project_ids: [project.id], time_entry_type: 'work' },
    });
    const allGoal = await createGoalViaApi(ctx, {
        name: 'All time goal',
        target_seconds: 4 * 3600,
        period: 'day',
    });
    const lessThanGoal = await createGoalViaApi(ctx, {
        name: 'Less than goal',
        comparison: 'less_than',
        target_seconds: 3600,
        period: 'day',
        filters: { project_ids: [project.id], time_entry_type: 'work' },
    });

    expect(projectGoal.progress.tracked_seconds).toBe(2 * 3600);
    expect(allGoal.progress.tracked_seconds).toBe(2 * 3600 + 90 * 60 + 45 * 60);
    expect(lessThanGoal.progress.status).toBe('exceeded');

    await goToGoalsOverview(page);
    await expect(goalRow(page, projectGoal.id).getByTestId('goal_tracked_time')).toHaveText(
        '2h 00min'
    );
    await expect(goalRow(page, projectGoal.id)).toContainText('Goal Project');
    await expect(goalRow(page, projectGoal.id)).toContainText('In progress');
    await expect(goalRow(page, allGoal.id).getByTestId('goal_tracked_time')).toHaveText('4h 15min');
    await expect(goalRow(page, allGoal.id)).toContainText('Achieved');
    await expect(goalRow(page, allGoal.id)).toContainText('All time entries');
    await expect(goalRow(page, lessThanGoal.id)).toContainText('Exceeded');
    await expect(goalRow(page, lessThanGoal.id)).toContainText('Less than 1h 00min per day');
});

async function openCreateGoalModal(page: Page, name: string) {
    await goToGoalsOverview(page);
    await page.getByRole('button', { name: 'Create Goal' }).click();
    await page.getByPlaceholder('e.g. Deep work on Project X').fill(name);
    await page.getByTestId('duration_seconds_input').fill('10h');
    await page.getByTestId('duration_seconds_input').press('Tab');
}

async function submitCreateGoalModal(page: Page) {
    const [response] = await Promise.all([
        goalResponse(page, 'POST', 201),
        page.getByRole('button', { name: 'Create Goal', exact: true }).last().click(),
    ]);
    return (await response.json()).data;
}

async function selectGoalFilterOption(
    page: Page,
    filter: string,
    searchPlaceholder: string,
    option: string
) {
    await page.getByTestId('goal_filter_' + filter).click();
    await page.getByRole('option', { name: option }).click();
    await page.getByPlaceholder(searchPlaceholder).press('Escape');
}

test('test that the task filter of a goal only counts entries of the selected tasks', async ({
    page,
    ctx,
}) => {
    const project = await createProjectViaApi(ctx, {
        name: 'Task filter project',
        is_public: true,
    });
    const task = await createTaskViaApi(ctx, { name: 'Counted task', project_id: project.id });
    const otherTask = await createTaskViaApi(ctx, { name: 'Other task', project_id: project.id });
    await createTimeEntryViaApi(ctx, { duration: '1h', projectId: project.id, taskId: task.id });
    await createTimeEntryViaApi(ctx, {
        duration: '2h',
        projectId: project.id,
        taskId: otherTask.id,
    });
    await createTimeEntryViaApi(ctx, { duration: '30min', projectId: project.id });

    await openCreateGoalModal(page, 'Task goal');
    await selectGoalFilterOption(page, 'task_ids', 'Search for a Task...', 'Counted task');
    await expect(page.getByTestId('goal_filter_task_ids')).toHaveText(/Tasks\s*1/);
    const goal = await submitCreateGoalModal(page);

    expect(goal.filters.task_ids).toEqual([task.id]);
    expect(goal.progress.tracked_seconds).toBe(3600);
    await expect(goalRow(page, goal.id).getByTestId('goal_tracked_time')).toHaveText('1h 00min');
});

test('test that the client filter of a goal only counts entries of projects of the selected clients', async ({
    page,
    ctx,
}) => {
    const client = await createClientViaApi(ctx, { name: 'Counted client' });
    const otherClient = await createClientViaApi(ctx, { name: 'Other client' });
    const project = await createProjectViaApi(ctx, {
        name: 'Client project',
        client_id: client.id,
        is_public: true,
    });
    const otherProject = await createProjectViaApi(ctx, {
        name: 'Other client project',
        client_id: otherClient.id,
        is_public: true,
    });
    await createTimeEntryViaApi(ctx, { duration: '1h', projectId: project.id });
    await createTimeEntryViaApi(ctx, { duration: '2h', projectId: otherProject.id });
    await createTimeEntryViaApi(ctx, { duration: '30min' });

    await openCreateGoalModal(page, 'Client goal');
    await selectGoalFilterOption(page, 'client_ids', 'Search for a Client...', 'Counted client');
    await expect(page.getByTestId('goal_filter_client_ids')).toHaveText(/Clients\s*1/);
    const goal = await submitCreateGoalModal(page);

    expect(goal.filters.client_ids).toEqual([client.id]);
    expect(goal.progress.tracked_seconds).toBe(3600);
    await expect(goalRow(page, goal.id).getByTestId('goal_tracked_time')).toHaveText('1h 00min');
});

test('test that the tag filter of a goal respects the tag match type', async ({ page, ctx }) => {
    const tag = await createTagViaApi(ctx, { name: 'Counted tag' });
    const otherTag = await createTagViaApi(ctx, { name: 'Other tag' });
    await createTimeEntryViaApi(ctx, { duration: '1h', tags: [tag.id] });
    await createTimeEntryViaApi(ctx, { duration: '2h', tags: [otherTag.id] });
    await createTimeEntryViaApi(ctx, { duration: '30min' });

    await openCreateGoalModal(page, 'Contains tag goal');
    await selectGoalFilterOption(page, 'tag_ids', 'Search for a Tag...', 'Counted tag');
    await expect(page.getByTestId('goal_filter_tag_ids')).toHaveText(/Tags\s*1/);
    const containsGoal = await submitCreateGoalModal(page);
    expect(containsGoal.filters.tag_ids).toEqual([tag.id]);
    expect(containsGoal.filters.tag_match_type).toBe('contains');
    expect(containsGoal.progress.tracked_seconds).toBe(3600);

    await openCreateGoalModal(page, 'Not contains tag goal');
    await page.getByTestId('goal_filter_tag_ids').click();
    await page.getByRole('radio', { name: 'Does Not Contain' }).click();
    await page.getByRole('option', { name: 'Counted tag' }).click();
    await page.getByPlaceholder('Search for a Tag...').press('Escape');
    const notContainsGoal = await submitCreateGoalModal(page);
    expect(notContainsGoal.filters.tag_ids).toEqual([tag.id]);
    expect(notContainsGoal.filters.tag_match_type).toBe('not_contains');
    // Entries with other tags and entries without tags count
    expect(notContainsGoal.progress.tracked_seconds).toBe(2 * 3600 + 30 * 60);

    await expect(goalRow(page, containsGoal.id).getByTestId('goal_tracked_time')).toHaveText(
        '1h 00min'
    );
    await expect(goalRow(page, notContainsGoal.id).getByTestId('goal_tracked_time')).toHaveText(
        '2h 30min'
    );
});

test('test that the billable filter of a goal only counts entries with the selected billable status', async ({
    page,
    ctx,
}) => {
    await createTimeEntryViaApi(ctx, { duration: '1h', billable: true });
    await createTimeEntryViaApi(ctx, { duration: '2h', billable: false });

    await openCreateGoalModal(page, 'Billable goal');
    await page.getByRole('combobox').filter({ hasText: 'Billable' }).click();
    await page.getByRole('option', { name: 'Billable', exact: true }).click();
    const billableGoal = await submitCreateGoalModal(page);
    expect(billableGoal.filters.billable).toBe(true);
    expect(billableGoal.progress.tracked_seconds).toBe(3600);

    await openCreateGoalModal(page, 'Non billable goal');
    await page.getByRole('combobox').filter({ hasText: 'Billable' }).click();
    await page.getByRole('option', { name: 'Non Billable' }).click();
    const nonBillableGoal = await submitCreateGoalModal(page);
    expect(nonBillableGoal.filters.billable).toBe(false);
    expect(nonBillableGoal.progress.tracked_seconds).toBe(2 * 3600);

    await expect(goalRow(page, billableGoal.id).getByTestId('goal_tracked_time')).toHaveText(
        '1h 00min'
    );
    await expect(goalRow(page, nonBillableGoal.id).getByTestId('goal_tracked_time')).toHaveText(
        '2h 00min'
    );
});

test('test that the type filter of a goal only counts entries of the selected type', async ({
    page,
    ctx,
}) => {
    await updateOrganizationSettingViaApi(ctx, { breaks_enabled: true });
    await createTimeEntryViaApi(ctx, { duration: '1h' });
    await createTimeEntryViaApi(ctx, { duration: '30min', type: 'break' });

    await openCreateGoalModal(page, 'Break goal');
    await page.getByRole('combobox').filter({ hasText: 'Work time' }).click();
    await page.getByRole('option', { name: 'Breaks' }).click();
    const breakGoal = await submitCreateGoalModal(page);
    expect(breakGoal.filters.time_entry_type).toBe('break');
    expect(breakGoal.progress.tracked_seconds).toBe(30 * 60);

    await openCreateGoalModal(page, 'Work and break goal');
    await page.getByRole('combobox').filter({ hasText: 'Work time' }).click();
    await page.getByRole('option', { name: 'Both' }).click();
    const bothGoal = await submitCreateGoalModal(page);
    expect(bothGoal.filters.time_entry_type).toBeNull();
    expect(bothGoal.progress.tracked_seconds).toBe(3600 + 30 * 60);

    await expect(goalRow(page, breakGoal.id).getByTestId('goal_tracked_time')).toHaveText(
        '0h 30min'
    );
    await expect(goalRow(page, bothGoal.id).getByTestId('goal_tracked_time')).toHaveText(
        '1h 30min'
    );
});

test('test that a running time entry counts towards the goal progress', async ({ page, ctx }) => {
    const start = new Date(Date.now() - 30 * 60 * 1000).toISOString().replace(/\.\d{3}Z$/, 'Z');
    await createRunningTimeEntryWithStartViaApi(ctx, 'Running work', start);
    // Close to midnight UTC the entry would start on the previous day, so the day of the goal
    // is moved to a timezone (without daylight saving time) where it is late morning instead
    const utcHour = new Date().getUTCHours();
    const goal = await createGoalViaApi(ctx, {
        name: 'Running goal',
        target_seconds: 3600,
        period: 'day',
        timezone: utcHour >= 2 && utcHour < 22 ? 'UTC' : 'Asia/Tokyo',
    });
    expect(goal.progress.tracked_seconds).toBeGreaterThanOrEqual(30 * 60);

    await goToGoalsOverview(page);
    // The entry keeps running while the page loads
    await expect(goalRow(page, goal.id).getByTestId('goal_tracked_time')).toHaveText(
        /^0h 3[0-2]min$/
    );
});

test('test that editing a goal via the modal works', async ({ page, ctx }) => {
    const goal = await createGoalViaApi(ctx, {
        name: 'Old goal name',
        target_seconds: 3600,
        period: 'week',
    });
    await goToGoalsOverview(page);
    await page.locator("[aria-label='Actions for Goal Old goal name']").click();
    await page.locator("[aria-label='Edit Goal Old goal name']").click();
    await page.getByPlaceholder('e.g. Deep work on Project X').fill('New goal name');
    await page.getByRole('combobox', { name: 'Target type' }).click();
    await page.getByRole('option', { name: 'Less than' }).click();

    const [response] = await Promise.all([
        goalResponse(page, 'PUT', 200),
        page.getByRole('button', { name: 'Update Goal' }).click(),
    ]);
    const body = await response.json();
    expect(body.data.name).toBe('New goal name');
    expect(body.data.comparison).toBe('less_than');

    const row = goalRow(page, goal.id);
    await expect(row.getByTestId('goal_name')).toHaveText('New goal name');
    await expect(row).toContainText('Less than 1h 00min per week');
    await expect(row).toContainText('On track');
});

test('test that a goal whose filters point to deleted entities can still be edited', async ({
    page,
    ctx,
}) => {
    const keptProject = await createProjectViaApi(ctx, { name: 'Kept project' });
    const deletedProject = await createProjectViaApi(ctx, { name: 'Deleted project' });
    const keptTask = await createTaskViaApi(ctx, { name: 'Kept task', project_id: keptProject.id });
    const deletedTask = await createTaskViaApi(ctx, {
        name: 'Deleted task',
        project_id: keptProject.id,
    });
    const keptTag = await createTagViaApi(ctx, { name: 'Kept tag' });
    const deletedTag = await createTagViaApi(ctx, { name: 'Deleted tag' });
    const deletedClient = await createClientViaApi(ctx, { name: 'Deleted client' });
    const filters = {
        project_ids: [keptProject.id, deletedProject.id],
        task_ids: [keptTask.id, deletedTask.id],
        // "none" is no entity, it has to survive the cleanup
        tag_ids: ['none', keptTag.id, deletedTag.id],
        // Every client of this filter gets deleted
        client_ids: [deletedClient.id],
        time_entry_type: 'work' as const,
    };
    const goal = await createGoalViaApi(ctx, {
        name: 'Stale filters',
        target_seconds: 3600,
        filters,
    });
    await deleteTaskViaApi(ctx, deletedTask.id);
    await deleteProjectViaApi(ctx, deletedProject.id);
    await deleteTagViaApi(ctx, deletedTag.id);
    await deleteClientViaApi(ctx, deletedClient.id);

    // Resending deleted IDs fails; unrelated edits can omit filters to preserve the scope.
    const rejected = await updateGoalViaApi(ctx, goal.id, { filters });
    expect(rejected.status()).toBe(422);
    const errors = (await rejected.json()).errors;
    expect(Object.keys(errors).sort()).toEqual([
        'filters.client_ids.0',
        'filters.project_ids.1',
        'filters.tag_ids.2',
        'filters.task_ids.1',
    ]);

    await goToGoalsOverview(page);
    await page.locator("[aria-label='Actions for Goal Stale filters']").click();
    await page.locator("[aria-label='Edit Goal Stale filters']").click();
    // Opening the form must preserve the scope, even after lists have loaded.
    await expect(page.getByTestId('goal_filter_project_ids')).toHaveText(/Projects\s*2/);
    await expect(page.getByTestId('goal_filter_task_ids')).toHaveText(/Tasks\s*2/);
    await expect(page.getByTestId('goal_filter_tag_ids')).toHaveText(/Tags\s*3/);
    await expect(page.getByTestId('goal_filter_client_ids')).toHaveText(/Clients\s*1/);
    await page.getByPlaceholder('e.g. Deep work on Project X').fill('Preserved filters');
    await page.getByRole('button', { name: 'Update Goal' }).click();
    await expect(page.getByTestId('goal_unavailable_filters')).toContainText(
        'This will count all clients'
    );
    const [preservedResponse] = await Promise.all([
        goalResponse(page, 'PUT', 200),
        page.getByRole('button', { name: 'Keep existing filters' }).click(),
    ]);
    expect(preservedResponse.request().postDataJSON()).not.toHaveProperty('filters');
    expect((await preservedResponse.json()).data.filters).toMatchObject(filters);

    await page.locator("[aria-label='Actions for Goal Preserved filters']").click();
    await page.locator("[aria-label='Edit Goal Preserved filters']").click();
    await page.getByPlaceholder('e.g. Deep work on Project X').fill('Cleaned filters');
    await page.getByRole('button', { name: 'Update Goal' }).click();
    await expect(page.getByTestId('goal_unavailable_filters')).toBeVisible();
    const [response] = await Promise.all([
        goalResponse(page, 'PUT', 200),
        page.getByRole('button', { name: 'Remove unavailable items' }).click(),
    ]);
    const body = await response.json();
    expect(body.data.name).toBe('Cleaned filters');
    expect(body.data.filters.project_ids).toEqual([keptProject.id]);
    expect(body.data.filters.task_ids).toEqual([keptTask.id]);
    expect(body.data.filters.tag_ids).toEqual(['none', keptTag.id]);
    // A filter whose entities are all gone is dropped, the goal no longer filters by client
    expect(body.data.filters.client_ids).toBeNull();
    await expect(goalRow(page, goal.id).getByTestId('goal_name')).toHaveText('Cleaned filters');
});

test('losing access to a private project warns without silently changing a personal goal', async ({
    ctx,
    employee,
}) => {
    test.setTimeout(120 * 1000);
    const project = await createProjectViaApi(ctx, {
        name: 'Private goal project',
        is_public: false,
    });
    const membership = await createProjectMemberViaApi(ctx, project.id, {
        member_id: employee.memberId,
    });
    const employeeCtx = {
        ...(await setupTestContext(employee.page)),
        orgId: ctx.orgId,
        memberId: employee.memberId,
    };
    const goal = await createGoalViaApi(employeeCtx, {
        name: 'Private project goal',
        target_seconds: 3600,
        filters: { project_ids: [project.id], time_entry_type: 'work' },
    });
    const removed = await ctx.request.delete(
        `${PLAYWRIGHT_BASE_URL}/api/v1/organizations/${ctx.orgId}/project-members/${membership.id}`
    );
    expect(removed.status()).toBe(204);
    // The project still exists, but the employee's selectable list no longer contains it.
    const projects = await employeeCtx.request.get(
        `${PLAYWRIGHT_BASE_URL}/api/v1/organizations/${ctx.orgId}/projects?archived=all`
    );
    expect(projects.status()).toBe(200);
    expect((await projects.json()).data.map((item: { id: string }) => item.id)).not.toContain(
        project.id
    );

    const page = employee.page;
    await goToGoalsOverview(page);
    await page.locator("[aria-label='Actions for Goal Private project goal']").click();
    await page.locator("[aria-label='Edit Goal Private project goal']").click();
    await expect(page.getByTestId('goal_filter_project_ids')).toHaveText(/Projects\s*1/);
    await page.getByPlaceholder('e.g. Deep work on Project X').fill('Still scoped');
    await page.getByRole('button', { name: 'Update Goal' }).click();
    await expect(page.getByTestId('goal_unavailable_filters')).toContainText(
        'Unavailable projects: 1'
    );
    await expect(page.getByTestId('goal_unavailable_filters')).toContainText(
        'This will count all projects'
    );
    await page.getByRole('button', { name: 'Back', exact: true }).click();
    await expect(page.getByTestId('goal_filter_project_ids')).toHaveText(/Projects\s*1/);
    await page.getByRole('button', { name: 'Update Goal' }).click();
    const [response] = await Promise.all([
        goalResponse(page, 'PUT', 200),
        page.getByRole('button', { name: 'Keep existing filters' }).click(),
    ]);
    expect(response.request().postDataJSON()).not.toHaveProperty('filters');
    expect((await response.json()).data.filters.project_ids).toEqual([project.id]);
    await expect(goalRow(page, goal.id).getByTestId('goal_name')).toHaveText('Still scoped');
});

test('test that archiving a goal moves it to the archived tab and back', async ({ page, ctx }) => {
    const goal = await createGoalViaApi(ctx, {
        name: 'Goal to archive',
        target_seconds: 3600,
    });
    await goToGoalsOverview(page);
    await expect(goalRow(page, goal.id)).toBeVisible();

    await page.locator("[aria-label='Actions for Goal Goal to archive']").click();
    await Promise.all([
        goalResponse(page, 'PUT', 200),
        page.locator("[aria-label='Archive Goal Goal to archive']").click(),
    ]);
    await expect(goalRow(page, goal.id)).not.toBeVisible();
    await expect(page.getByTestId('goal_table')).toContainText('No goals found');

    await page.getByRole('tab', { name: 'Archived' }).click();
    const archivedRow = goalRow(page, goal.id);
    await expect(archivedRow).toBeVisible();
    await expect(archivedRow.getByTestId('goal_archived_badge')).toBeVisible();

    await page.locator("[aria-label='Actions for Goal Goal to archive']").click();
    await Promise.all([
        goalResponse(page, 'PUT', 200),
        page.locator("[aria-label='Unarchive Goal Goal to archive']").click(),
    ]);
    await expect(goalRow(page, goal.id)).not.toBeVisible();
    await page.getByRole('tab', { name: 'Active' }).click();
    await expect(goalRow(page, goal.id)).toBeVisible();
});

test('test that the period settings of a goal can be changed in the accordion', async ({
    page,
    ctx,
}) => {
    const goal = await createGoalViaApi(ctx, {
        name: 'Goal with timezone',
        target_seconds: 3600,
        period: 'week',
        timezone: 'Asia/Tokyo',
        week_start: 'friday',
    });
    await goToGoalsOverview(page);
    await page.locator("[aria-label='Actions for Goal Goal with timezone']").click();
    await page.locator("[aria-label='Edit Goal Goal with timezone']").click();
    await page.getByTestId('goal_period_settings').click();
    const dialog = page.getByRole('dialog');
    await expect(timezoneField(dialog)).toHaveText('Asia/Tokyo');
    await expect(weekStartField(dialog)).toHaveText('Friday');

    await selectTimezone(page, dialog, 'America/New_York', 'new york');
    await selectWeekStart(page, dialog, 'Sunday');

    const [response] = await Promise.all([
        goalResponse(page, 'PUT', 200),
        page.getByRole('button', { name: 'Update Goal' }).click(),
    ]);
    const body = await response.json();
    expect(body.data.id).toBe(goal.id);
    expect(body.data.timezone).toBe('America/New_York');
    expect(body.data.week_start).toBe('sunday');
});

test('test that the period settings of a new goal default to the user settings', async ({
    page,
    ctx,
}) => {
    // Same UTC offset as the browser, so the timezone mismatch modal stays closed
    await updateUserProfileViaApi(ctx, { timezone: 'Atlantic/Reykjavik', week_start: 'wednesday' });
    await goToGoalsOverview(page);
    await page.getByRole('button', { name: 'Create Goal' }).click();
    await page.getByPlaceholder('e.g. Deep work on Project X').fill('Goal with defaults');
    await page.getByTestId('duration_seconds_input').fill('10h');
    await page.getByTestId('duration_seconds_input').press('Tab');
    await page.getByTestId('goal_period_settings').click();
    await expect(timezoneField(page.getByRole('dialog'))).toHaveText('Atlantic/Reykjavik');
    await expect(weekStartField(page.getByRole('dialog'))).toHaveText('Wednesday');

    const [response] = await Promise.all([
        goalResponse(page, 'POST', 201),
        page.getByRole('button', { name: 'Create Goal', exact: true }).last().click(),
    ]);
    const body = await response.json();
    expect(body.data.timezone).toBe('Atlantic/Reykjavik');
    expect(body.data.week_start).toBe('wednesday');
});

test('test that the period settings of a new goal can be set in the create modal', async ({
    page,
}) => {
    await goToGoalsOverview(page);
    await page.getByRole('button', { name: 'Create Goal' }).click();
    await page.getByPlaceholder('e.g. Deep work on Project X').fill('Goal in New York');
    await page.getByTestId('duration_seconds_input').fill('10h');
    await page.getByTestId('duration_seconds_input').press('Tab');
    await page.getByTestId('goal_period_settings').click();
    const dialog = page.getByRole('dialog');
    await selectTimezone(page, dialog, 'America/New_York', 'new york');
    await selectWeekStart(page, dialog, 'Saturday');

    const [response] = await Promise.all([
        goalResponse(page, 'POST', 201),
        page.getByRole('button', { name: 'Create Goal', exact: true }).last().click(),
    ]);
    const body = await response.json();
    expect(body.data.name).toBe('Goal in New York');
    expect(body.data.timezone).toBe('America/New_York');
    expect(body.data.week_start).toBe('saturday');
});

test('test that the search of a goal filter can be used with the keyboard', async ({
    page,
    ctx,
}) => {
    await createProjectViaApi(ctx, { name: 'Alpha keyboard project', is_public: true });
    await createProjectViaApi(ctx, { name: 'Beta keyboard project', is_public: true });
    await goToGoalsOverview(page);
    await page.getByRole('button', { name: 'Create Goal' }).click();
    await page.getByTestId('goal_filter_project_ids').click();

    const search = page.getByPlaceholder('Search for a Project...');
    await search.click();
    await search.pressSequentially('alpha');
    await expect(search).toHaveValue('alpha');
    await expect(page.getByRole('option', { name: 'Beta keyboard project' })).toBeHidden();

    // The arrow keys highlight an option while the focus stays in the search
    const alpha = page.getByRole('option', { name: 'Alpha keyboard project' });
    await search.press('ArrowDown');
    await expect(alpha).toHaveAttribute('data-highlighted', '');
    await search.press('Enter');
    await expect(search).toBeFocused();
    await expect(page.getByTestId('goal_filter_project_ids')).toHaveText(/Projects\s*1/);

    // Escape closes the dropdown but keeps the modal and the selection
    await search.press('Escape');
    await expect(search).toBeHidden();
    await expect(page.getByPlaceholder('e.g. Deep work on Project X')).toBeVisible();
    await expect(page.getByTestId('goal_filter_project_ids')).toHaveText(/Projects\s*1/);
});

test('test that deleting a goal via the more options dropdown works', async ({ page, ctx }) => {
    const goal = await createGoalViaApi(ctx, {
        name: 'Goal to delete',
        target_seconds: 3600,
    });
    await goToGoalsOverview(page);
    await expect(goalRow(page, goal.id)).toBeVisible();
    await page.locator("[aria-label='Actions for Goal Goal to delete']").click();
    await Promise.all([
        goalResponse(page, 'DELETE', 204),
        page.locator("[aria-label='Delete Goal Goal to delete']").click(),
    ]);
    await expect(goalRow(page, goal.id)).not.toBeVisible();
    await expect(page.getByTestId('goal_table')).toContainText('No goals found');
});

test.describe('employee', () => {
    test('employee can create and see their own goals', async ({ employee }) => {
        await goToGoalsOverview(employee.page);
        await employee.page.getByRole('button', { name: 'Create Goal' }).click();
        await employee.page.getByPlaceholder('e.g. Deep work on Project X').fill('Employee goal');
        await employee.page.getByTestId('duration_seconds_input').fill('2h');
        await employee.page.getByTestId('duration_seconds_input').press('Tab');
        const [response] = await Promise.all([
            goalResponse(employee.page, 'POST', 201),
            employee.page.getByRole('button', { name: 'Create Goal', exact: true }).last().click(),
        ]);
        const body = await response.json();
        expect(body.data.type).toBe('personal');
        expect(body.data.member_id).toBe(employee.memberId);
        await expect(goalRow(employee.page, body.data.id)).toContainText('Employee goal');
    });
});
