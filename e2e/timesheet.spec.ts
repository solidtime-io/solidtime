import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import {
    createProjectViaApi,
    createTaskViaApi,
    createTimeEntryOnDateViaApi,
    createTimeEntryWithTimestampsViaApi,
    getTimeEntriesViaApi,
    updateOrganizationSettingViaApi,
} from './utils/api';

// ──────────────────────────────────────────────────
// Helpers
// ──────────────────────────────────────────────────

async function goToTimesheet(page: Page) {
    await page.addInitScript(() => {
        window.localStorage.setItem('showReleaseInfo-desktop', 'false');
    });
    await page.goto(PLAYWRIGHT_BASE_URL + '/timesheet');
}

function getMonday(d: Date): Date {
    const date = new Date(d);
    const day = date.getUTCDay();
    const diff = date.getUTCDate() - day + (day === 0 ? -6 : 1);
    date.setUTCDate(diff);
    date.setUTCHours(0, 0, 0, 0);
    return date;
}

function getCurrentWeekMonday(): Date {
    return getMonday(new Date());
}

function getLastWeekMonday(): Date {
    const monday = getCurrentWeekMonday();
    monday.setUTCDate(monday.getUTCDate() - 7);
    return monday;
}

function getDayOfWeek(weekStart: Date, dayOffset: number): Date {
    const date = new Date(weekStart);
    date.setUTCDate(date.getUTCDate() + dayOffset);
    return date;
}

async function waitForTimesheetLoad(page: Page) {
    await page.waitForURL(/\/timesheet(?:$|\?)/);
    await expect(page.getByTestId('timesheet_view')).toBeVisible();
    await expect(page.getByTestId('timesheet_week_display')).toBeVisible();

    const timezoneMismatchModal = page
        .getByRole('dialog')
        .filter({ hasText: 'Timezone mismatch detected' });
    if (await timezoneMismatchModal.isVisible().catch(() => false)) {
        await timezoneMismatchModal.getByRole('button', { name: 'Cancel' }).click();
        await expect(timezoneMismatchModal).not.toBeVisible();
    }
}

function addRowButton(page: Page) {
    return page.getByRole('button', { name: /Add row/i }).first();
}

async function chooseRowIdentity(page: Page, optionName: string) {
    await addRowButton(page).click();

    const dialog = page.getByRole('dialog', { name: /Add row/i });
    const dialogVisible = await dialog
        .waitFor({ state: 'visible', timeout: 1000 })
        .then(() => true)
        .catch(() => false);

    if (dialogVisible) {
        await dialog.getByRole('option', { name: optionName }).click();
        return;
    }

    if (optionName === 'No Project') return;

    const row = page.locator('[data-testid="timesheet_row"]').first();
    await row.getByText('No Project').click();
    await page.getByText(optionName).click();
}

// ──────────────────────────────────────────────────
// Navigation & Page Load
// ──────────────────────────────────────────────────

test('timesheet renders empty with add row + copy last week actions', async ({ page }) => {
    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);

    await expect(page.locator('[data-testid="timesheet_row"]')).toHaveCount(0);
    await expect(addRowButton(page)).toBeVisible();
    await expect(page.getByRole('button', { name: /Copy last week/i })).toBeVisible();
});

// ──────────────────────────────────────────────────
// Display Existing Time Entries
// ──────────────────────────────────────────────────

test('timesheet displays existing time entries grouped by project', async ({ page, ctx }) => {
    const monday = getCurrentWeekMonday();
    const tuesday = getDayOfWeek(monday, 1);
    const wednesday = getDayOfWeek(monday, 2);

    const projectA = await createProjectViaApi(ctx, { name: 'Project Alpha' });
    const projectB = await createProjectViaApi(ctx, { name: 'Project Beta' });

    await createTimeEntryOnDateViaApi(ctx, {
        date: monday,
        duration: '2h',
        projectId: projectA.id,
    });
    await createTimeEntryOnDateViaApi(ctx, {
        date: wednesday,
        duration: '1h',
        projectId: projectA.id,
    });
    await createTimeEntryOnDateViaApi(ctx, {
        date: tuesday,
        duration: '3h',
        projectId: projectB.id,
    });

    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);

    const rows = page.locator('[data-testid="timesheet_row"]');
    await expect(rows).toHaveCount(2);

    // Check that the grand total is shown
    await expect(page.getByTestId('timesheet_grand_total')).toBeVisible();
});

