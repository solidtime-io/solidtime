<script setup lang="ts">
import {
    TrashIcon,
    PencilSquareIcon,
    ArchiveBoxIcon,
} from '@heroicons/vue/20/solid';
import type { Project } from '@/packages/api/src';
import { canDeleteProjects, canUpdateProjects } from '@/utils/permissions';
import MoreOptionsDropdown from '@/packages/ui/src/MoreOptionsDropdown.vue';
const emit = defineEmits<{
    delete: [];
    edit: [];
    archive: [];
}>();
const props = defineProps<{
    project: Project;
}>();
</script>

<template>
    <MoreOptionsDropdown :label="'Actions for Project ' + props.project.name">
        <div class="min-w-[150px]">
            <button
                v-if="canUpdateProjects()"
                :aria-label="'Edit Project ' + props.project.name"
                data-testid="project_edit"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-text-primary hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out"
                @click.prevent="emit('edit')">
                <PencilSquareIcon
                    class="w-5 text-icon-active"></PencilSquareIcon>
                <span>Edit</span>
            </button>
            <button
                v-if="canUpdateProjects()"
                :aria-label="'Archive Project ' + props.project.name"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-text-primary hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out"
                @click.prevent="emit('archive')">
                <ArchiveBoxIcon class="w-5 text-icon-active"></ArchiveBoxIcon>
                <span>{{ project.is_archived ? 'Unarchive' : 'Archive' }}</span>
            </button>
            <button
                v-if="canDeleteProjects()"
                :aria-label="'Delete Project ' + props.project.name"
                data-testid="project_delete"
                class="border-b border-card-background-separator flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-text-primary hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out"
                @click.prevent="emit('delete')">
                <TrashIcon class="w-5 text-icon-active"></TrashIcon>
                <span>Delete</span>
            </button>
        </div>
    </MoreOptionsDropdown>
</template>

<style scoped></style>
