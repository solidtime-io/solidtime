<script setup lang="ts">
import type { Task } from '@/utils/api';
import { CheckCircleIcon } from '@heroicons/vue/20/solid';
import { useTasksStore } from '@/utils/useTasks';
import TaskMoreOptionsDropdown from '@/Components/Common/Task/TaskMoreOptionsDropdown.vue';
import TableRow from '@/Components/TableRow.vue';
import { canDeleteTasks } from '@/utils/permissions';
import TaskEditModal from '@/Components/Common/Task/TaskEditModal.vue';
import { ref } from 'vue';

const props = defineProps<{
    task: Task;
}>();

function deleteTask() {
    useTasksStore().deleteTask(props.task.id);
}

function markTaskAsDone() {
    useTasksStore().updateTask(props.task.id, {
        ...props.task,
        is_done: !props.task.is_done,
    });
}

const showTaskEditModal = ref(false);
</script>

<template>
    <TableRow>
        <div
            class="whitespace-nowrap flex items-center space-x-5 3xl:pl-12 py-4 pr-3 text-sm font-medium text-white pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
            <span>
                {{ task.name }}
            </span>
        </div>
        <div
            class="whitespace-nowrap px-3 py-4 text-sm text-muted flex space-x-1 items-center font-medium">
            <template v-if="task.is_done">
                <CheckCircleIcon class="w-5"></CheckCircleIcon>
                <span>Done</span>
            </template>
            <template v-else>
                <span>Active</span>
            </template>
        </div>
        <div
            class="relative whitespace-nowrap flex items-center pl-3 text-right text-sm font-medium sm:pr-0 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
            <TaskMoreOptionsDropdown
                v-if="canDeleteTasks()"
                :task="task"
                @done="markTaskAsDone"
                @edit="showTaskEditModal = true"
                @delete="deleteTask"></TaskMoreOptionsDropdown>
        </div>
        <TaskEditModal
            :task="task"
            v-model:show="showTaskEditModal"></TaskEditModal>
    </TableRow>
</template>

<style scoped></style>