test('timesheet groups entries by project and task combination', async ({ page, ctx }) => {
    const monday = getCurrentWeekMonday();

    const project = await createProjectViaApi(ctx, { name: 'Task Project' });
    const taskA = await createTaskViaApi(ctx, { name: 'Task A', project_id: project.id });
    const taskB = await createTaskViaApi(ctx, { name: 'Task B', project_id: project.id });

    await createTimeEntryOnDateViaApi(ctx, {
        date: monday,
        duration: '1h',
        projectId: project.id,
        taskId: taskA.id,
    });
    await createTimeEntryOnDateViaApi(ctx, {
        date: monday,
        duration: '2h',
        projectId: project.id,
        taskId: taskB.id,
    });

    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);

    const rows = page.locator('[data-testid="timesheet_row"]');
    await expect(rows).toHaveCount(2);
});

// ──────────────────────────────────────────────────
// Enter Duration in Cell
// ──────────────────────────────────────────────────

test('entering duration in empty cell creates a time entry', async ({ page, ctx }) => {
    await createProjectViaApi(ctx, { name: 'Duration Test' });

    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);

    await chooseRowIdentity(page, 'Duration Test');

    const row = page.locator('[data-testid="timesheet_row"]').first();

    // Click the first day cell and enter duration
    const cells = row.locator('[data-testid="timesheet_cell"]');
    const mondayCell = cells.first();
    const mondayInput = mondayCell.locator('input');

    await mondayInput.click();
    await mondayInput.fill('2');

    // Submit and wait for create response
    const [response] = await Promise.all([
        page.waitForResponse(
            (resp) =>
                resp.url().includes('/time-entries') &&
                resp.request().method() === 'POST' &&
                resp.status() === 201
        ),
        mondayInput.press('Enter'),
    ]);

    expect(response.status()).toBe(201);

    // Verify the cell shows the duration
    await expect(mondayInput).not.toHaveValue('');
});

// ──────────────────────────────────────────────────
// Edit Duration (Increase)
// ──────────────────────────────────────────────────

test('increasing duration in cell extends the last time entry', async ({ page, ctx }) => {
    const monday = getCurrentWeekMonday();
    const project = await createProjectViaApi(ctx, { name: 'Increase Test' });

    await createTimeEntryOnDateViaApi(ctx, {
        date: monday,
        duration: '1h',
        projectId: project.id,
    });

    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);

    const row = page.locator('[data-testid="timesheet_row"]').first();
    const cells = row.locator('[data-testid="timesheet_cell"]');
    const mondayInput = cells.first().locator('input');

    // Click and change to 3 hours
    await mondayInput.click();
    await mondayInput.fill('3');

    const [response] = await Promise.all([
        page.waitForResponse(
            (resp) =>
                resp.url().includes('/time-entries') &&
                resp.request().method() === 'PUT' &&
                resp.status() === 200
        ),
        mondayInput.press('Enter'),
    ]);

    expect(response.status()).toBe(200);
});

// ──────────────────────────────────────────────────
// Edit Duration (Decrease)
// ──────────────────────────────────────────────────

