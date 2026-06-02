import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { ref } from 'vue';
import { createPinia, setActivePinia } from 'pinia';
import { useTimesheetCellMutations, makeCellStatusKey } from './useTimesheetCellMutations';
import { api } from '@/packages/api/src';
import type { TimesheetRow, TimesheetCell } from '@/utils/useTimesheetGrid';
import type { TimeEntry } from '@/packages/api/src';

const addNotification = vi.fn();

vi.mock('@/utils/useUser', () => ({
    getCurrentOrganizationId: vi.fn(() => 'org-1'),
    getCurrentMembershipId: vi.fn(() => 'mem-1'),
}));

vi.mock('@tanstack/vue-query', () => ({
    useQueryClient: () => ({
        invalidateQueries: vi.fn(),
    }),
}));

vi.mock('@/utils/notification', () => ({
    useNotificationsStore: () => ({
        addNotification,
    }),
}));

vi.mock('@/packages/api/src', () => ({
    api: {
        createTimeEntry: vi.fn(async () => ({ data: { id: 'new-id' } })),
        updateTimeEntry: vi.fn(async () => undefined),
        deleteTimeEntry: vi.fn(async () => undefined),
        deleteTimeEntries: vi.fn(async () => undefined),
    },
}));

// All scenarios use UTC so the local "day" matches the UTC day exactly
// (the test-setup mocks getTimezoneSetting to return 'UTC').

const DATE = '2026-04-10';
const HOUR = 3600;

function entry(start: string, end: string | null, overrides: Partial<TimeEntry> = {}): TimeEntry {
    const startMs = new Date(start).valueOf();
    const endMs = end ? new Date(end).valueOf() : startMs;
    return {
        id: overrides.id ?? `e-${start}-${end ?? 'running'}`,
        start,
        end,
        duration: end ? Math.floor((endMs - startMs) / 1000) : null,
        description: '',
        member_id: 'm-1',
        project_id: 'p-1',
        task_id: null,
        billable: false,
        tags: [],
        ...overrides,
    } as unknown as TimeEntry;
}

function buildCell(entries: TimeEntry[]): TimesheetCell {
    return {
        dayIndex: 0,
        date: DATE,
        entries,
        totalSeconds: entries.reduce((sum, e) => sum + (e.duration ?? 0), 0),
    };
}

function buildRow(
    projectId: string | null,
    entries: TimeEntry[],
    key = `${projectId}:null`
): TimesheetRow {
    const cell = buildCell(entries);
    return {
        key,
        projectId,
        taskId: null,
        billable: false,
        tags: [],
        cells: new Map([[0, cell]]),
        totalSeconds: cell.totalSeconds,
    };
}

function buildEmptyRow(projectId: string | null, key = `${projectId}:null`): TimesheetRow {
    return {
        key,
        projectId,
        taskId: null,
        billable: false,
        tags: [],
        cells: new Map(),
        totalSeconds: 0,
    };
}

/** Shape of the body the cell-mutation logic passes to api.createTimeEntry. */
interface CapturedEntry {
    id?: string;
    start: string;
    end: string | null;
    project_id?: string | null;
    task_id?: string | null;
    description?: string | null;
}

const apiMocks = vi.mocked(api);

function firstArg(
    mock: typeof apiMocks.createTimeEntry | typeof apiMocks.updateTimeEntry
): CapturedEntry {
    return mock.mock.calls[0]?.[0] as unknown as CapturedEntry;
}

function setup(
    allEntries: TimeEntry[],
    rowsValue: TimesheetRow[] = [],
    removeSlot: (key: string) => void = () => undefined
) {
    const cellMutations = useTimesheetCellMutations(
        ref([
            DATE,
            '2026-04-11',
            '2026-04-12',
            '2026-04-13',
            '2026-04-14',
            '2026-04-15',
            '2026-04-16',
        ]),
        ref(allEntries),
        ref(rowsValue),
        removeSlot
    );

    return { cellMutations };
}

