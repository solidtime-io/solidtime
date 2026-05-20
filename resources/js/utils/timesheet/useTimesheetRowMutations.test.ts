import { beforeEach, describe, expect, it, vi } from 'vitest';
import { ref } from 'vue';
import type { Project, TimeEntry } from '@/packages/api/src';
import type { TimesheetCell, TimesheetRow } from '@/utils/useTimesheetGrid';
import { useTimesheetRowMutations } from './useTimesheetRowMutations';

const addNotification = vi.fn();

vi.mock('@/utils/notification', () => ({
    useNotificationsStore: () => ({
        addNotification,
    }),
}));

function project(id: string, isBillable: boolean): Project {
    return {
        id,
        name: id,
        is_billable: isBillable,
    } as unknown as Project;
}

function entry(id: string): TimeEntry {
    return {
        id,
        start: '2026-04-10T09:00:00Z',
        end: '2026-04-10T10:00:00Z',
        duration: 3600,
        description: '',
        member_id: 'm-1',
        project_id: 'p-1',
        task_id: null,
        billable: false,
        tags: [],
    } as unknown as TimeEntry;
}

function buildCell(entries: TimeEntry[]): TimesheetCell {
    return {
        dayIndex: 0,
        date: '2026-04-10',
        entries,
        totalSeconds: entries.reduce((sum, item) => sum + (item.duration ?? 0), 0),
    };
}

function buildRow(key: string, projectId: string | null, entries: TimeEntry[]): TimesheetRow {
    const cells = entries.length > 0 ? new Map([[0, buildCell(entries)]]) : new Map();
    const totalSeconds = entries.reduce((sum, item) => sum + (item.duration ?? 0), 0);

    return {
        key,
        projectId,
        taskId: null,
        billable: false,
        tags: [],
        cells,
        totalSeconds,
    };
}