test('decreasing duration in cell shortens the last time entry', async ({ page, ctx }) => {
    const monday = getCurrentWeekMonday();
    const project = await createProjectViaApi(ctx, { name: 'Decrease Test' });

    await createTimeEntryOnDateViaApi(ctx, {
        date: monday,
        duration: '3h',
        projectId: project.id,
    });

    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);

    const row = page.locator('[data-testid="timesheet_row"]').first();
    const cells = row.locator('[data-testid="timesheet_cell"]');
    const mondayInput = cells.first().locator('input');

    await mondayInput.click();
    await mondayInput.fill('1');

    const [response] = await Promise.all([
        page.waitForResponse(
            (resp) =>
                resp.url().includes('/time-entries') &&
                resp.request().method() === 'PUT' &&
                resp.status() === 200
        ),
        mondayInput.press('Enter'),
    ]);

    expect(response.status()).toBe(200);
});

// ──────────────────────────────────────────────────
// Clear Cell
// ──────────────────────────────────────────────────

test('clearing a cell deletes all time entries for that project+day', async ({ page, ctx }) => {
    const monday = getCurrentWeekMonday();
    const project = await createProjectViaApi(ctx, { name: 'Clear Test' });

    await createTimeEntryOnDateViaApi(ctx, {
        date: monday,
        duration: '2h',
        projectId: project.id,
    });

    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);

    const row = page.locator('[data-testid="timesheet_row"]').first();
    const cells = row.locator('[data-testid="timesheet_cell"]');
    const mondayInput = cells.first().locator('input');

    await mondayInput.click();
    await mondayInput.fill('0');

    const [response] = await Promise.all([
        page.waitForResponse(
            (resp) =>
                resp.url().includes('/time-entries') &&
                resp.request().method() === 'DELETE' &&
                resp.status() === 200
        ),
        mondayInput.press('Enter'),
    ]);

    expect(response.status()).toBe(200);
});

test('Escape during cell edit reverts the displayed value without an API call', async ({
    page,
    ctx,
}) => {
    const monday = getCurrentWeekMonday();
    const project = await createProjectViaApi(ctx, { name: 'Escape Cancel Test' });

    await createTimeEntryOnDateViaApi(ctx, {
        date: monday,
        duration: '2h',
        projectId: project.id,
    });

    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);

    const row = page.locator('[data-testid="timesheet_row"]').first();
    const cells = row.locator('[data-testid="timesheet_cell"]');
    const mondayInput = cells.first().locator('input');

    // Capture the formatted display value before editing.
    const originalValue = await mondayInput.inputValue();
    expect(originalValue).toMatch(/2/);

    let mutationFired = false;
    page.on('request', (req) => {
        if (req.url().includes('/time-entries') && req.method() !== 'GET') {
            mutationFired = true;
        }
    });

    await mondayInput.click();
    await mondayInput.fill('5');
    await mondayInput.press('Escape');

    // The Escape handler reverts the displayed value synchronously, so
    // once this assertion passes we know the handler ran. Any mutation
    // request would have been queued by then.
    await expect(mondayInput).toHaveValue(originalValue);
    expect(mutationFired).toBe(false);
});

// ──────────────────────────────────────────────────
// Week Navigation
// ──────────────────────────────────────────────────

test('navigating to previous week shows entries from that week', async ({ page, ctx }) => {
    const lastMonday = getLastWeekMonday();
    const project = await createProjectViaApi(ctx, { name: 'Last Week Project' });

    await createTimeEntryOnDateViaApi(ctx, {
        date: lastMonday,
        duration: '2h',
        projectId: project.id,
    });

    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);

    // Current week should have no entries
    await expect(page.locator('[data-testid="timesheet_row"]')).toHaveCount(0);

    // Go to previous week — the row-count assertion below auto-retries
    // until the new week's data arrives.
    await page.getByTestId('timesheet_prev_week').click();

    // Should now see the entry
    const rows = page.locator('[data-testid="timesheet_row"]');
    await expect(rows).toHaveCount(1);
});

test('can navigate forward and return to current week', async ({ page }) => {
    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);

    // Should show "This week"
    await expect(page.getByTestId('timesheet_week_display')).toContainText('This week');

    // Go to next week — the text assertions below auto-retry until the
    // header label flips.
    await page.getByTestId('timesheet_next_week').click();

    // Should no longer show "This week"
    await expect(page.getByTestId('timesheet_week_display')).not.toContainText('This week');

    // Go back to this week
    await page.getByTestId('timesheet_week_display').click();

    await expect(page.getByTestId('timesheet_week_display')).toContainText('This week');
});

