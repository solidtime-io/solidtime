/**
 * E2E coverage for the timesheet overlap-prevention logic introduced
 * in `useTimesheetCellMutations` (Phase 1+2+3 of the overlap fix).
 *
 * Each test:
 *   1. Pre-creates entries via the API to set up a deterministic
 *      day-of-work scenario,
 *   2. Triggers ONE cell edit through the UI,
 *   3. Reads the resulting entries back via the API and asserts on
 *      the start/end placement.
 *
 * Pre-creating rows (rather than driving the "Add row" + project picker
 * UI) keeps the tests focused on the placement logic and out of the
 * project-dropdown's flake surface.
 */

import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import { expect } from '@playwright/test';
import type { Page, Request } from '@playwright/test';
import {
    createProjectViaApi,
    createTimeEntryAtHourViaApi,
    getTimeEntriesViaApi,
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

async function waitForTimesheetLoad(page: Page) {
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

const HOUR = 3600;

function utcHourOf(iso: string): number {
    return new Date(iso).getUTCHours();
}

function utcMinuteOf(iso: string): number {
    return new Date(iso).getUTCMinutes();
}

function sortByStart<T extends { start: string }>(entries: T[]): T[] {
    return [...entries].sort((a, b) => a.start.localeCompare(b.start));
}

/**
 * Returns the locator for the row whose project name matches the given
 * substring. Robust against ordering changes.
 */
function rowByProject(page: Page, projectName: string) {
    return page.locator('[data-testid="timesheet_row"]').filter({ hasText: projectName });
}

/**
 * Returns the locator for the input in the (row, dayIndex) cell, where
 * the row is identified by project name.
 */
function cellInputByProject(page: Page, projectName: string, dayIndex: number) {
    return rowByProject(page, projectName)
        .locator('[data-testid="timesheet_cell"]')
        .nth(dayIndex)
        .locator('input');
}

/** Asserts that no entries in the list overlap each other. */
function expectNoOverlaps(entries: Array<{ start: string; end: string | null }>) {
    const sorted = sortByStart(entries.filter((e) => e.end !== null));
    for (let i = 1; i < sorted.length; i++) {
        const prev = sorted[i - 1]!;
        const curr = sorted[i]!;
        expect(
            curr.start >= prev.end!,
            `entries overlap: ${prev.start}–${prev.end} vs ${curr.start}–${curr.end}`
        ).toBe(true);
    }
}

// ──────────────────────────────────────────────────
// Phase 1: createCell — overlap avoidance when cell is empty
// ──────────────────────────────────────────────────

test('extendCell on a row that has no entries on the day yet places after another row (Scenario #4)', async ({
    page,
    ctx,
}) => {
    // Setup: project A has Monday 09:00–10:00, project B has Tuesday
    // 09:00–10:00. The B row is therefore visible on the timesheet but
    // has an EMPTY cell on Monday. Typing into B's Monday cell exercises
    // the createCell path (cell empty → place a new entry).
    const monday = getCurrentWeekMonday();
    const tuesday = new Date(monday);
    tuesday.setUTCDate(monday.getUTCDate() + 1);

    const projectA = await createProjectViaApi(ctx, { name: 'OverlapAlpha' });
    const projectB = await createProjectViaApi(ctx, { name: 'OverlapBravo' });

    await createTimeEntryAtHourViaApi(ctx, {
        date: monday,
        startHour: 9,
        durationSeconds: HOUR,
        projectId: projectA.id,
    });
    await createTimeEntryAtHourViaApi(ctx, {
        date: tuesday,
        startHour: 9,
        durationSeconds: HOUR,
        projectId: projectB.id,
    });

    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);
    await expect(page.locator('[data-testid="timesheet_row"]')).toHaveCount(2);

    // Type 1h into project B's Monday cell. The createCell path should
    // place it AFTER project A's 09:00–10:00 (i.e. at 10:00 or later),
    // not at 09:00.
    const input = cellInputByProject(page, 'OverlapBravo', 0);
    await input.click();
    await input.fill('1');

    await Promise.all([
        page.waitForResponse(
            (resp) =>
                resp.url().includes('/time-entries') &&
                resp.request().method() === 'POST' &&
                resp.status() === 201
        ),
        input.press('Enter'),
    ]);

    const entries = await getTimeEntriesViaApi(ctx);
    const bMondayEntry = entries.find(
        (e) =>
            e.project_id === projectB.id &&
            new Date(e.start).getTime() >= monday.getTime() &&
            new Date(e.start).getTime() < tuesday.getTime()
    )!;
    expect(bMondayEntry).toBeDefined();
    // 09:00 is blocked → must be at 10:00 or later.
    expect(utcHourOf(bMondayEntry.start)).toBeGreaterThanOrEqual(10);
    expectNoOverlaps(entries);
});

test('createCell refuses to cross midnight when day is full (Scenario #3)', async ({
    page,
    ctx,
}) => {
    // Setup: fill Monday 01:00–23:00 (22 hours, leaving 1h before and
    // 1h after — neither big enough for a 3h ask). Project B is on
    // Tuesday so the B row exists with an empty Monday cell. Typing 3h
    // into B's Monday cell should be refused.
    //
    // We start at 01:00 (not 00:00) because the API's time-entry
    // filter excludes entries whose `start` equals the query's `start`
    // bound exactly. Using 01:00 avoids that boundary condition.
    const monday = getCurrentWeekMonday();
    const tuesday = new Date(monday);
    tuesday.setUTCDate(monday.getUTCDate() + 1);

    const projectFull = await createProjectViaApi(ctx, { name: 'OverlapFull' });
    const projectNew = await createProjectViaApi(ctx, { name: 'OverlapNoRoom' });

    await createTimeEntryAtHourViaApi(ctx, {
        date: monday,
        startHour: 1,
        durationSeconds: 22 * HOUR,
        projectId: projectFull.id,
    });
    await createTimeEntryAtHourViaApi(ctx, {
        date: tuesday,
        startHour: 9,
        durationSeconds: HOUR,
        projectId: projectNew.id,
    });

    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);
    await expect(page.locator('[data-testid="timesheet_row"]')).toHaveCount(2);

    const input = cellInputByProject(page, 'OverlapNoRoom', 0);
    const seenMutationRequests: string[] = [];
    const onRequest = (request: Request) => {
        if (request.url().includes('/time-entries') && request.method() !== 'GET') {
            seenMutationRequests.push(request.method());
        }
    };
    page.on('request', onRequest);
    await input.click();
    await input.fill('3');
    await input.press('Enter');

    await expect(page.getByText("This day can't fit any more work")).toBeVisible();
    page.off('request', onRequest);

    const entries = await getTimeEntriesViaApi(ctx);
    // The new project should still only have its Tuesday entry.
    const newEntries = entries.filter((e) => e.project_id === projectNew.id);
    expect(seenMutationRequests).toEqual([]);
    expect(newEntries).toHaveLength(1);
    expect(utcHourOf(newEntries[0]!.start)).toBe(9);
    // The Tuesday entry's date is unchanged (still Tuesday).
    expect(new Date(newEntries[0]!.start).getUTCDay()).toBe(2);
});

