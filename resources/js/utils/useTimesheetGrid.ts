import type { TimeEntry, Project, Task } from '@/packages/api/src';
import { getDayJsInstance, getLocalizedDateFromTimestamp } from '@/packages/ui/src/utils/time';
import type { Dayjs } from 'dayjs';
import { computed, ref, watch, type Ref } from 'vue';

export type TimesheetRowKey = string;

export interface TimesheetCell {
    dayIndex: number;
    date: string;
    entries: TimeEntry[];
    totalSeconds: number;
}

export interface TimesheetRow {
    key: TimesheetRowKey;
    projectId: string | null;
    taskId: string | null;
    billable: boolean;
    tags: string[];
    cells: Map<number, TimesheetCell>;
    totalSeconds: number;
}

export interface TimesheetRowIdentity {
    projectId: string | null;
    taskId: string | null;
    billable: boolean;
    tags: string[];
}

interface Slot extends TimesheetRowIdentity {
    id: string;
    // 'seeded' slots are derived from the entries query and re-sort
    // alphabetically whenever project/task lists change. 'user' slots
    // were created via Add Row / project-change interactions and keep
    // their insertion order (always below the seeded block).
    origin: 'seeded' | 'user';
}

function sortTags(tags: string[] | null | undefined): string[] {
    return [...(tags ?? [])].sort();
}

export function makeRowKey(
    projectId: string | null,
    taskId: string | null,
    billable: boolean,
    tags: string[]
): TimesheetRowKey {
    return JSON.stringify([projectId, taskId, billable, sortTags(tags)]);
}

function slotIdentityKey(slot: Slot): TimesheetRowKey {
    return makeRowKey(slot.projectId, slot.taskId, slot.billable, slot.tags);
}

let slotCounter = 0;

function newSlotId(): string {
    return `s${++slotCounter}`;
}

/**
 * Slot-first row model.
 *
 * The timesheet renders one row per slot, in insertion order. Slots
 * carry a stable id — the row's Vue key never changes across mutations,
 * so rows don't jump positions as entries load or get edited.
 *
 * Entries hydrate slots: `rows` is computed by grouping entries by
 * identity (projectId, taskId, billable, tags) and attaching the
 * matching group to the first slot with that identity. Duplicate
 * slots with the same identity render empty (the first one claims
 * the entries) — callers are expected to collapse duplicates after a
 * cell-create rather than letting them linger.
 *
 * Seeding: a watcher scans `timeEntries` and appends a slot for every
 * identity that doesn't already have one. Initial loads come in as a
 * batch and are sorted by project name so the first render is stable;
 * slots added later (via `addSlot` or post-mutation refetches) append
 * at the end.
 *
 * Mutations:
 *   - `addSlot`           push a blank or pre-populated slot at the end
 *   - `removeSlot`        drop a slot by id (the row's `key`)
 *   - `updateSlot`        migrate a slot's identity in place — used by
 *                         project/billable/tags changes so the row
 *                         stays put while the server roundtrips
 *   - `clearSlots`        wipe everything (used on week navigation)
 */
