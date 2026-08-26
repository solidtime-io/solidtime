<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { PlusCircleIcon } from '@heroicons/vue/24/solid';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { ref } from 'vue';
import TaskTableRow from '@/Components/Common/Task/TaskTableRow.vue';
import TaskTableHeading from '@/Components/Common/Task/TaskTableHeading.vue';
import TaskCreateModal from '@/Components/Common/Task/TaskCreateModal.vue';
import { canCreateTasks } from '@/utils/permissions';
import type { Task } from '@/packages/api/src';
import {
    useSortableTable,
    type SortableColumnDef,
    type SortDirection,
} from '@/utils/useSortableTable';

export type SortColumn = 'name' | 'spent_time' | 'progress';
export type { SortDirection } from '@/utils/useSortableTable';

const props = defineProps<{
    projectId: string;
    tasks: Task[];
    sortColumn: SortColumn;
    sortDirection: SortDirection;
}>();

const emit = defineEmits<{
    sort: [column: SortColumn, direction: SortDirection];
}>();

const columns: SortableColumnDef<Task, SortColumn>[] = [
    {
        id: 'name',
        accessorFn: (row: Task) => row.name.toLowerCase(),
    },
    {
        id: 'spent_time',
        sortDescFirst: true,
        accessorFn: (row: Task) => row.spent_time,
    },
    {
        id: 'progress',
        sortDescFirst: true,
        accessorFn: (row: Task) => {
            if (!row.estimated_time) return undefined;
            return (row.spent_time / row.estimated_time) * 100;
        },
    },
];

const {
    sortedRows: sortedTasks,
    descFirstColumns,
    nextDirection,
} = useSortableTable({
    data: () => props.tasks,
    columns: () => columns,
    sortColumn: () => props.sortColumn,
    sortDirection: () => props.sortDirection,
    tieBreakColumn: 'name',
});

function handleSort(column: SortColumn) {
    emit('sort', column, nextDirection(column));
}

const createTask = ref(false);
</script>

<template>
    <TaskCreateModal v-model:show="createTask" :project-id="props.projectId"></TaskCreateModal>
    <div class="flow-root">
        <div class="inline-block min-w-full align-middle">
            <div
                data-testid="task_table"
                role="table"
                class="grid min-w-full"
                style="
                    grid-template-columns:
                        1fr minmax(80px, auto) minmax(120px, auto) minmax(50px, auto)
                        80px;
                ">
                <TaskTableHeading
                    :sort-column="sortColumn"
                    :sort-direction="sortDirection"
                    :desc-first-columns="descFirstColumns"
                    @sort="handleSort"></TaskTableHeading>
                <div v-if="sortedTasks.length === 0" class="col-span-5 py-24 text-center">
                    <PlusCircleIcon class="w-8 text-icon-default inline pb-2"></PlusCircleIcon>
                    <h3 class="text-text-primary font-semibold">No tasks found</h3>
                    <p v-if="canCreateTasks()" class="pb-5">Create your first task now!</p>
                    <SecondaryButton
                        v-if="canCreateTasks()"
                        :icon="PlusIcon"
                        @click="createTask = true"
                        >Create your First Task
                    </SecondaryButton>
                </div>
                <template v-for="task in sortedTasks" :key="task.id">
                    <TaskTableRow :task="task"></TaskTableRow>
                </template>
            </div>
        </div>
    </div>
</template>