// ──────────────────────────────────────────────────
// Phase 2: extendCell — collision detection + split
// ──────────────────────────────────────────────────

test('extendCell splits the extension when another row blocks the path (Scenario #5)', async ({
    page,
    ctx,
}) => {
    // Setup:
    //   - project A on Monday 09:00–10:00 (1h)
    //   - project B on Monday 10:30–11:30 (1h, blocker)
    // Bumping A's Monday cell from 1h to 3h (+2h) should:
    //   - extend A to 09:00–10:30 (filling the 30min gap)
    //   - place a new A entry at 11:30–13:00 (the remaining 90min)
    const monday = getCurrentWeekMonday();
    const projectA = await createProjectViaApi(ctx, { name: 'OverlapExtend' });
    const projectB = await createProjectViaApi(ctx, { name: 'OverlapBlocker' });

    await createTimeEntryAtHourViaApi(ctx, {
        date: monday,
        startHour: 9,
        durationSeconds: HOUR,
        projectId: projectA.id,
    });
    await createTimeEntryAtHourViaApi(ctx, {
        date: monday,
        startHour: 10,
        startMinute: 30,
        durationSeconds: HOUR,
        projectId: projectB.id,
    });

    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);
    await expect(page.locator('[data-testid="timesheet_row"]')).toHaveCount(2);

    const input = cellInputByProject(page, 'OverlapExtend', 0);
    await input.click();
    await input.fill('3');
    await Promise.all([
        page.waitForResponse(
            (resp) =>
                resp.url().includes('/time-entries') &&
                resp.request().method() === 'PUT' &&
                resp.status() === 200
        ),
        page.waitForResponse(
            (resp) =>
                resp.url().includes('/time-entries') &&
                resp.request().method() === 'POST' &&
                resp.status() === 201
        ),
        input.press('Enter'),
    ]);

    const entries = await getTimeEntriesViaApi(ctx);
    const aEntries = entries.filter((e) => e.project_id === projectA.id);
    const bEntries = entries.filter((e) => e.project_id === projectB.id);

    // The blocker is unchanged.
    expect(bEntries).toHaveLength(1);
    expect(utcHourOf(bEntries[0]!.start)).toBe(10);
    expect(utcMinuteOf(bEntries[0]!.start)).toBe(30);

    // Project A should now have 2 entries.
    expect(aEntries).toHaveLength(2);
    const sortedA = sortByStart(aEntries);
    // Extended entry: 09:00 → 10:30
    expect(utcHourOf(sortedA[0]!.start)).toBe(9);
    expect(utcHourOf(sortedA[0]!.end!)).toBe(10);
    expect(utcMinuteOf(sortedA[0]!.end!)).toBe(30);
    // Split remainder: 11:30 → 13:00
    expect(utcHourOf(sortedA[1]!.start)).toBe(11);
    expect(utcMinuteOf(sortedA[1]!.start)).toBe(30);

    // No overlaps anywhere on the day.
    expectNoOverlaps(entries);
});