describe('useTimesheetRowMutations', () => {
    beforeEach(() => {
        addNotification.mockClear();
    });

    it('collapses a populated row into an existing matching row after identity change', async () => {
        const existingRow = buildRow('row-a', 'p-1', [entry('e-a')]);
        const editedRow = buildRow('row-b', 'p-2', [entry('e-b')]);
        const rows = ref([existingRow, editedRow]);
        const updateTimeEntries = vi.fn().mockResolvedValue(undefined);
        const addSlot = vi.fn();
        const updateSlot = vi.fn();
        const removeSlot = vi.fn();

        const { handleRowIdentityChange } = useTimesheetRowMutations(
            { updateTimeEntries },
            ref<Project[]>([]),
            rows,
            addSlot,
            updateSlot,
            removeSlot
        );

        await handleRowIdentityChange(editedRow, { projectId: 'p-1' });

        expect(updateTimeEntries).toHaveBeenCalledWith({
            ids: ['e-b'],
            changes: { project_id: 'p-1' },
        });
        expect(removeSlot).toHaveBeenCalledWith('row-b');
        expect(updateSlot).not.toHaveBeenCalled();
        expect(addNotification).toHaveBeenCalledWith(
            'success',
            'Merged into matching row',
            'Another row with the same project, task, billable status and tags already exists.'
        );
    });

    it('translates identity fields to snake_case API field names', async () => {
        const row = buildRow('row-a', 'p-1', [entry('e-a')]);
        const rows = ref([row]);
        const updateTimeEntries = vi.fn().mockResolvedValue(undefined);

        const { handleRowIdentityChange } = useTimesheetRowMutations(
            { updateTimeEntries },
            ref<Project[]>([]),
            rows,
            vi.fn(),
            vi.fn(),
            vi.fn()
        );

        await handleRowIdentityChange(row, {
            projectId: 'p-2',
            taskId: 't-1',
            billable: true,
            tags: ['tag-1'],
        });

        expect(updateTimeEntries).toHaveBeenCalledWith({
            ids: ['e-a'],
            changes: {
                project_id: 'p-2',
                task_id: 't-1',
                billable: true,
                tags: ['tag-1'],
            },
        });
    });

    it('only includes touched fields in the API changeset', async () => {
        const row = buildRow('row-a', 'p-1', [entry('e-a')]);
        const rows = ref([row]);
        const updateTimeEntries = vi.fn().mockResolvedValue(undefined);

        const { handleRowIdentityChange } = useTimesheetRowMutations(
            { updateTimeEntries },
            ref<Project[]>([]),
            rows,
            vi.fn(),
            vi.fn(),
            vi.fn()
        );

        await handleRowIdentityChange(row, { tags: ['tag-1'] });

        expect(updateTimeEntries).toHaveBeenCalledWith({
            ids: ['e-a'],
            changes: { tags: ['tag-1'] },
        });
    });

    it('keeps an empty duplicate row until it receives time', async () => {
        const existingRow = buildRow('row-a', 'p-1', [entry('e-a')]);
        const emptyRow = buildRow('row-b', 'p-2', []);
        const rows = ref([existingRow, emptyRow]);
        const updateTimeEntries = vi.fn().mockResolvedValue(undefined);
        const addSlot = vi.fn();
        const updateSlot = vi.fn();
        const removeSlot = vi.fn();

        const { handleRowIdentityChange } = useTimesheetRowMutations(
            { updateTimeEntries },
            ref<Project[]>([]),
            rows,
            addSlot,
            updateSlot,
            removeSlot
        );

        await handleRowIdentityChange(emptyRow, { projectId: 'p-1' });

        expect(updateTimeEntries).not.toHaveBeenCalled();
        expect(updateSlot).toHaveBeenCalledWith('row-b', {
            projectId: 'p-1',
            taskId: null,
            billable: false,
            tags: [],
        });
        expect(removeSlot).not.toHaveBeenCalled();
    });

    it('sends null project updates when an existing row changes to no project', async () => {
        const row = buildRow('row-a', 'p-1', [entry('e-a')]);
        const rows = ref([row]);
        const updateTimeEntries = vi.fn().mockResolvedValue(undefined);
        const updateSlot = vi.fn();

        const { handleRowIdentityChange } = useTimesheetRowMutations(
            { updateTimeEntries },
            ref<Project[]>([]),
            rows,
            vi.fn(),
            updateSlot,
            vi.fn()
        );

        await handleRowIdentityChange(row, { projectId: null });

        expect(updateTimeEntries).toHaveBeenCalledWith({
            ids: ['e-a'],
            changes: { project_id: null },
        });
        expect(updateSlot).toHaveBeenCalledWith('row-a', {
            projectId: null,
            taskId: null,
            billable: false,
            tags: [],
        });
    });

    it('defaults billable from the selected project when an empty row picks its first project', async () => {
        const emptyRow = buildRow('row-a', null, []);
        const rows = ref([emptyRow]);
        const updateTimeEntries = vi.fn().mockResolvedValue(undefined);
        const updateSlot = vi.fn();

        const { handleRowIdentityChange } = useTimesheetRowMutations(
            { updateTimeEntries },
            ref([project('p-billable', true)]),
            rows,
            vi.fn(),
            updateSlot,
            vi.fn()
        );

        await handleRowIdentityChange(emptyRow, { projectId: 'p-billable' });

        expect(updateTimeEntries).not.toHaveBeenCalled();
        expect(updateSlot).toHaveBeenCalledWith('row-a', {
            projectId: 'p-billable',
            taskId: null,
            billable: true,
            tags: [],
        });
    });

    it('handleAddRow defaults billable from the selected project metadata', () => {
        const addSlot = vi.fn();

        const { handleAddRow } = useTimesheetRowMutations(
            { updateTimeEntries: vi.fn() },
            ref([project('p-billable', true)]),
            ref<TimesheetRow[]>([]),
            addSlot,
            vi.fn(),
            vi.fn()
        );

        handleAddRow('p-billable', 't-1');

        expect(addSlot).toHaveBeenCalledWith('p-billable', 't-1', true, []);
    });

    it('can add a no-project row', () => {
        const addSlot = vi.fn();

        const { handleAddRow } = useTimesheetRowMutations(
            { updateTimeEntries: vi.fn() },
            ref([project('p-billable', true)]),
            ref<TimesheetRow[]>([]),
            addSlot,
            vi.fn(),
            vi.fn()
        );

        handleAddRow(null, null);

        expect(addSlot).toHaveBeenCalledWith(null, null, false, []);
    });
});