// ──────────────────────────────────────────────────
// Copy Last Week
// ──────────────────────────────────────────────────

test('copy last week adds project rows from previous week without hours', async ({ page, ctx }) => {
    const lastMonday = getLastWeekMonday();
    const lastWednesday = getDayOfWeek(lastMonday, 2);

    const projectA = await createProjectViaApi(ctx, { name: 'Copy Project A' });
    const projectB = await createProjectViaApi(ctx, { name: 'Copy Project B' });

    await createTimeEntryOnDateViaApi(ctx, {
        date: lastMonday,
        duration: '2h',
        projectId: projectA.id,
    });
    await createTimeEntryOnDateViaApi(ctx, {
        date: lastWednesday,
        duration: '3h',
        projectId: projectB.id,
    });

    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);

    // Current week should have no populated rows yet.
    await expect(page.locator('[data-testid="timesheet_row"]')).toHaveCount(0);

    // Open copy last week dropdown and click "Copy rows only"
    await page.getByRole('button', { name: /Copy last week/i }).click();
    await page.getByText('Copy rows only').click();

    // Should now show 2 rows (one per project)
    const rows = page.locator('[data-testid="timesheet_row"]');
    await expect(rows).toHaveCount(2);

    // All row totals should be 0
    const rowTotals = page.locator('[data-testid="timesheet_row_total"]');
    const count = await rowTotals.count();
    for (let i = 0; i < count; i++) {
        await expect(rowTotals.nth(i)).toContainText('-');
    }
});

test('copy last week does not duplicate rows that already exist', async ({ page, ctx }) => {
    const lastMonday = getLastWeekMonday();
    const thisMonday = getCurrentWeekMonday();
    const thisTuesday = getDayOfWeek(thisMonday, 1);

    const project = await createProjectViaApi(ctx, { name: 'No Dup Project' });

    // Create entry for last week
    await createTimeEntryOnDateViaApi(ctx, {
        date: lastMonday,
        duration: '2h',
        projectId: project.id,
    });

    // Create entry for current week
    await createTimeEntryOnDateViaApi(ctx, {
        date: thisTuesday,
        duration: '1h',
        projectId: project.id,
    });

    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);

    // Should have 1 row (from current week entry)
    const rows = page.locator('[data-testid="timesheet_row"]');
    await expect(rows).toHaveCount(1);

    // Open copy last week dropdown and click "Copy rows only"
    await page.getByRole('button', { name: /Copy last week/i }).click();
    await page.getByText('Copy rows only').click();

    // Should still have only 1 row (not duplicated)
    await expect(rows).toHaveCount(1);
});

test('copy last week with time entries creates rows and entries', async ({ page, ctx }) => {
    const lastMonday = getLastWeekMonday();

    const project = await createProjectViaApi(ctx, { name: 'Copy Time Project' });

    await createTimeEntryOnDateViaApi(ctx, {
        date: lastMonday,
        duration: '2h',
        projectId: project.id,
    });

    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);

    // Current week should have no populated rows yet.
    await expect(page.locator('[data-testid="timesheet_row"]')).toHaveCount(0);

    // Open copy last week dropdown and click "Copy rows and time entries"
    await page.getByRole('button', { name: /Copy last week/i }).click();

    await Promise.all([
        page.waitForResponse(
            (resp) =>
                resp.url().includes('/time-entries') &&
                resp.request().method() === 'POST' &&
                resp.status() === 201
        ),
        page.getByText('Copy rows and time entries').click(),
    ]);

    // Should now show 1 row with time entries
    const rows = page.locator('[data-testid="timesheet_row"]');
    await expect(rows).toHaveCount(1);

    // Row total should not be 0 (entries were copied)
    const rowTotal = page.locator('[data-testid="timesheet_row_total"]').first();
    await expect(rowTotal).not.toContainText('0 h');
});