test('extendCell prefers latest-end (not latest-start) when nested entries exist (Scenario #6)', async ({
    page,
    ctx,
}) => {
    // Pre-existing nested overlap on the same project:
    //   - outer: 09:00 → 12:00 (3h)
    //   - inner: 10:00 → 11:00 (1h, contained inside outer)
    // The cell total is 3h + 1h = 4h. Bumping to 5h (+1h) should grow
    // the OUTER entry's end to 13:00, not the inner.
    const monday = getCurrentWeekMonday();
    const project = await createProjectViaApi(ctx, { name: 'OverlapNested' });

    await createTimeEntryAtHourViaApi(ctx, {
        date: monday,
        startHour: 9,
        durationSeconds: 3 * HOUR,
        projectId: project.id,
        description: 'outer',
    });
    await createTimeEntryAtHourViaApi(ctx, {
        date: monday,
        startHour: 10,
        durationSeconds: HOUR,
        projectId: project.id,
        description: 'inner',
    });

    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);
    await expect(page.locator('[data-testid="timesheet_row"]')).toHaveCount(1);

    const input = cellInputByProject(page, 'OverlapNested', 0);
    await input.click();
    await input.fill('5');

    await Promise.all([
        page.waitForResponse(
            (resp) =>
                resp.url().includes('/time-entries') &&
                resp.request().method() === 'PUT' &&
                resp.status() === 200
        ),
        input.press('Enter'),
    ]);

    const entries = await getTimeEntriesViaApi(ctx);
    const outer = entries.find((e) => e.description === 'outer')!;
    const inner = entries.find((e) => e.description === 'inner')!;

    expect(utcHourOf(outer.start)).toBe(9);
    expect(utcHourOf(outer.end!)).toBe(13); // extended from 12:00 → 13:00
    expect(utcHourOf(inner.start)).toBe(10);
    expect(utcHourOf(inner.end!)).toBe(11); // unchanged
});

// ──────────────────────────────────────────────────
// Phase 1+2 spillover from previous day
// ──────────────────────────────────────────────────

test('createCell handles intra-week spillover from previous day (Scenario #2)', async ({
    page,
    ctx,
}) => {
    // Setup: an entry that starts on Monday 22:00 and ends Tuesday 03:00
    // (5h, crosses midnight INTO Tuesday). This spillover starts inside
    // the loaded week, so the timesheet query loads it.
    //
    // Then we try to place 1h on Tuesday for a different project. The
    // expected behavior: the new entry must NOT overlap the spillover.
    // Tuesday 09:00 is well clear of the [00:00, 03:00) spillover, so
    // 09:00 is the correct placement.
    const monday = getCurrentWeekMonday();
    const tuesday = new Date(monday);
    tuesday.setUTCDate(monday.getUTCDate() + 1);
    const wednesday = new Date(monday);
    wednesday.setUTCDate(monday.getUTCDate() + 2);

    const projectSpill = await createProjectViaApi(ctx, { name: 'OverlapSpill' });
    const projectNew = await createProjectViaApi(ctx, { name: 'OverlapToday' });

    // Monday 22:00 → Tuesday 03:00 (5h spillover into Tuesday).
    await createTimeEntryAtHourViaApi(ctx, {
        date: monday,
        startHour: 22,
        durationSeconds: 5 * HOUR,
        projectId: projectSpill.id,
    });
    // Stub Wednesday entry on the new project so its row is visible
    // even before we type anything in Tuesday's cell.
    await createTimeEntryAtHourViaApi(ctx, {
        date: wednesday,
        startHour: 9,
        durationSeconds: HOUR,
        projectId: projectNew.id,
    });

    await Promise.all([goToTimesheet(page), waitForTimesheetLoad(page)]);
    await expect(page.locator('[data-testid="timesheet_row"]')).toHaveCount(2);

    // Type 1h into the new project's Tuesday cell (day index 1).
    const input = cellInputByProject(page, 'OverlapToday', 1);
    await input.click();
    await input.fill('1');

    await Promise.all([
        page.waitForResponse(
            (resp) =>
                resp.url().includes('/time-entries') &&
                resp.request().method() === 'POST' &&
                resp.status() === 201
        ),
        input.press('Enter'),
    ]);

    const entries = await getTimeEntriesViaApi(ctx);
    const newTuesdayEntry = entries.find(
        (e) =>
            e.project_id === projectNew.id &&
            new Date(e.start).getTime() >= tuesday.getTime() &&
            new Date(e.start).getTime() < wednesday.getTime()
    )!;
    expect(newTuesdayEntry).toBeDefined();
    // 09:00 is well past the spillover end (03:00) → should land at 09:00.
    expect(utcHourOf(newTuesdayEntry.start)).toBe(9);
    expectNoOverlaps(entries);
});
