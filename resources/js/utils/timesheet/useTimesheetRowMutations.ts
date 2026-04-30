import type { Ref } from 'vue';
import type { Project, UpdateMultipleTimeEntriesChangeset } from '@/packages/api/src';
import {
    makeRowKey,
    type TimesheetRow,
    type TimesheetRowIdentity,
    type TimesheetRowKey,
} from '@/utils/useTimesheetGrid';
import type { useTimeEntriesMutations } from '@/utils/useTimeEntriesMutations';
import { useNotificationsStore } from '@/utils/notification';

function identityPartialToApiChanges(
    partial: Partial<TimesheetRowIdentity>
): UpdateMultipleTimeEntriesChangeset {
    const changes: UpdateMultipleTimeEntriesChangeset = {};
    if ('projectId' in partial) changes.project_id = partial.projectId;
    if ('taskId' in partial) changes.task_id = partial.taskId;
    if ('billable' in partial) changes.billable = partial.billable;
    if ('tags' in partial) changes.tags = partial.tags;
    return changes;
}

type Mutations = ReturnType<typeof useTimeEntriesMutations>;

/**
 * Row-level mutations that don't involve confirmation.
 *
 * Rows are keyed by slot id (not identity), so any partial change to
 * a row's identity is handled the same way: push the change to the
 * server for any entries in the row, then migrate the slot's identity
 * in place so the row stays at its existing position.
 */
export function useTimesheetRowMutations(
    mutations: Pick<Mutations, 'updateTimeEntries'>,
    projects: Ref<Project[]>,
    rows: Ref<TimesheetRow[]>,
    addSlot: (
        projectId: string | null,
        taskId: string | null,
        billable: boolean,
        tags: string[]
    ) => TimesheetRowKey,
    updateSlot: (key: TimesheetRowKey, identity: TimesheetRowIdentity) => void,
    removeSlot: (key: TimesheetRowKey) => void
) {
    const notifications = useNotificationsStore();

    function collectEntryIds(row: TimesheetRow): string[] {
        const ids: string[] = [];
        for (const cell of row.cells.values()) {
            for (const entry of cell.entries) ids.push(entry.id);
        }
        return ids;
    }

    function hasDuplicateIdentityRow(
        rowKey: TimesheetRowKey,
        identity: TimesheetRowIdentity
    ): boolean {
        const target = makeRowKey(
            identity.projectId,
            identity.taskId,
            identity.billable,
            identity.tags
        );

        return rows.value.some(
            (candidate) =>
                candidate.key !== rowKey &&
                makeRowKey(
                    candidate.projectId,
                    candidate.taskId,
                    candidate.billable,
                    candidate.tags
                ) === target
        );
    }

    async function handleRowIdentityChange(
        row: TimesheetRow,
        partial: Partial<TimesheetRowIdentity>
    ): Promise<void> {
        const entryIds = collectEntryIds(row);
        const currentIdentity = makeRowKey(row.projectId, row.taskId, row.billable, row.tags);
        let merged: TimesheetRowIdentity = {
            projectId: row.projectId,
            taskId: row.taskId,
            billable: row.billable,
            tags: row.tags,
            ...partial,
        };

        // Auto-default billable on the first project pick for an empty
        // row (project provides the default; user can override after).
        if (
            entryIds.length === 0 &&
            partial.projectId !== undefined &&
            partial.projectId !== row.projectId &&
            partial.projectId &&
            partial.billable === undefined
        ) {
            const projectBillable = projects.value.find(
                (p) => p.id === partial.projectId
            )?.is_billable;
            if (projectBillable !== undefined) {
                merged = { ...merged, billable: projectBillable };
            }
        }

        const mergedIdentity = makeRowKey(
            merged.projectId,
            merged.taskId,
            merged.billable,
            merged.tags
        );
        const shouldMergeIntoExistingRow =
            entryIds.length > 0 &&
            currentIdentity !== mergedIdentity &&
            hasDuplicateIdentityRow(row.key, merged);

        if (entryIds.length > 0) {
            await mutations.updateTimeEntries({
                ids: entryIds,
                changes: identityPartialToApiChanges(partial),
            });
        }

        if (shouldMergeIntoExistingRow) {
            removeSlot(row.key);
            notifications.addNotification(
                'success',
                'Merged into matching row',
                'Another row with the same project, task, billable status and tags already exists.'
            );
            return;
        }

        updateSlot(row.key, merged);
    }

    function handleAddRow(projectId: string | null = null, taskId: string | null = null): void {
        const project = projectId ? projects.value.find((p) => p.id === projectId) : null;
        addSlot(projectId, taskId, project?.is_billable ?? false, []);
    }

    return {
        handleRowIdentityChange,
        handleAddRow,
    };
}