// ──────────────────────────────────────────────────
// Row Removal
// ──────────────────────────────────────────────────

test('can remove an empty project row without confirmation', async ({ page, ctx }) => {
    const project = await createProjectViaApi(ctx, { name: 'Empty Remove Project' });

    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);

    await chooseRowIdentity(page, project.name);

    const rows = page.locator('[data-testid="timesheet_row"]');
    await expect(rows).toHaveCount(1);

    // Hover the row to reveal the X button, then click it
    await rows.first().hover();
    await rows.first().getByRole('button', { name: 'Remove row' }).click();

    // Row should be removed immediately (no dialog)
    await expect(rows).toHaveCount(0);
});

test('removing a row with entries shows confirmation dialog and deletes entries', async ({
    page,
    ctx,
}) => {
    const monday = getCurrentWeekMonday();
    const project = await createProjectViaApi(ctx, { name: 'Delete Row Project' });

    await createTimeEntryOnDateViaApi(ctx, {
        date: monday,
        duration: '2h',
        projectId: project.id,
    });

    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);

    const rows = page.locator('[data-testid="timesheet_row"]');
    await expect(rows).toHaveCount(1);

    // Hover and click X
    await rows.first().hover();
    await rows.first().getByRole('button', { name: 'Remove row' }).click();

    // Confirmation dialog should appear
    await expect(page.getByRole('alertdialog')).toBeVisible();
    await expect(page.getByText('Remove timesheet row?')).toBeVisible();

    // Click Delete
    await Promise.all([
        page.waitForResponse(
            (resp) =>
                resp.url().includes('/time-entries') &&
                resp.request().method() === 'DELETE' &&
                resp.status() === 200
        ),
        page
            .getByRole('alertdialog')
            .getByRole('button', { name: /Delete/i })
            .click(),
    ]);

    // Row should be gone
    await expect(rows).toHaveCount(0);
});

// ──────────────────────────────────────────────────
// Multiple Entries Same Cell
// ──────────────────────────────────────────────────

test('cell correctly sums multiple entries for same project+day', async ({ page, ctx }) => {
    const monday = getCurrentWeekMonday();
    const project = await createProjectViaApi(ctx, { name: 'Sum Test' });

    // Create 2 entries for the same project on Monday
    await createTimeEntryOnDateViaApi(ctx, {
        date: monday,
        duration: '1h',
        projectId: project.id,
        description: 'Entry 1',
    });
    await createTimeEntryOnDateViaApi(ctx, {
        date: monday,
        duration: '2h',
        projectId: project.id,
        description: 'Entry 2',
    });

    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);

    // Should be 1 row (both entries grouped)
    const rows = page.locator('[data-testid="timesheet_row"]');
    await expect(rows).toHaveCount(1);

    // The Monday cell should show 3h total
    const cells = rows.first().locator('[data-testid="timesheet_cell"]');
    const mondayInput = cells.first().locator('input');
    // The value should contain "3" (for 3h in some format)
    await expect(mondayInput).toHaveValue(/3/);
});

// ──────────────────────────────────────────────────
// Duration Input Formats
// ──────────────────────────────────────────────────

test('cell accepts various duration input formats', async ({ page, ctx }) => {
    await createProjectViaApi(ctx, { name: 'Format Test' });

    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);

    await chooseRowIdentity(page, 'Format Test');

    const row = page.locator('[data-testid="timesheet_row"]').first();

    // Test entering "1.5" (should be 1h 30min)
    const cells = row.locator('[data-testid="timesheet_cell"]');
    const mondayInput = cells.first().locator('input');

    await mondayInput.click();
    await mondayInput.fill('1.5');

    await Promise.all([
        page.waitForResponse(
            (resp) =>
                resp.url().includes('/time-entries') &&
                resp.request().method() === 'POST' &&
                resp.status() === 201
        ),
        mondayInput.press('Enter'),
    ]);

    // 1.5 hours = 1h 30min
    await expect(mondayInput).toHaveValue('1h 30min');
});