beforeEach(() => {
    setActivePinia(createPinia());
    apiMocks.createTimeEntry.mockClear();
    apiMocks.updateTimeEntry.mockClear();
    apiMocks.deleteTimeEntry.mockClear();
    apiMocks.deleteTimeEntries.mockClear();
    addNotification.mockClear();
    // Lock the clock to mid-afternoon on the test day so running-entry
    // tests have a deterministic "now". Past 12:00 to make spillover
    // scenarios meaningful.
    vi.useFakeTimers();
    vi.setSystemTime(new Date('2026-04-10T14:00:00Z'));
});

afterEach(() => {
    vi.useRealTimers();
});

describe('useTimesheetCellMutations.handleCellUpdate', () => {
    // ── No-op ─────────────────────────────────────────────────────

    it('does nothing when the diff is zero', async () => {
        const { cellMutations } = setup([]);
        const row = buildRow('p-1', [entry('2026-04-10T09:00:00Z', '2026-04-10T10:00:00Z')]);
        await cellMutations.handleCellUpdate(row, 0, HOUR);

        expect(apiMocks.createTimeEntry).not.toHaveBeenCalled();
        expect(apiMocks.updateTimeEntry).not.toHaveBeenCalled();
        expect(apiMocks.deleteTimeEntry).not.toHaveBeenCalled();
        expect(apiMocks.deleteTimeEntries).not.toHaveBeenCalled();
    });

    // ── Delete cell ───────────────────────────────────────────────

    it('clearing a cell deletes all entries in it', async () => {
        const cellEntry = entry('2026-04-10T09:00:00Z', '2026-04-10T10:00:00Z');
        const { cellMutations } = setup([cellEntry]);
        const row = buildRow('p-1', [cellEntry]);

        await cellMutations.handleCellUpdate(row, 0, 0);

        expect(apiMocks.deleteTimeEntries).toHaveBeenCalledTimes(1);
        const [, options] = apiMocks.deleteTimeEntries.mock.calls[0]!;
        expect(options?.queries?.ids).toEqual([cellEntry.id]);
        expect(options?.params?.organization).toBe('org-1');
    });

    // ── Create cell (Phase 1) ──────────────────────────────────────

    describe('createCell', () => {
        it('places a new entry at 09:00 on an empty day', async () => {
            const { cellMutations } = setup([]);
            const row = buildEmptyRow('p-1');

            await cellMutations.handleCellUpdate(row, 0, HOUR);

            expect(apiMocks.createTimeEntry).toHaveBeenCalledTimes(1);
            const arg = firstArg(apiMocks.createTimeEntry);
            expect(arg.start).toBe('2026-04-10T09:00:00Z');
            expect(arg.end).toBe('2026-04-10T10:00:00Z');
            expect(arg.project_id).toBe('p-1');
        });

        it('passes no-project rows to the API as null', async () => {
            const { cellMutations } = setup([]);
            const row = buildEmptyRow(null);

            await cellMutations.handleCellUpdate(row, 0, HOUR);

            expect(apiMocks.createTimeEntry).toHaveBeenCalledTimes(1);
            expect(firstArg(apiMocks.createTimeEntry).project_id).toBeNull();
        });

        it('collapses an empty duplicate row after its first entry is created', async () => {
            const removeSlot = vi.fn();
            const existingRow = buildEmptyRow('p-1', 'existing-slot');
            const duplicateRow = buildEmptyRow('p-1', 'duplicate-slot');
            const { cellMutations } = setup([], [existingRow, duplicateRow], removeSlot);

            await cellMutations.handleCellUpdate(duplicateRow, 0, HOUR);

            expect(apiMocks.createTimeEntry).toHaveBeenCalledTimes(1);
            expect(removeSlot).toHaveBeenCalledWith('duplicate-slot');
            expect(addNotification).toHaveBeenCalledWith(
                'success',
                'Merged into matching row',
                'Another row with the same project, task, billable status and tags already exists.'
            );
        });

        it("falls back to the start of the day when 09:00 wouldn't fit", async () => {
            // Block 09:00 → 23:30 with another row's entry. The only
            // gap big enough for 1h is 00:00 → 09:00.
            const blocker = entry('2026-04-10T09:00:00Z', '2026-04-10T23:30:00Z', {
                id: 'blocker',
            });
            const { cellMutations } = setup([blocker]);
            const row = buildEmptyRow('p-1');

            await cellMutations.handleCellUpdate(row, 0, HOUR);

            expect(apiMocks.createTimeEntry).toHaveBeenCalledTimes(1);
            const arg = firstArg(apiMocks.createTimeEntry);
            expect(arg.start).toBe('2026-04-10T00:00:00Z');
            expect(arg.end).toBe('2026-04-10T01:00:00Z');
        });

        it('avoids overlapping with another row on the same day (Scenario #4)', async () => {
            // Another row has an entry 09:00 → 10:00. The new entry must
            // not overlap it.
            const blocker = entry('2026-04-10T09:00:00Z', '2026-04-10T10:00:00Z', {
                id: 'blocker',
            });
            const { cellMutations } = setup([blocker]);
            const row = buildEmptyRow('p-1');

            await cellMutations.handleCellUpdate(row, 0, HOUR);

            const arg = firstArg(apiMocks.createTimeEntry);
            // 09:00 is blocked → must place after, at 10:00
            expect(arg.start).toBe('2026-04-10T10:00:00Z');
            expect(arg.end).toBe('2026-04-10T11:00:00Z');
        });

        it('treats a running entry as a blocker (Scenario #1)', async () => {
            const running = entry('2026-04-10T08:00:00Z', null, { id: 'running' });
            const { cellMutations } = setup([running]);
            const row = buildEmptyRow('p-1');

            await cellMutations.handleCellUpdate(row, 0, HOUR);

            const arg = firstArg(apiMocks.createTimeEntry);
            // The running entry blocks [08:00, now=14:00). The 09:00
            // work-hours default lands inside that block, so the
            // search advances the cursor to 14:00 and places the new
            // entry at 14:00 → 15:00. Critically, this does NOT
            // overlap the running timer.
            expect(arg.start).toBe('2026-04-10T14:00:00Z');
            expect(arg.end).toBe('2026-04-10T15:00:00Z');
        });

        it('avoids spillover from the previous day (Scenario #2)', async () => {
            // An entry from yesterday spills into today's first 4h.
            const spillover = entry('2026-04-09T22:00:00Z', '2026-04-10T04:00:00Z', {
                id: 'spillover',
            });
            const { cellMutations } = setup([spillover]);
            const row = buildEmptyRow('p-1');

            await cellMutations.handleCellUpdate(row, 0, HOUR);

            const arg = firstArg(apiMocks.createTimeEntry);
            // 09:00 is free (the spillover ends at 04:00).
            expect(arg.start).toBe('2026-04-10T09:00:00Z');
        });

        it('refuses to cross midnight (Scenario #3)', async () => {
            // Block all of the day except the last 2h. Asking for 3h
            // → no single-day window fits → notification, no API call.
            const blocker = entry('2026-04-10T00:00:00Z', '2026-04-10T22:00:00Z', {
                id: 'blocker',
            });
            const { cellMutations } = setup([blocker]);
            const row = buildEmptyRow('p-1');

            await cellMutations.handleCellUpdate(row, 0, 3 * HOUR);

            expect(apiMocks.createTimeEntry).not.toHaveBeenCalled();
        });
    });

    // ── Extend cell (Phase 2) ──────────────────────────────────────

    describe('extendCell', () => {
        it("extends the cell's latest-ended entry forward when there is room", async () => {
            const cellEntry = entry('2026-04-10T09:00:00Z', '2026-04-10T10:00:00Z', {
                id: 'extend-me',
            });
            const { cellMutations } = setup([cellEntry]);
            const row = buildRow('p-1', [cellEntry]);

            // Cell is 1h, request 2h total → +1h
            await cellMutations.handleCellUpdate(row, 0, 2 * HOUR);

            expect(apiMocks.updateTimeEntry).toHaveBeenCalledTimes(1);
            const updated = firstArg(apiMocks.updateTimeEntry);
            expect(updated.id).toBe('extend-me');
            expect(updated.end).toBe('2026-04-10T11:00:00Z');
            expect(apiMocks.createTimeEntry).not.toHaveBeenCalled();
        });

        it('picks the latest-END entry when nested entries exist (Scenario #6)', async () => {
            // Outer entry 09:00 → 12:00, inner entry 10:00 → 11:00.
            // The latest START is "inner" but the latest END is "outer".
            // Extending should grow the OUTER entry, not the inner.
            const outer = entry('2026-04-10T09:00:00Z', '2026-04-10T12:00:00Z', { id: 'outer' });
            const inner = entry('2026-04-10T10:00:00Z', '2026-04-10T11:00:00Z', { id: 'inner' });
            const { cellMutations } = setup([outer, inner]);
            const row = buildRow('p-1', [outer, inner]);

            // Cell total = 3h + 1h = 4h. Bump to 5h → +1h.
            await cellMutations.handleCellUpdate(row, 0, 5 * HOUR);

            expect(apiMocks.updateTimeEntry).toHaveBeenCalledTimes(1);
            const updated = firstArg(apiMocks.updateTimeEntry);
            expect(updated.id).toBe('outer');
            expect(updated.end).toBe('2026-04-10T13:00:00Z');
        });

        it('splits the extension when another row blocks the path (Scenario #5)', async () => {
            // Cell entry: 09:00 → 10:00 (1h). Blocker on another row:
            // 10:30 → 11:30. Bump cell to 3h (+2h):
            //   - 30 minutes fit in the gap [10:00, 10:30) → extend to 10:30
            //   - 90 minutes remain → place a NEW entry in the next free
            //     window (11:30 → 13:00)
            const cellEntry = entry('2026-04-10T09:00:00Z', '2026-04-10T10:00:00Z', {
                id: 'cell-entry',
            });
            const blocker = entry('2026-04-10T10:30:00Z', '2026-04-10T11:30:00Z', {
                id: 'blocker',
                project_id: 'other-project',
            });
            const { cellMutations } = setup([cellEntry, blocker]);
            const row = buildRow('p-1', [cellEntry]);

            await cellMutations.handleCellUpdate(row, 0, 3 * HOUR);

            expect(apiMocks.updateTimeEntry).toHaveBeenCalledTimes(1);
            const updated = firstArg(apiMocks.updateTimeEntry);
            expect(updated.id).toBe('cell-entry');
            expect(updated.end).toBe('2026-04-10T10:30:00Z');

            expect(apiMocks.createTimeEntry).toHaveBeenCalledTimes(1);
            const created = firstArg(apiMocks.createTimeEntry);
            // Remainder = 2h - 30min = 90min, placed in next free window
            // (11:30 → 13:00)
            expect(created.start).toBe('2026-04-10T11:30:00Z');
            expect(created.end).toBe('2026-04-10T13:00:00Z');
        });

        it("places everything as a new entry when the cell's tail abuts another row immediately (Scenario #5 zero-gap)", async () => {
            // Cell entry: 09:00 → 10:00. Another row starts EXACTLY at 10:00.
            // Gap after cell entry = 0. The whole add becomes a new entry
            // somewhere else.
            const cellEntry = entry('2026-04-10T09:00:00Z', '2026-04-10T10:00:00Z', {
                id: 'cell-entry',
            });
            const blocker = entry('2026-04-10T10:00:00Z', '2026-04-10T11:00:00Z', {
                id: 'blocker',
                project_id: 'other-project',
            });
            const { cellMutations } = setup([cellEntry, blocker]);
            const row = buildRow('p-1', [cellEntry]);

            // Bump cell from 1h to 2h → +1h
            await cellMutations.handleCellUpdate(row, 0, 2 * HOUR);

            // No update to cell entry (gap is zero)
            expect(apiMocks.updateTimeEntry).not.toHaveBeenCalled();
            // New entry placed in next free window: 11:00 → 12:00
            expect(apiMocks.createTimeEntry).toHaveBeenCalledTimes(1);
            const created = firstArg(apiMocks.createTimeEntry);
            expect(created.start).toBe('2026-04-10T11:00:00Z');
            expect(created.end).toBe('2026-04-10T12:00:00Z');
        });

        it('falls back to creating a new entry when the latest cell entry is running (Scenario #8)', async () => {
            // Cell contains only a running timer.
            const running = entry('2026-04-10T08:00:00Z', null, {
                id: 'running',
                duration: null,
            });
            // The grid would treat the cell total as 0 because duration is
            // null, so we fake a non-zero totalSeconds via a manual cell.
            const cell: TimesheetCell = {
                dayIndex: 0,
                date: DATE,
                entries: [running],
                // Pretend we computed 1h so that handleCellUpdate sees a
                // diff > 0 and routes through extendCell rather than
                // createCell.
                totalSeconds: HOUR,
            };
            const row: TimesheetRow = {
                key: 'p-1:null',
                projectId: 'p-1',
                taskId: null,
                billable: false,
                tags: [],
                cells: new Map([[0, cell]]),
                totalSeconds: HOUR,
            };

            const { cellMutations } = setup([running]);
            // Bump from 1h to 2h → +1h. extendCell sees the running entry
            // as the latest-end and falls through to createCell.
            await cellMutations.handleCellUpdate(row, 0, 2 * HOUR);

            expect(apiMocks.updateTimeEntry).not.toHaveBeenCalled();
            expect(apiMocks.createTimeEntry).toHaveBeenCalledTimes(1);
            // Running entry blocks [08:00, now=14:00). 09:00 lands inside
            // → cursor advances to 14:00. Crucially the running entry is
            // never modified.
            const created = firstArg(apiMocks.createTimeEntry);
            expect(created.start).toBe('2026-04-10T14:00:00Z');
            expect(created.end).toBe('2026-04-10T15:00:00Z');
        });

        it('places the remainder strictly after the just-extended end (no stale-overlap)', async () => {
            // Regression: timeEntries.value is stale right after the
            // updateEntry call (still shows candidate.end at the old
            // value). Without an explicit floor, findFreeWindowOnDay
            // would propose a window inside the gap we just filled —
            // overlapping the now-extended candidate.
            //
            // Cell entry: 09:00 → 10:00 (1h). Blocker on another row at
            // 11:00 → 12:00. Gap is 1h. Bump cell to 2.5h (+1.5h):
            //   - extend by 1h → newEnd = 11:00
            //   - remainder = 30 min must land AFTER 11:00, not in the
            //     stale-looking [10:00, 11:00] window.
            const cellEntry = entry('2026-04-10T09:00:00Z', '2026-04-10T10:00:00Z', {
                id: 'cell-entry',
            });
            const blocker = entry('2026-04-10T11:00:00Z', '2026-04-10T12:00:00Z', {
                id: 'blocker',
                project_id: 'other-project',
            });
            const { cellMutations } = setup([cellEntry, blocker]);
            const row = buildRow('p-1', [cellEntry]);

            await cellMutations.handleCellUpdate(row, 0, Math.round(2.5 * HOUR));

            expect(apiMocks.updateTimeEntry).toHaveBeenCalledTimes(1);
            const updated = firstArg(apiMocks.updateTimeEntry);
            expect(updated.id).toBe('cell-entry');
            expect(updated.end).toBe('2026-04-10T11:00:00Z');

            expect(apiMocks.createTimeEntry).toHaveBeenCalledTimes(1);
            const created = firstArg(apiMocks.createTimeEntry);
            // Next free window after blocker, at 12:00.
            expect(created.start).toBe('2026-04-10T12:00:00Z');
            expect(created.end).toBe('2026-04-10T12:30:00Z');
        });

        it('refuses extension that would cross midnight before patching the server (Scenario #7)', async () => {
            // Cell entry 22:00 → 23:00. Bump to 4h (+3h). Only 1h is
            // available before midnight. Because the remaining 2h don't
            // fit anywhere else on the day, the mutation now aborts
            // before issuing the PATCH, leaving the entry untouched.
            const cellEntry = entry('2026-04-10T22:00:00Z', '2026-04-10T23:00:00Z', {
                id: 'cell-entry',
            });
            const fillEarly = entry('2026-04-10T00:00:00Z', '2026-04-10T22:00:00Z', {
                id: 'fill',
                project_id: 'other-project',
            });
            const { cellMutations } = setup([cellEntry, fillEarly]);
            const row = buildRow('p-1', [cellEntry]);

            // Bump from 1h to 4h → +3h
            await cellMutations.handleCellUpdate(row, 0, 4 * HOUR);

            // Nothing should be patched or created because the preflight
            // fit-check rejects the whole edit.
            expect(apiMocks.updateTimeEntry).not.toHaveBeenCalled();
            expect(apiMocks.createTimeEntry).not.toHaveBeenCalled();
        });
    });

    // ── Shrink (unchanged behavior, still correct) ─────────────────

    describe('shrinkFromEnd', () => {
        it('shortens the latest entry by the requested amount', async () => {
            const cellEntry = entry('2026-04-10T09:00:00Z', '2026-04-10T12:00:00Z', {
                id: 'shrink-me',
            });
            const { cellMutations } = setup([cellEntry]);
            const row = buildRow('p-1', [cellEntry]);

            // 3h → 2h: shrink by 1h
            await cellMutations.handleCellUpdate(row, 0, 2 * HOUR);

            expect(apiMocks.updateTimeEntry).toHaveBeenCalledTimes(1);
            const updated = firstArg(apiMocks.updateTimeEntry);
            expect(updated.end).toBe('2026-04-10T11:00:00Z');
        });

        it('deletes entries that are entirely consumed', async () => {
            const a = entry('2026-04-10T09:00:00Z', '2026-04-10T10:00:00Z', {
                id: 'a',
            });
            const b = entry('2026-04-10T11:00:00Z', '2026-04-10T12:00:00Z', {
                id: 'b',
            });
            const { cellMutations } = setup([a, b]);
            const row = buildRow('p-1', [a, b]);

            // Cell = 2h, shrink to 0.5h: must delete b entirely (1h) and
            // shorten a from 1h to 30min.
            await cellMutations.handleCellUpdate(row, 0, 1800);

            expect(apiMocks.deleteTimeEntry).toHaveBeenCalledTimes(1);
            const [, deleteOptions] = apiMocks.deleteTimeEntry.mock.calls[0]!;
            expect(deleteOptions?.params?.timeEntry).toBe('b');

            expect(apiMocks.updateTimeEntry).toHaveBeenCalledTimes(1);
            const updated = firstArg(apiMocks.updateTimeEntry);
            expect(updated.id).toBe('a');
            expect(updated.end).toBe('2026-04-10T09:30:00Z');
        });
    });
});

