import { describe, it, expect, vi, beforeEach } from 'vitest';
import { ref } from 'vue';
import { createPinia, setActivePinia } from 'pinia';
import { useBreakPlacement } from './useBreakPlacement';
import { NoFreeWindowError } from './cellMath';
import { api } from '@/packages/api/src';
import type { TimeEntry } from '@/packages/api/src';
import type { TimesheetRow } from '@/utils/useTimesheetGrid';

const addNotification = vi.fn();
const mutationOptionsSpy = vi.hoisted(() => vi.fn());

vi.mock('@/utils/useUser', () => ({
    getCurrentOrganizationId: vi.fn(() => 'org-1'),
    getCurrentMembershipId: vi.fn(() => 'mem-1'),
}));

vi.mock('@tanstack/vue-query', () => ({
    useQueryClient: () => ({ invalidateQueries: vi.fn() }),
    useMutation: (options: {
        mutationFn: (variables: unknown) => Promise<unknown>;
        onSuccess?: (data: unknown, variables: unknown) => void | Promise<void>;
        onError?: (error: unknown, variables: unknown) => void | Promise<void>;
        onSettled?: () => void | Promise<void>;
    }) => {
        mutationOptionsSpy(options);
        return {
            mutateAsync: async (variables: unknown) => {
                try {
                    const data = await options.mutationFn(variables);
                    await options.onSuccess?.(data, variables);
                    return data;
                } catch (error) {
                    await options.onError?.(error, variables);
                    throw error;
                } finally {
                    await options.onSettled?.();
                }
            },
        };
    },
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

function setup(allEntries: TimeEntry[], preventOverlaps = false) {
    const createCell = vi.fn(async () => undefined);
    const updateEntry = vi.fn(async (_entry: TimeEntry) => undefined);
    const deleteEntry = vi.fn(async (_id: string) => undefined);
    const bp = useBreakPlacement({
        weekDays: ref([DATE, '2026-04-11', '2026-04-12']),
        timeEntries: ref(allEntries),
        requireOrgId: () => 'org-1',
        createCell,
        updateEntry,
        deleteEntry,
        preventOverlappingTimeEntries: () => preventOverlaps,
    });
    return { bp, createCell, updateEntry, deleteEntry };
}

beforeEach(() => {
    setActivePinia(createPinia());
    apiMocks.createTimeEntry.mockClear();
    apiMocks.updateTimeEntry.mockClear();
    apiMocks.deleteTimeEntry.mockClear();
    addNotification.mockClear();
    mutationOptionsSpy.mockClear();
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

    it('parks the break next to work too short to split instead of refusing it', async () => {
        const work = entry('2026-04-10T09:00:00Z', '2026-04-10T09:01:00Z', { id: 'w1' });
        const { bp } = setup([work]);

        await bp.placeBreak(breakRow, 0, HOUR / 2);

        expect(apiMocks.createTimeEntry).toHaveBeenCalledTimes(1);
        expect(apiMocks.createTimeEntry.mock.calls[0]![0]).toEqual(
            expect.objectContaining({
                type: 'break',
                start: '2026-04-10T09:01:00Z',
                end: '2026-04-10T09:31:00Z',
            })
        );
        expect(bp.breakPlacementRequest.value).toBeNull();
    });

    it('still offers a split when a running entry caps the end of the day', async () => {
        const work = entry('2026-04-10T09:00:00Z', '2026-04-10T12:00:00Z', { id: 'w1' });
        const running = entry('2026-04-10T12:00:00Z', null, { id: 'running' });
        const { bp } = setup([work, running]);

        await expect(bp.placeBreak(breakRow, 0, HOUR)).resolves.toBe('needs-input');
        expect(bp.breakPlacementRequest.value?.defaultBreakStart).toBe('2026-04-10T09:30:00Z');

        await bp.applyBreakPlacement('2026-04-10T09:30:00Z', HOUR);
        const created = apiMocks.createTimeEntry.mock.calls.map((c) => c[0]);
        expect(created).toEqual(
            expect.arrayContaining([
                expect.objectContaining({
                    type: 'work',
                    start: '2026-04-10T10:30:00Z',
                    end: '2026-04-10T12:00:00Z',
                }),
                expect.objectContaining({
                    type: 'break',
                    start: '2026-04-10T09:30:00Z',
                    end: '2026-04-10T10:30:00Z',
                }),
            ])
        );
    });

    it('drops a new break into the first free window when the day has no work', async () => {
        const { bp, createCell } = setup([]);
        await bp.placeBreak(breakRow, 0, HOUR);
        expect(createCell).toHaveBeenCalledWith(breakRow, 0, HOUR);
        expect(apiMocks.createTimeEntry).not.toHaveBeenCalled();
    });

    it('resizes a break on a workless day in place while it still fits', async () => {
        const brk = entry('2026-04-10T20:00:00Z', '2026-04-10T20:30:00Z', {
            id: 'b1',
            type: 'break',
        });
        const { bp, updateEntry } = setup([brk]);

        await bp.placeBreak(breakRow, 0, HOUR, 'b1');

        expect(updateEntry).toHaveBeenCalledWith(
            expect.objectContaining({
                id: 'b1',
                start: '2026-04-10T20:00:00Z',
                end: '2026-04-10T21:00:00Z',
            })
        );
    });

    it('slides a workless-day break earlier rather than growing it past midnight', async () => {
        const brk = entry('2026-04-10T23:00:00Z', '2026-04-10T23:30:00Z', {
            id: 'b1',
            type: 'break',
        });
        const { bp, updateEntry } = setup([brk]);

        await bp.placeBreak(breakRow, 0, 2 * HOUR, 'b1');

        expect(updateEntry).toHaveBeenCalledWith(
            expect.objectContaining({
                id: 'b1',
                start: '2026-04-10T22:00:00Z',
                end: '2026-04-11T00:00:00Z',
            })
        );
    });

    it('refuses a workless-day resize the day genuinely cannot hold', async () => {
        const brk = entry('2026-04-10T23:00:00Z', '2026-04-10T23:30:00Z', {
            id: 'b1',
            type: 'break',
        });
        const other = entry('2026-04-10T06:00:00Z', '2026-04-10T18:00:00Z', {
            id: 'b2',
            type: 'break',
        });
        const { bp, updateEntry } = setup([brk, other]);

        await expect(bp.placeBreak(breakRow, 0, 20 * HOUR, 'b1')).rejects.toBeInstanceOf(
            NoFreeWindowError
        );
        expect(updateEntry).not.toHaveBeenCalled();
    });

    it('requests input in the split modal when a single work entry blocks every gap', async () => {
        const work = entry('2026-04-10T09:00:00Z', '2026-04-10T17:00:00Z', { id: 'w1' });
        const { bp } = setup([work]);

        await expect(bp.placeBreak(breakRow, 0, HOUR)).resolves.toBe('needs-input');
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
    it('configures the logical placement as a serialized, non-retrying mutation', () => {
        setup([]);

        expect(mutationOptionsSpy).toHaveBeenCalledWith(
            expect.objectContaining({
                mutationKey: ['timesheet', 'break-placement'],
                scope: { id: 'timesheet-break-placement' },
                retry: false,
            })
        );
    });

    it('shrinks the original, pushes the rest of the work out, and saves the break', async () => {
        const work = entry('2026-04-10T09:00:00Z', '2026-04-10T17:00:00Z', { id: 'w1' });
        const { bp, updateEntry } = setup([work]);

        await bp.placeBreak(breakRow, 0, HOUR);
        await bp.applyBreakPlacement('2026-04-10T12:00:00Z', HOUR);

        expect(updateEntry).toHaveBeenCalledWith(
            expect.objectContaining({
                id: 'w1',
                start: '2026-04-10T09:00:00Z',
                end: '2026-04-10T12:00:00Z',
            })
        );
        const created = apiMocks.createTimeEntry.mock.calls.map((c) => c[0]);
        expect(created).toEqual(
            expect.arrayContaining([
                expect.objectContaining({
                    type: 'work',
                    start: '2026-04-10T13:00:00Z',
                    end: '2026-04-10T18:00:00Z',
                }),
                expect.objectContaining({
                    type: 'break',
                    start: '2026-04-10T12:00:00Z',
                    end: '2026-04-10T13:00:00Z',
                }),
            ])
        );
        expect(bp.breakPlacementRequest.value).toBeNull();
        expect(addNotification).toHaveBeenCalledWith('success', 'Break added', expect.any(String));
    });

    it('keeps the work length when the break is as long as the work entry', async () => {
        const work = entry('2026-04-10T09:00:00Z', '2026-04-10T10:00:00Z', { id: 'w1' });
        const { bp, updateEntry } = setup([work]);

        await bp.placeBreak(breakRow, 0, HOUR);
        expect(bp.breakPlacementRequest.value?.defaultBreakStart).toBe('2026-04-10T09:30:00Z');
        await bp.applyBreakPlacement('2026-04-10T09:30:00Z', HOUR);

        expect(updateEntry).toHaveBeenCalledWith(
            expect.objectContaining({
                id: 'w1',
                start: '2026-04-10T09:00:00Z',
                end: '2026-04-10T09:30:00Z',
            })
        );
        const created = apiMocks.createTimeEntry.mock.calls.map((c) => c[0]);
        expect(created).toEqual(
            expect.arrayContaining([
                expect.objectContaining({
                    type: 'work',
                    start: '2026-04-10T10:30:00Z',
                    end: '2026-04-10T11:00:00Z',
                }),
                expect.objectContaining({
                    type: 'break',
                    start: '2026-04-10T09:30:00Z',
                    end: '2026-04-10T10:30:00Z',
                }),
            ])
        );
    });

    it('accepts a break longer than the work entry', async () => {
        const work = entry('2026-04-10T09:00:00Z', '2026-04-10T10:00:00Z', { id: 'w1' });
        const { bp } = setup([work]);

        await bp.placeBreak(breakRow, 0, 3 * HOUR);
        await bp.applyBreakPlacement('2026-04-10T09:30:00Z', 3 * HOUR);

        const created = apiMocks.createTimeEntry.mock.calls.map((c) => c[0]);
        expect(created).toEqual(
            expect.arrayContaining([
                expect.objectContaining({
                    type: 'work',
                    start: '2026-04-10T12:30:00Z',
                    end: '2026-04-10T13:00:00Z',
                }),
                expect.objectContaining({
                    type: 'break',
                    start: '2026-04-10T09:30:00Z',
                    end: '2026-04-10T12:30:00Z',
                }),
            ])
        );
        expect(addNotification).toHaveBeenCalledWith('success', 'Break added', expect.any(String));
    });

    it('moves an existing break out of the way of the pushed-out work', async () => {
        const work = entry('2026-04-10T09:00:00Z', '2026-04-10T12:00:00Z', { id: 'w1' });
        const existingBreak = entry('2026-04-10T12:00:00Z', '2026-04-10T12:15:00Z', {
            id: 'b1',
            type: 'break',
        });
        const { bp, updateEntry } = setup([work, existingBreak]);

        await bp.placeBreak(breakRow, 0, HOUR);
        await bp.applyBreakPlacement('2026-04-10T10:00:00Z', HOUR);

        expect(updateEntry).toHaveBeenNthCalledWith(
            1,
            expect.objectContaining({
                id: 'w1',
                start: '2026-04-10T09:00:00Z',
                end: '2026-04-10T10:00:00Z',
            })
        );
        expect(updateEntry).toHaveBeenNthCalledWith(
            2,
            expect.objectContaining({
                id: 'b1',
                start: '2026-04-10T13:00:00Z',
                end: '2026-04-10T13:15:00Z',
            })
        );
        const created = apiMocks.createTimeEntry.mock.calls.map((c) => c[0]);
        expect(created).toEqual(
            expect.arrayContaining([
                expect.objectContaining({
                    type: 'work',
                    start: '2026-04-10T11:00:00Z',
                    end: '2026-04-10T13:00:00Z',
                }),
                expect.objectContaining({
                    type: 'break',
                    start: '2026-04-10T10:00:00Z',
                    end: '2026-04-10T11:00:00Z',
                }),
            ])
        );
    });

    it('moves a replacement break before creating work through its old slot', async () => {
        const work = entry('2026-04-10T09:00:00Z', '2026-04-10T17:00:00Z', { id: 'w1' });
        const existingBreak = entry('2026-04-10T17:00:00Z', '2026-04-10T17:30:00Z', {
            id: 'b1',
            type: 'break',
        });
        const { bp, updateEntry } = setup([work, existingBreak], true);

        await bp.placeBreak(breakRow, 0, HOUR, 'b1');
        await bp.applyBreakPlacement('2026-04-10T12:00:00Z', HOUR);

        expect(updateEntry).toHaveBeenNthCalledWith(
            1,
            expect.objectContaining({ id: 'w1', end: '2026-04-10T12:00:00Z' })
        );
        expect(updateEntry).toHaveBeenNthCalledWith(
            2,
            expect.objectContaining({
                id: 'b1',
                start: '2026-04-10T12:00:00Z',
                end: '2026-04-10T13:00:00Z',
            })
        );
        expect(apiMocks.createTimeEntry).toHaveBeenCalledTimes(1);
        expect(apiMocks.createTimeEntry.mock.calls[0]![0]).toEqual(
            expect.objectContaining({
                type: 'work',
                start: '2026-04-10T13:00:00Z',
                end: '2026-04-10T18:00:00Z',
            })
        );
    });

    it('restores the original entry when creating the second half fails', async () => {
        const work = entry('2026-04-10T09:00:00Z', '2026-04-10T17:00:00Z', { id: 'w1' });
        const { bp, updateEntry } = setup([work]);
        apiMocks.createTimeEntry.mockRejectedValueOnce(new Error('boom'));

        await bp.placeBreak(breakRow, 0, HOUR);
        await expect(bp.applyBreakPlacement('2026-04-10T12:00:00Z', HOUR)).rejects.toThrow('boom');

        expect(updateEntry).toHaveBeenNthCalledWith(
            1,
            expect.objectContaining({
                id: 'w1',
                start: '2026-04-10T09:00:00Z',
                end: '2026-04-10T12:00:00Z',
            })
        );
        expect(updateEntry).toHaveBeenNthCalledWith(
            2,
            expect.objectContaining({
                id: 'w1',
                start: '2026-04-10T09:00:00Z',
                end: '2026-04-10T17:00:00Z',
            })
        );
        expect(addNotification).toHaveBeenCalledWith(
            'error',
            'Failed to add break',
            expect.any(String)
        );
    });

    it('restores shifted breaks as well as the original when creating work fails', async () => {
        const work = entry('2026-04-10T09:00:00Z', '2026-04-10T12:00:00Z', { id: 'w1' });
        const existingBreak = entry('2026-04-10T12:00:00Z', '2026-04-10T12:15:00Z', {
            id: 'b1',
            type: 'break',
        });
        const { bp, updateEntry } = setup([work, existingBreak]);
        apiMocks.createTimeEntry.mockRejectedValueOnce(new Error('work create failed'));

        await bp.placeBreak(breakRow, 0, HOUR);
        await expect(bp.applyBreakPlacement('2026-04-10T10:00:00Z', HOUR)).rejects.toThrow(
            'work create failed'
        );

        expect(updateEntry.mock.calls.map((call) => call[0])).toEqual([
            expect.objectContaining({ id: 'w1', end: '2026-04-10T10:00:00Z' }),
            expect.objectContaining({
                id: 'b1',
                start: '2026-04-10T13:00:00Z',
                end: '2026-04-10T13:15:00Z',
            }),
            expect.objectContaining({
                id: 'b1',
                start: '2026-04-10T12:00:00Z',
                end: '2026-04-10T12:15:00Z',
            }),
            expect.objectContaining({ id: 'w1', end: '2026-04-10T12:00:00Z' }),
        ]);
    });

    it('deletes the created second half and restores updates when saving the break fails', async () => {
        const work = entry('2026-04-10T09:00:00Z', '2026-04-10T17:00:00Z', { id: 'w1' });
        const { bp, updateEntry, deleteEntry } = setup([work]);
        apiMocks.createTimeEntry
            .mockResolvedValueOnce({ data: { id: 'second-half' } } as never)
            .mockRejectedValueOnce(new Error('break create failed'));

        await bp.placeBreak(breakRow, 0, HOUR);
        await expect(bp.applyBreakPlacement('2026-04-10T12:00:00Z', HOUR)).rejects.toThrow(
            'break create failed'
        );

        expect(deleteEntry).toHaveBeenCalledWith('second-half');
        expect(updateEntry).toHaveBeenLastCalledWith(
            expect.objectContaining({ id: 'w1', end: '2026-04-10T17:00:00Z' })
        );
        expect(bp.breakPlacementRequest.value).not.toBeNull();
    });

    it('surfaces incomplete recovery and closes a stale placement request', async () => {
        const work = entry('2026-04-10T09:00:00Z', '2026-04-10T17:00:00Z', { id: 'w1' });
        const { bp, updateEntry } = setup([work]);
        updateEntry
            .mockResolvedValueOnce(undefined)
            .mockRejectedValueOnce(new Error('restore failed'));
        apiMocks.createTimeEntry.mockRejectedValueOnce(new Error('create failed'));

        await bp.placeBreak(breakRow, 0, HOUR);
        await expect(bp.applyBreakPlacement('2026-04-10T12:00:00Z', HOUR)).rejects.toThrow(
            'create failed'
        );

        expect(addNotification).toHaveBeenCalledWith(
            'error',
            'Break placement needs attention',
            expect.any(String)
        );
        expect(bp.breakPlacementRequest.value).toBeNull();
    });

    it('does nothing when there is no pending placement request', async () => {
        const { bp, updateEntry } = setup([]);
        await bp.applyBreakPlacement('2026-04-10T12:00:00Z', HOUR);
        expect(updateEntry).not.toHaveBeenCalled();
        expect(apiMocks.createTimeEntry).not.toHaveBeenCalled();
    });
});

describe('useBreakPlacement.applyBreakPlacement (move)', () => {
    it('rejects a replacement dependency cycle before changing any entry', async () => {
        const morning = entry('2026-04-10T09:00:00Z', '2026-04-10T12:00:00Z', {
            id: 'morning',
        });
        const afternoon = entry('2026-04-10T12:00:00Z', '2026-04-10T17:00:00Z', {
            id: 'afternoon',
        });
        const existingBreak = entry('2026-04-10T17:00:00Z', '2026-04-10T17:30:00Z', {
            id: 'b1',
            type: 'break',
        });
        const { bp, updateEntry } = setup([morning, afternoon, existingBreak], true);

        await bp.placeBreak(breakRow, 0, HOUR, 'b1');
        await expect(bp.applyBreakPlacement('2026-04-10T12:00:00Z', HOUR)).rejects.toBeInstanceOf(
            NoFreeWindowError
        );

        expect(updateEntry).not.toHaveBeenCalled();
        expect(apiMocks.createTimeEntry).not.toHaveBeenCalled();
    });
});