test('test that adding a timesheet break to a full day splits the work entry via the placement modal', async ({
    page,
    ctx,
}) => {
    // A single work entry filling the day leaves no gap for a break, so the placement
    // modal must offer to split it (the only entry) and drop the break in the middle.
    await updateOrganizationSettingViaApi(ctx, { breaks_enabled: true });
    const day = getCurrentWeekMonday().toISOString().slice(0, 10);
    await createTimeEntryWithTimestampsViaApi(ctx, {
        start: `${day}T09:00:00Z`,
        end: `${day}T17:00:00Z`,
        description: 'Split me',
    });

    await goToTimesheet(page);
    await expect(page.getByTestId('timesheet_view')).toBeVisible();

    // The break row is always present — enter a 30m break on Monday
    const breakRow = page
        .locator('[data-testid="timesheet_row"]')
        .filter({ has: page.getByText('Break', { exact: true }) });
    const breakCell = breakRow.locator('[data-testid="timesheet_cell"]').nth(0).locator('input');
    await breakCell.click();
    await breakCell.fill('0.5');
    await breakCell.press('Enter');

    // The placement modal opens with the split preview
    await expect(page.getByTestId('break_placement_summary')).toBeVisible();
    await Promise.all([
        page.waitForResponse(
            async (resp) =>
                resp.url().includes('/time-entries') &&
                resp.request().method() === 'POST' &&
                resp.status() === 201 &&
                (await resp.json()).data.type === 'break'
        ),
        page.getByRole('button', { name: 'Add break' }).click(),
    ]);

    // The day now has two work halves and one break, none overlapping
    const entries = await getTimeEntriesViaApi(ctx);
    const dayEntries = entries
        .filter((e) => e.start.startsWith(day))
        .sort((a, b) => a.start.localeCompare(b.start));
    expect(dayEntries).toHaveLength(3);
    expect(dayEntries.map((e) => e.type)).toEqual(['work', 'break', 'work']);
    // The break sits flush between the two halves
    expect(dayEntries[0].end).toBe(dayEntries[1].start);
    expect(dayEntries[1].end).toBe(dayEntries[2].start);
});

test('test that adding a break into an oversized gap places it without moving other entries', async ({
    page,
    ctx,
}) => {
    // 09-12 and 15-17 leave a 3h gap — wider than the placement tolerance allows,
    // but easily big enough to hold the break. Such a gap is deliberate (the app
    // itself never creates one), so the break goes flush after the morning entry
    // and nothing else moves — no placement modal.
    await updateOrganizationSettingViaApi(ctx, { breaks_enabled: true });
    const day = getCurrentWeekMonday().toISOString().slice(0, 10);
    await createTimeEntryWithTimestampsViaApi(ctx, {
        start: `${day}T09:00:00Z`,
        end: `${day}T12:00:00Z`,
        description: 'Morning',
    });
    await createTimeEntryWithTimestampsViaApi(ctx, {
        start: `${day}T15:00:00Z`,
        end: `${day}T17:00:00Z`,
        description: 'Afternoon',
    });

    await goToTimesheet(page);
    await expect(page.getByTestId('timesheet_view')).toBeVisible();

    const breakRow = page
        .locator('[data-testid="timesheet_row"]')
        .filter({ has: page.getByText('Break', { exact: true }) });
    const breakCell = breakRow.locator('[data-testid="timesheet_cell"]').nth(0).locator('input');
    await breakCell.click();
    await breakCell.fill('0.5');
    await Promise.all([
        page.waitForResponse(
            async (resp) =>
                resp.url().includes('/time-entries') &&
                resp.request().method() === 'POST' &&
                resp.status() === 201 &&
                (await resp.json()).data.type === 'break'
        ),
        breakCell.press('Enter'),
    ]);

    await expect(page.getByTestId('break_placement_summary')).not.toBeVisible();
    const entries = await getTimeEntriesViaApi(ctx);
    const dayEntries = entries
        .filter((e) => e.start.startsWith(day))
        .sort((a, b) => a.start.localeCompare(b.start));
    expect(dayEntries.map((e) => [e.type, e.start, e.end])).toEqual([
        ['work', `${day}T09:00:00Z`, `${day}T12:00:00Z`],
        ['break', `${day}T12:00:00Z`, `${day}T12:30:00Z`],
        ['work', `${day}T15:00:00Z`, `${day}T17:00:00Z`],
    ]);
});

