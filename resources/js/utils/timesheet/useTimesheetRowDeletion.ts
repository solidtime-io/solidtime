import { computed, ref, type Ref } from 'vue';
import type { Project, TimeEntry } from '@/packages/api/src';
import type { TimesheetRow, TimesheetRowKey } from '@/utils/useTimesheetGrid';
import type { useTimeEntriesMutations } from '@/utils/useTimeEntriesMutations';

type Mutations = ReturnType<typeof useTimeEntriesMutations>;

/**
 * Holds the state and handlers for the "remove row" confirmation flow.
 *
 * Empty rows (no entries) are removed immediately without confirmation;
 * rows with entries open a confirmation dialog, and on confirm we bulk
 * delete every entry in the row before dropping the row from the grid.
 */
export function useTimesheetRowDeletion(
    projects: Ref<Project[]>,
    mutations: Pick<Mutations, 'deleteTimeEntries'>,
    removeSlot: (key: TimesheetRowKey) => void
) {
    const showDeleteDialog = ref(false);
    const rowToDelete = ref<TimesheetRow | null>(null);

    const deleteRowEntryCount = computed(() => {
        if (!rowToDelete.value) return 0;
        let count = 0;
        for (const cell of rowToDelete.value.cells.values()) {
            count += cell.entries.length;
        }
        return count;
    });

    const deleteRowProjectName = computed(() => {
        if (!rowToDelete.value?.projectId) return 'No Project';
        return projects.value.find((p) => p.id === rowToDelete.value?.projectId)?.name ?? 'Unknown';
    });

    function requestRemoveRow(row: TimesheetRow): void {
        if (row.totalSeconds === 0) {
            removeSlot(row.key);
            return;
        }
        rowToDelete.value = row;
        showDeleteDialog.value = true;
    }

    async function confirmDeleteRow(): Promise<void> {
        if (!rowToDelete.value) return;

        const allEntries: TimeEntry[] = [];
        for (const cell of rowToDelete.value.cells.values()) {
            allEntries.push(...cell.entries);
        }

        if (allEntries.length > 0) {
            await mutations.deleteTimeEntries(allEntries);
        }
        removeSlot(rowToDelete.value.key);
        showDeleteDialog.value = false;
        rowToDelete.value = null;
    }

    return {
        showDeleteDialog,
        rowToDelete,
        deleteRowEntryCount,
        deleteRowProjectName,
        requestRemoveRow,
        confirmDeleteRow,
    };
}
