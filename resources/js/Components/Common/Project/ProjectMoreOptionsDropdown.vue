<script setup lang="ts">
import Dropdown from '@/Components/Dropdown.vue';
import { TrashIcon, PencilSquareIcon } from '@heroicons/vue/20/solid';
import type { Project } from '@/utils/api';
const emit = defineEmits<{
    delete: [];
    edit: [];
}>();
const props = defineProps<{
    project: Project;
}>();
</script>

<template>
    <Dropdown>
        <template #trigger>
            <svg
                data-testid="project_actions"
                :aria-label="'Actions for Project ' + props.project.name"
                class="h-10 w-10 p-2 rounded-full hover:bg-card-background opacity-20 group-hover:opacity-100 transition"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M12 5.92A.96.96 0 1 0 12 4a.96.96 0 0 0 0 1.92m0 7.04a.96.96 0 1 0 0-1.92a.96.96 0 0 0 0 1.92M12 20a.96.96 0 1 0 0-1.92a.96.96 0 0 0 0 1.92" />
            </svg>
        </template>
        <template #content>
            <button
                @click.prevent="emit('delete')"
                :aria-label="'Delete Project ' + props.project.name"
                data-testid="project_delete"
                class="border-b border-card-background-separator flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
                <TrashIcon class="w-5 text-icon-active"></TrashIcon>
                <span>Delete</span>
            </button>
            <button
                @click.prevent="emit('edit')"
                :aria-label="'Edit Project ' + props.project.name"
                data-testid="project_edit"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
                <PencilSquareIcon
                    class="w-5 text-icon-active"></PencilSquareIcon>
                <span>Edit</span>
            </button>
        </template>
    </Dropdown>
</template>

<style scoped></style>
