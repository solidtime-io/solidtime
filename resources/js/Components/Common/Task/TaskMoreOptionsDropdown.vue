<script setup lang="ts">
import {
    TrashIcon,
    PencilSquareIcon,
    CheckCircleIcon,
} from '@heroicons/vue/20/solid';
import type { Task } from '@/utils/api';
import { canDeleteTasks, canUpdateTasks } from '@/utils/permissions';
import MoreOptionsDropdown from '@/Components/MoreOptionsDropdown.vue';
const emit = defineEmits<{
    delete: [];
    edit: [];
    done: [];
}>();
const props = defineProps<{
    task: Task;
}>();
</script>

<template>
    <MoreOptionsDropdown :label="'Actions for Task ' + props.task.name">
        <div class="min-w-[150px]">
            <button
                @click="emit('edit')"
                v-if="canUpdateTasks()"
                :aria-label="'Edit Task ' + props.task.name"
                data-testid="task_edit"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
                <PencilSquareIcon
                    class="w-5 text-icon-active"></PencilSquareIcon>
                <span>Edit</span>
            </button>
            <button
                @click="emit('done')"
                v-if="canUpdateTasks()"
                :aria-label="'Mark Task ' + props.task.name + ' as done'"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
                <CheckCircleIcon class="w-5 text-icon-active"></CheckCircleIcon>
                <span v-if="props.task.is_done">Mark as active</span>
                <span v-else>Mark as done</span>
            </button>
            <button
                @click="emit('delete')"
                :aria-label="'Delete Task ' + props.task.name"
                v-if="canDeleteTasks()"
                data-testid="task_delete"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
                <TrashIcon class="w-5 text-icon-active"></TrashIcon>
                <span>Delete</span>
            </button>
        </div>
    </MoreOptionsDropdown>
</template>

<style scoped></style>