test('test that the placement modal warns when the chosen time would leave the break misaligned', async ({
    page,
    ctx,
}) => {
    // Back-to-back 09-12 and 12-17 leave no gap, so the placement modal opens.
    // The suggested slot (flush at 12:00) is aligned — no warning. Moving the
    // break to 07:00, before any work, keeps the plan feasible but the result
    // would immediately carry the misaligned hint, so the modal warns upfront.
    await updateOrganizationSettingViaApi(ctx, { breaks_enabled: true });
    const day = getCurrentWeekMonday().toISOString().slice(0, 10);
    await createTimeEntryWithTimestampsViaApi(ctx, {
        start: `${day}T09:00:00Z`,
        end: `${day}T12:00:00Z`,
        description: 'Morning',
    });
    await createTimeEntryWithTimestampsViaApi(ctx, {
        start: `${day}T12:00:00Z`,
        end: `${day}T17:00:00Z`,
        description: 'Afternoon',
    });

    await goToTimesheet(page);
    await expect(page.getByTestId('timesheet_view')).toBeVisible();

    const breakRow = page
        .locator('[data-testid="timesheet_row"]')
        .filter({ has: page.getByText('Break', { exact: true }) });
    const breakCell = breakRow.locator('[data-testid="timesheet_cell"]').nth(0).locator('input');
    await breakCell.click();
    await breakCell.fill('0.5');
    await breakCell.press('Enter');

    // Default suggestion sits flush between work → no warning
    await expect(page.getByTestId('break_placement_summary')).toBeVisible();
    await expect(page.getByTestId('break_placement_misaligned_warning')).not.toBeVisible();

    // Move the break to 07:00-07:30, before all work
    const modal = page.getByRole('dialog');
    const startTimeInput = modal.getByTestId('time_picker_input').first();
    await startTimeInput.fill('07:00');
    await startTimeInput.press('Tab');
    const endTimeInput = modal.getByTestId('time_picker_input').nth(1);
    await endTimeInput.fill('07:30');
    await endTimeInput.press('Tab');

    // Feasible (nothing has to move), but flagged as misaligned beforehand
    await expect(page.getByTestId('break_placement_misaligned_warning')).toBeVisible();
    await expect(page.getByTestId('break_placement_summary')).toContainText(
        'No entries need to move.'
    );

    // The warning is non-blocking: the break can still be added as chosen
    await Promise.all([
        page.waitForResponse(
            async (resp) =>
                resp.url().includes('/time-entries') &&
                resp.request().method() === 'POST' &&
                resp.status() === 201 &&
                (await resp.json()).data.type === 'break'
        ),
        page.getByRole('button', { name: 'Add break' }).click(),
    ]);

    const entries = await getTimeEntriesViaApi(ctx);
    const dayEntries = entries
        .filter((e) => e.start.startsWith(day))
        .sort((a, b) => a.start.localeCompare(b.start));
    expect(dayEntries.map((e) => [e.type, e.start, e.end])).toEqual([
        ['break', `${day}T07:00:00Z`, `${day}T07:30:00Z`],
        ['work', `${day}T09:00:00Z`, `${day}T12:00:00Z`],
        ['work', `${day}T12:00:00Z`, `${day}T17:00:00Z`],
    ]);
    // ...and the timesheet now shows the misaligned-break hint for that day
    await expect(
        page.getByRole('button', { name: 'does not align with your work entries' })
    ).toBeVisible();
});