describe('useTimesheetCellMutations save status', () => {
    // Timer handles keep old fade-outs from clearing newer status, and
    // the same-cell saving guard prevents concurrent writes from stale rows.

    it('does not let a stale fade-out timer clear a newer edit on the same cell', async () => {
        const { cellMutations } = setup([]);
        const row = buildEmptyRow('p-1');
        const key = makeCellStatusKey(row.key, 0);

        await cellMutations.handleCellUpdate(row, 0, HOUR);
        expect(cellMutations.cellStatus.value[key]).toBe('saved');

        // Re-edit the same cell partway through the first "saved" window.
        vi.advanceTimersByTime(1000);
        await cellMutations.handleCellUpdate(row, 0, 2 * HOUR);
        expect(cellMutations.cellPendingSeconds.value[key]).toBe(2 * HOUR);

        // Advance past the FIRST timer's deadline: it must not wipe the newer state.
        vi.advanceTimersByTime(2000);
        expect(cellMutations.cellStatus.value[key]).toBe('saved');
        expect(cellMutations.cellPendingSeconds.value[key]).toBe(2 * HOUR);
    });

    it('ignores another commit while the same cell is saving', async () => {
        const { cellMutations } = setup([]);
        const row = buildEmptyRow('p-1');
        const key = makeCellStatusKey(row.key, 0);

        let release!: () => void;
        const gateA = new Promise<void>((res) => {
            release = () => res();
        });
        apiMocks.createTimeEntry.mockImplementationOnce(async () => {
            await gateA;
            return { data: { id: 'a' } } as never;
        });

        const save = cellMutations.handleCellUpdate(row, 0, HOUR);
        expect(cellMutations.cellStatus.value[key]).toBe('saving');
        expect(cellMutations.cellPendingSeconds.value[key]).toBe(HOUR);

        // The second commit would be planned from the same stale row, so it is ignored.
        await cellMutations.handleCellUpdate(row, 0, 2 * HOUR);
        expect(apiMocks.createTimeEntry).toHaveBeenCalledTimes(1);
        expect(cellMutations.cellPendingSeconds.value[key]).toBe(HOUR);

        release();
        await save;
        expect(cellMutations.cellStatus.value[key]).toBe('saved');
        expect(cellMutations.cellPendingSeconds.value[key]).toBe(HOUR);
    });

    it('marks error and drops the optimistic value when the save fails', async () => {
        const { cellMutations } = setup([]);
        const row = buildEmptyRow('p-1');
        const key = makeCellStatusKey(row.key, 0);

        apiMocks.createTimeEntry.mockRejectedValueOnce(new Error('boom'));

        await cellMutations.handleCellUpdate(row, 0, HOUR);

        expect(cellMutations.cellStatus.value[key]).toBe('error');
        expect(cellMutations.cellPendingSeconds.value[key]).toBeUndefined();
        expect(addNotification).toHaveBeenCalledWith(
            'error',
            'Failed to update timesheet',
            expect.any(String)
        );
    });

    it('marks error and drops the optimistic value when the day is full', async () => {
        // Block all but the last 2h, then ask for 3h → NoFreeWindowError.
        const blocker = entry('2026-04-10T00:00:00Z', '2026-04-10T22:00:00Z', { id: 'blocker' });
        const { cellMutations } = setup([blocker]);
        const row = buildEmptyRow('p-1');
        const key = makeCellStatusKey(row.key, 0);

        await cellMutations.handleCellUpdate(row, 0, 3 * HOUR);

        expect(cellMutations.cellStatus.value[key]).toBe('error');
        expect(cellMutations.cellPendingSeconds.value[key]).toBeUndefined();
        expect(addNotification).toHaveBeenCalledWith(
            'error',
            "This day can't fit any more work",
            expect.any(String)
        );
    });

    it('creates no status when the committed value is unchanged', async () => {
        const cellEntry = entry('2026-04-10T09:00:00Z', '2026-04-10T10:00:00Z');
        const { cellMutations } = setup([cellEntry]);
        const row = buildRow('p-1', [cellEntry]);
        const key = makeCellStatusKey(row.key, 0);

        await cellMutations.handleCellUpdate(row, 0, HOUR);

        expect(cellMutations.cellStatus.value[key]).toBeUndefined();
        expect(cellMutations.cellPendingSeconds.value[key]).toBeUndefined();
    });

    it('tracks save status independently for each cell', async () => {
        const { cellMutations } = setup([]);
        const row = buildEmptyRow('p-1');
        const mondayKey = makeCellStatusKey(row.key, 0);
        const tuesdayKey = makeCellStatusKey(row.key, 1);

        await cellMutations.handleCellUpdate(row, 0, HOUR);
        await cellMutations.handleCellUpdate(row, 1, 2 * HOUR);

        expect(cellMutations.cellStatus.value[mondayKey]).toBe('saved');
        expect(cellMutations.cellStatus.value[tuesdayKey]).toBe('saved');
        expect(cellMutations.cellPendingSeconds.value[mondayKey]).toBe(HOUR);
        expect(cellMutations.cellPendingSeconds.value[tuesdayKey]).toBe(2 * HOUR);
    });
});
