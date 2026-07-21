import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ref } from 'vue';
import { createPinia, setActivePinia } from 'pinia';
import { useBreakPlacement, BreakPlacementDeferred } from './useBreakPlacement';
import { api } from '@/packages/api/src';
import type { TimeEntry } from '@/packages/api/src';
import type { TimesheetRow } from '@/utils/useTimesheetGrid';

const addNotification = vi.fn();

vi.mock('@/utils/useUser', () => ({
    getCurrentOrganizationId: vi.fn(() => 'org-1'),
    getCurrentMembershipId: vi.fn(() => 'mem-1'),
}));

vi.mock('@tanstack/vue-query', () => ({
    useQueryClient: () => ({ invalidateQueries: vi.fn() }),
}));

vi.mock('@/utils/notification', () => ({
    useNotificationsStore: () => ({ addNotification }),
}));

vi.mock('@/packages/api/src', () => ({
    api: {
        createTimeEntry: vi.fn(async () => ({ data: { id: 'new-id' } })),
        updateTimeEntry: vi.fn(async () => undefined),
        deleteTimeEntry: vi.fn(async () => undefined),
        deleteTimeEntries: vi.fn(async () => undefined),
    },
}));

const apiMocks = vi.mocked(api);
const DATE = '2026-04-10';
const HOUR = 3600;

function entry(start: string, end: string | null, overrides: Partial<TimeEntry> = {}): TimeEntry {
    return {
        id: overrides.id ?? `e-${start}`,
        start,
        end,
        description: '',
        member_id: 'mem-1',
        project_id: 'p-1',
        task_id: null,
        billable: false,
        tags: [],
        type: 'work',
        ...overrides,
    } as unknown as TimeEntry;
}

const breakRow: TimesheetRow = {
    key: 'break-row',
    projectId: null,
    taskId: null,
    billable: false,
    tags: [],
    type: 'break',
    cells: new Map(),
    totalSeconds: 0,
};

function setup(allEntries: TimeEntry[]) {
    const createCell = vi.fn(async () => undefined);
    const updateEntry = vi.fn(async () => undefined);
    const bp = useBreakPlacement({
        weekDays: ref([DATE, '2026-04-11', '2026-04-12']),
        timeEntries: ref(allEntries),
        requireOrgId: () => 'org-1',
        createCell,
        updateEntry,
    });
    return { bp, createCell, updateEntry };
}

beforeEach(() => {
    setActivePinia(createPinia());
    apiMocks.createTimeEntry.mockClear();
    apiMocks.updateTimeEntry.mockClear();
    addNotification.mockClear();
});

describe('useBreakPlacement.placeBreak', () => {
    it('saves the break directly when it drops into a valid gap', async () => {
        const morning = entry('2026-04-10T09:00:00Z', '2026-04-10T12:00:00Z', {
            id: 'morning',
        });
        const afternoon = entry('2026-04-10T13:00:00Z', '2026-04-10T17:00:00Z', {
            id: 'afternoon',
        });
        const { bp } = setup([morning, afternoon]);

        await bp.placeBreak(breakRow, 0, HOUR); // exactly fills the 12:00-13:00 gap

        expect(apiMocks.createTimeEntry).toHaveBeenCalledTimes(1);
        expect(apiMocks.createTimeEntry.mock.calls[0]![0]).toEqual(
            expect.objectContaining({
                type: 'break',
                start: '2026-04-10T12:00:00Z',
                end: '2026-04-10T13:00:00Z',
            })
        );
        expect(bp.breakPlacementRequest.value).toBeNull();
    });

    it('never places a break over a running entry', async () => {
        const morning = entry('2026-04-10T09:00:00Z', '2026-04-10T12:00:00Z', { id: 'morning' });
        const afternoon = entry('2026-04-10T13:00:00Z', '2026-04-10T17:00:00Z', {
            id: 'afternoon',
        });
        const running = entry('2026-04-10T12:30:00Z', null, { id: 'running' });
        const { bp } = setup([morning, afternoon, running]);

        // Centered placement (12:15-12:45) would overlap the running entry, so
        // the break slides to the free part of the gap instead.
        await bp.placeBreak(breakRow, 0, HOUR / 2);

        expect(apiMocks.createTimeEntry).toHaveBeenCalledTimes(1);
        expect(apiMocks.createTimeEntry.mock.calls[0]![0]).toEqual(
            expect.objectContaining({
                type: 'break',
                start: '2026-04-10T12:00:00Z',
                end: '2026-04-10T12:30:00Z',
            })
        );
    });

    it('defers to the split modal when a single work entry blocks every gap', async () => {
        const work = entry('2026-04-10T09:00:00Z', '2026-04-10T17:00:00Z', { id: 'w1' });
        const { bp } = setup([work]);

        await expect(bp.placeBreak(breakRow, 0, HOUR)).rejects.toBeInstanceOf(
            BreakPlacementDeferred
        );
        expect(bp.breakPlacementRequest.value).toEqual(
            expect.objectContaining({
                durationSeconds: HOUR,
                replaceBreakId: null,
                workEntries: [expect.objectContaining({ id: 'w1' })],
            })
        );
        expect(apiMocks.createTimeEntry).not.toHaveBeenCalled();
    });
});

describe('useBreakPlacement.applyBreakPlacement (split)', () => {
    it('shrinks the original, creates the second half, and saves the break', async () => {
        const work = entry('2026-04-10T09:00:00Z', '2026-04-10T17:00:00Z', { id: 'w1' });
        const { bp, updateEntry } = setup([work]);

        // Open the placement request, then commit the break at noon.
        await bp.placeBreak(breakRow, 0, HOUR).catch(() => undefined);
        await bp.applyBreakPlacement('2026-04-10T12:00:00Z', HOUR);

        // Original work shrunk to its first half.
        expect(updateEntry).toHaveBeenCalledWith(
            expect.objectContaining({
                id: 'w1',
                start: '2026-04-10T09:00:00Z',
                end: '2026-04-10T12:00:00Z',
            })
        );
        // Second half of work + the break both created.
        const created = apiMocks.createTimeEntry.mock.calls.map((c) => c[0]);
        expect(created).toEqual(
            expect.arrayContaining([
                expect.objectContaining({
                    type: 'work',
                    start: '2026-04-10T13:00:00Z',
                    end: '2026-04-10T17:00:00Z',
                }),
                expect.objectContaining({
                    type: 'break',
                    start: '2026-04-10T12:00:00Z',
                    end: '2026-04-10T13:00:00Z',
                }),
            ])
        );
        // Request cleared and a success toast surfaced.
        expect(bp.breakPlacementRequest.value).toBeNull();
        expect(addNotification).toHaveBeenCalledWith('success', 'Break added', expect.any(String));
    });

    it('does nothing when there is no pending placement request', async () => {
        const { bp, updateEntry } = setup([]);
        await bp.applyBreakPlacement('2026-04-10T12:00:00Z', HOUR);
        expect(updateEntry).not.toHaveBeenCalled();
        expect(apiMocks.createTimeEntry).not.toHaveBeenCalled();
    });
});