test('test that a misplaced break shows a warning on its timesheet day cell', async ({
    page,
    ctx,
}) => {
    // Work ends at 10:00 and the break starts hours later with no work after it,
    // so it is misplaced and its day header should carry the warning hint.
    await updateOrganizationSettingViaApi(ctx, { breaks_enabled: true });
    const day = getCurrentWeekMonday().toISOString().slice(0, 10);
    await createTimeEntryWithTimestampsViaApi(ctx, {
        start: `${day}T09:00:00Z`,
        end: `${day}T10:00:00Z`,
    });
    await createTimeEntryWithTimestampsViaApi(ctx, {
        start: `${day}T14:00:00Z`,
        end: `${day}T14:30:00Z`,
        type: 'break',
    });

    await goToTimesheet(page);
    await expect(page.getByTestId('timesheet_view')).toBeVisible();

    // Exactly one warning, sitting in Monday's day header
    const hint = page.getByRole('button', {
        name: 'does not align with your work entries',
    });
    await expect(hint).toHaveCount(1);
    await expect(
        page.getByTestId('timesheet_day_header').first().getByRole('button', {
            name: 'does not align with your work entries',
        })
    ).toBeVisible();

    // The hint links to the calendar on the affected date
    await hint.click();
    await expect(page.getByRole('link', { name: 'Fix in calendar' })).toHaveAttribute(
        'href',
        `/calendar?date=${day}`
    );
});

test('test that editing a timesheet break re-places it as one entry instead of fragmenting it', async ({
    page,
    ctx,
}) => {
    // Two work entries with a 1h gap, and a 30m break created directly inside it (12:15–12:45).
    await updateOrganizationSettingViaApi(ctx, { breaks_enabled: true });
    const day = getCurrentWeekMonday().toISOString().slice(0, 10);
    await createTimeEntryWithTimestampsViaApi(ctx, {
        start: `${day}T09:00:00Z`,
        end: `${day}T12:00:00Z`,
        description: 'Work',
    });
    await createTimeEntryWithTimestampsViaApi(ctx, {
        start: `${day}T13:00:00Z`,
        end: `${day}T17:00:00Z`,
        description: 'Work',
    });
    const breakEntry = await createTimeEntryWithTimestampsViaApi(ctx, {
        start: `${day}T12:15:00Z`,
        end: `${day}T12:45:00Z`,
        type: 'break',
    });

    await goToTimesheet(page);
    await expect(page.getByTestId('timesheet_view')).toBeVisible();
    const breakRow = page
        .locator('[data-testid="timesheet_row"]')
        .filter({ has: page.getByText('Break', { exact: true }) });
    const breakCell = breakRow.locator('[data-testid="timesheet_cell"]').nth(0).locator('input');
    await breakCell.click();
    await breakCell.fill('0.75'); // 45 minutes — still fits the 1h gap, so it stays anchored
    await Promise.all([
        // A break that still fits its gap is re-placed in place (PUT on the same entry),
        // not deleted and recreated — that's what keeps it a single entry.
        page.waitForResponse(
            async (resp) =>
                resp.url().includes(`/time-entries/${breakEntry.id}`) &&
                resp.request().method() === 'PUT' &&
                resp.status() === 200 &&
                (await resp.json()).data.type === 'break'
        ),
        breakCell.press('Enter'),
    ]);

    // Still exactly one break on the day (not fragmented). It stays anchored at its current
    // start (12:15) rather than re-centering, growing its end to 13:00 to reach 45 minutes.
    const after = await getTimeEntriesViaApi(ctx);
    const breaks = after.filter((e) => e.start.startsWith(day) && e.type === 'break');
    expect(breaks).toHaveLength(1);
    expect(breaks[0].duration).toBe(2700);
    expect(breaks[0].start).toBe(`${day}T12:15:00Z`);
    expect(breaks[0].end).toBe(`${day}T13:00:00Z`);
});
