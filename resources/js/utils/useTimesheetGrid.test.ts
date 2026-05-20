import { describe, expect, it } from 'vitest';
import { nextTick, ref } from 'vue';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import type { Dayjs } from 'dayjs';
import type { Project, Task, TimeEntry } from '@/packages/api/src';
import { useTimesheetGrid } from './useTimesheetGrid';

dayjs.extend(utc);

const WEEK_DAYS = [
    '2026-04-06',
    '2026-04-07',
    '2026-04-08',
    '2026-04-09',
    '2026-04-10',
    '2026-04-11',
    '2026-04-12',
];

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

function project(id: string, name: string, isBillable = false): Project {
    return {
        id,
        name,
        is_billable: isBillable,
    } as unknown as Project;
}

function task(id: string, name: string, projectId: string): Task {
    return {
        id,
        name,
        project_id: projectId,
    } as unknown as Task;
}

describe('useTimesheetGrid', () => {
    it('seeds unseen identities and re-sorts seeded rows when project metadata changes', async () => {
        const timeEntries = ref([
            entry('2026-04-10T09:00:00Z', '2026-04-10T10:00:00Z', {
                id: 'seed-b',
                project_id: 'p-b',
                task_id: null,
            }),
            entry('2026-04-10T10:00:00Z', '2026-04-10T11:00:00Z', {
                id: 'seed-a-z',
                project_id: 'p-a',
                task_id: 't-z',
            }),
            entry('2026-04-10T11:00:00Z', '2026-04-10T12:00:00Z', {
                id: 'seed-a-a',
                project_id: 'p-a',
                task_id: 't-a',
            }),
        ]);
        const projects = ref([project('p-a', 'Bravo'), project('p-b', 'Alpha')]);
        const tasks = ref([task('t-z', 'Zulu Task', 'p-a'), task('t-a', 'Alpha Task', 'p-a')]);

        const { rows } = useTimesheetGrid(
            timeEntries,
            ref(WEEK_DAYS),
            projects,
            tasks,
            ref<Dayjs | null>(null)
        );

        expect(rows.value.map((row) => `${row.projectId}:${row.taskId}`)).toEqual([
            'p-b:null',
            'p-a:t-a',
            'p-a:t-z',
        ]);

        projects.value = [project('p-a', 'Aardvark'), project('p-b', 'Zulu')];
        await nextTick();

        expect(rows.value.map((row) => `${row.projectId}:${row.taskId}`)).toEqual([
            'p-a:t-a',
            'p-a:t-z',
            'p-b:null',
        ]);
    });

    it('keeps user-added slots below seeded rows in insertion order', async () => {
        const timeEntries = ref([
            entry('2026-04-10T09:00:00Z', '2026-04-10T10:00:00Z', {
                id: 'seed-b',
                project_id: 'p-seed-b',
            }),
            entry('2026-04-10T10:00:00Z', '2026-04-10T11:00:00Z', {
                id: 'seed-a',
                project_id: 'p-seed-a',
            }),
        ]);
        const projects = ref([
            project('p-seed-a', 'Bravo Seed'),
            project('p-seed-b', 'Alpha Seed'),
            project('p-user-1', 'Alpha User'),
            project('p-user-2', 'Zulu User'),
        ]);

        const { rows, addSlot } = useTimesheetGrid(
            timeEntries,
            ref(WEEK_DAYS),
            projects,
            ref<Task[]>([]),
            ref<Dayjs | null>(null)
        );

        addSlot('p-user-2', null, false, []);
        addSlot('p-user-1', null, false, []);
        await nextTick();

        expect(rows.value.map((row) => row.projectId)).toEqual([
            'p-seed-b',
            'p-seed-a',
            'p-user-2',
            'p-user-1',
        ]);

        projects.value = [
            project('p-seed-a', 'Zulu Seed'),
            project('p-seed-b', 'Alpha Seed'),
            project('p-user-1', 'Aardvark User'),
            project('p-user-2', 'Bravo User'),
        ];
        await nextTick();

        expect(rows.value.map((row) => row.projectId)).toEqual([
            'p-seed-b',
            'p-seed-a',
            'p-user-2',
            'p-user-1',
        ]);
    });

    it('assigns entries to the first duplicate-identity slot and leaves later duplicates empty', async () => {
        const timeEntries = ref<TimeEntry[]>([]);

        const { rows, addSlot } = useTimesheetGrid(
            timeEntries,
            ref(WEEK_DAYS),
            ref([project('p-1', 'Project One')]),
            ref<Task[]>([]),
            ref<Dayjs | null>(null)
        );

        const firstKey = addSlot('p-1', null, false, []);
        const secondKey = addSlot('p-1', null, false, []);
        await nextTick();

        timeEntries.value = [
            entry('2026-04-10T09:00:00Z', '2026-04-10T10:00:00Z', { project_id: 'p-1' }),
        ];
        await nextTick();

        expect(rows.value).toHaveLength(2);
        expect(rows.value[0]?.key).toBe(firstKey);
        expect(rows.value[0]?.totalSeconds).toBe(3600);
        expect(rows.value[1]?.key).toBe(secondKey);
        expect(rows.value[1]?.totalSeconds).toBe(0);
        expect(rows.value[1]?.cells.size).toBe(0);
    });

    it('keeps no-project user slots aligned with refetched no-project entries', async () => {
        const timeEntries = ref<TimeEntry[]>([]);

        const { rows, addSlot } = useTimesheetGrid(
            timeEntries,
            ref(WEEK_DAYS),
            ref<Project[]>([]),
            ref<Task[]>([]),
            ref<Dayjs | null>(null)
        );

        const rowKey = addSlot(null, null, false, []);
        await nextTick();

        expect(rows.value).toHaveLength(1);
        expect(rows.value[0]).toMatchObject({ key: rowKey, projectId: null });

        timeEntries.value = [
            entry('2026-04-10T09:00:00Z', '2026-04-10T10:00:00Z', {
                id: 'no-project',
                project_id: null,
            }),
        ];
        await nextTick();

        expect(rows.value).toHaveLength(1);
        expect(rows.value[0]).toMatchObject({
            key: rowKey,
            projectId: null,
            totalSeconds: 3600,
        });
    });

    it('updates a slot identity in place and clearSlots removes all rows', async () => {
        const { rows, slots, addSlot, updateSlot, clearSlots } = useTimesheetGrid(
            ref<TimeEntry[]>([]),
            ref(WEEK_DAYS),
            ref([project('p-next', 'Next Project')]),
            ref([task('t-1', 'Task One', 'p-next')]),
            ref<Dayjs | null>(null)
        );

        const key = addSlot(null, null, false, []);
        await nextTick();

        updateSlot(key, {
            projectId: 'p-next',
            taskId: 't-1',
            billable: true,
            tags: ['b-tag', 'a-tag'],
        });
        await nextTick();

        expect(rows.value).toHaveLength(1);
        expect(rows.value[0]).toMatchObject({
            key,
            projectId: 'p-next',
            taskId: 't-1',
            billable: true,
            tags: ['a-tag', 'b-tag'],
        });

        clearSlots();
        await nextTick();

        expect(rows.value).toHaveLength(0);
        expect(slots.value).toHaveLength(0);
    });

    it('includes running entries in row and week totals using the live timer clock', () => {
        const currentTime = ref(dayjs.utc('2026-04-10T10:00:00Z'));
        const runningEntry = entry('2026-04-10T09:00:00Z', null, { id: 'running' });

        const { rows, dayTotals, grandTotal } = useTimesheetGrid(
            ref([runningEntry]),
            ref(WEEK_DAYS),
            ref<Project[]>([]),
            ref<Task[]>([]),
            currentTime
        );

        expect(rows.value).toHaveLength(1);
        expect(rows.value[0]?.cells.get(4)?.totalSeconds).toBe(3600);
        expect(rows.value[0]?.totalSeconds).toBe(3600);
        expect(dayTotals.value[4]).toBe(3600);
        expect(grandTotal.value).toBe(3600);

        currentTime.value = dayjs.utc('2026-04-10T11:30:00Z');

        expect(rows.value[0]?.cells.get(4)?.totalSeconds).toBe(9000);
        expect(rows.value[0]?.totalSeconds).toBe(9000);
        expect(dayTotals.value[4]).toBe(9000);
        expect(grandTotal.value).toBe(9000);
    });
});