export function useTimesheetGrid(
    timeEntries: Ref<TimeEntry[]>,
    weekDays: Ref<string[]>,
    projects: Ref<Project[]>,
    tasks: Ref<Task[]>,
    currentTime: Ref<Dayjs | null>
) {
    const dayjs = getDayJsInstance();
    const slots = ref<Slot[]>([]);

    // Seed / re-sort the seeded portion of slots whenever entries,
    // projects or tasks change. Seeded slots sort alphabetically by
    // project name → task name → billable → tags so reloads are
    // deterministic. User-added slots keep their insertion order and
    // stay after the seeded block.
    watch(
        [() => timeEntries.value, () => projects.value, () => tasks.value],
        ([entries, projectList, taskList]) => {
            const present = new Set(slots.value.map(slotIdentityKey));
            for (const entry of entries) {
                const key = makeRowKey(
                    entry.project_id,
                    entry.task_id,
                    entry.billable,
                    sortTags(entry.tags)
                );
                if (present.has(key)) continue;
                present.add(key);
                slots.value.push({
                    id: newSlotId(),
                    origin: 'seeded',
                    projectId: entry.project_id,
                    taskId: entry.task_id,
                    billable: entry.billable,
                    tags: sortTags(entry.tags),
                });
            }

            const projectNameMap = new Map<string, string>();
            for (const p of projectList) projectNameMap.set(p.id, p.name);
            const taskNameMap = new Map<string, string>();
            for (const t of taskList) taskNameMap.set(t.id, t.name);

            const sortKey = (s: Slot): string => {
                const projectName = s.projectId ? (projectNameMap.get(s.projectId) ?? '') : '';
                const taskName = s.taskId ? (taskNameMap.get(s.taskId) ?? '') : '';
                return `${projectName}\x00${taskName}\x00${s.billable ? '1' : '0'}\x00${s.tags.join(',')}`;
            };

            const seeded = slots.value.filter((s) => s.origin === 'seeded');
            const userAdded = slots.value.filter((s) => s.origin === 'user');
            seeded.sort((a, b) => sortKey(a).localeCompare(sortKey(b)));
            slots.value = [...seeded, ...userAdded];
        },
        { immediate: true }
    );

    const rows = computed<TimesheetRow[]>(() => {
        const dayIndexMap = new Map<string, number>();
        weekDays.value.forEach((date, index) => dayIndexMap.set(date, index));

        // Group entries by identity. The first slot (in render order) with
        // a given identity claims that group; later duplicate-identity
        // slots render empty.
        const entriesByIdentity = new Map<TimesheetRowKey, TimeEntry[]>();
        for (const entry of timeEntries.value) {
            const identityKey = makeRowKey(
                entry.project_id,
                entry.task_id,
                entry.billable,
                sortTags(entry.tags)
            );
            if (!entriesByIdentity.has(identityKey)) entriesByIdentity.set(identityKey, []);
            entriesByIdentity.get(identityKey)!.push(entry);
        }

        const claimed = new Set<TimesheetRowKey>();

        function buildCellsFromEntries(entries: TimeEntry[]) {
            const cells = new Map<number, TimesheetCell>();
            let totalSeconds = 0;

            function getEntryDurationSeconds(entry: TimeEntry): number {
                if (entry.end !== null) {
                    return entry.duration ?? 0;
                }

                const liveNow = currentTime.value ?? dayjs.utc();
                return Math.max(0, liveNow.diff(dayjs.utc(entry.start), 'second'));
            }

            for (const entry of entries) {
                const entryDate = getLocalizedDateFromTimestamp(entry.start);
                const dayIndex = dayIndexMap.get(entryDate);
                if (dayIndex === undefined) continue;
                const existing = cells.get(dayIndex);
                const duration = getEntryDurationSeconds(entry);
                if (existing) {
                    existing.entries.push(entry);
                    existing.totalSeconds += duration;
                } else {
                    cells.set(dayIndex, {
                        dayIndex,
                        date: weekDays.value[dayIndex]!,
                        entries: [entry],
                        totalSeconds: duration,
                    });
                }
                totalSeconds += duration;
            }
            return { cells, totalSeconds };
        }

        return slots.value.map((slot) => {
            const identityKey = slotIdentityKey(slot);
            let collected: TimeEntry[] = [];

            if (!claimed.has(identityKey)) {
                const byIdentity = entriesByIdentity.get(identityKey);
                if (byIdentity) {
                    claimed.add(identityKey);
                    collected = byIdentity;
                }
            }

            const { cells, totalSeconds } = buildCellsFromEntries(collected);

            return {
                key: slot.id,
                projectId: slot.projectId,
                taskId: slot.taskId,
                billable: slot.billable,
                tags: slot.tags,
                cells,
                totalSeconds,
            };
        });
    });

    const dayTotals = computed<number[]>(() =>
        weekDays.value.map((_, dayIndex) =>
            rows.value.reduce((sum, row) => sum + (row.cells.get(dayIndex)?.totalSeconds ?? 0), 0)
        )
    );

    const grandTotal = computed(() => dayTotals.value.reduce((a, b) => a + b, 0));

    function addSlot(
        projectId: string | null,
        taskId: string | null,
        billable: boolean,
        tags: string[]
    ): TimesheetRowKey {
        const id = newSlotId();
        slots.value.push({
            id,
            origin: 'user',
            projectId,
            taskId,
            billable,
            tags: sortTags(tags),
        });
        return id;
    }

    function removeSlot(key: TimesheetRowKey) {
        slots.value = slots.value.filter((s) => s.id !== key);
    }

    function updateSlot(key: TimesheetRowKey, identity: TimesheetRowIdentity) {
        const slot = slots.value.find((s) => s.id === key);
        if (!slot) return;
        slot.projectId = identity.projectId;
        slot.taskId = identity.taskId;
        slot.billable = identity.billable;
        slot.tags = sortTags(identity.tags);
    }

    function clearSlots() {
        slots.value = [];
    }

    return {
        rows,
        dayTotals,
        grandTotal,
        slots,
        addSlot,
        removeSlot,
        updateSlot,
        clearSlots,
    };
}
