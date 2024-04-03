<script setup lang="ts">
import ProjectMoreOptionsDropdown from '@/Components/Common/Project/ProjectMoreOptionsDropdown.vue';
import type { Project } from '@/utils/api';
import { computed } from 'vue';
import { CheckCircleIcon } from '@heroicons/vue/20/solid';
import { useClientsStore } from '@/utils/useClients';
import { storeToRefs } from 'pinia';
import { useTasksStore } from '@/utils/useTasks';
import { useProjectsStore } from '@/utils/useProjects';

const { clients } = storeToRefs(useClientsStore());
const { tasks } = storeToRefs(useTasksStore());

const props = defineProps<{
    project: Project;
}>();

const client = computed(() => {
    return clients.value.find(
        (client) => client.id === props.project.client_id
    );
});

const projectTasksCount = computed(() => {
    return tasks.value.filter((task) => task.project_id === props.project.id)
        .length;
});

function deleteProject() {
    useProjectsStore().deleteProject(props.project.id);
}
</script>

<template>
    <div
        class="whitespace-nowrap flex items-center space-x-5 3xl:pl-12 py-4 pr-3 text-sm font-medium text-white pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
        <div
            :style="{
                backgroundColor: project.color,
                boxShadow: `var(--tw-ring-inset) 0 0 0 calc(4px + var(--tw-ring-offset-width)) ${project.color}30`,
            }"
            class="w-3 h-3 rounded-full"></div>
        <span>
            {{ project.name }}
        </span>
        <span class="text-muted"> {{ projectTasksCount }} Tasks </span>
    </div>
    <div class="whitespace-nowrap px-3 py-4 text-sm text-muted">
        <div v-if="project.client_id">
            {{ client?.name }}
        </div>
        <div v-else>mem No client</div>
    </div>
    <div class="whitespace-nowrap px-3 py-4 text-sm text-muted">
        <div class="isolate flex -space-x-1 opacity-50">
            <img
                class="relative z-30 inline-block h-6 w-6 rounded-full ring-4 ring-card-background"
                src="https://images.unsplash.com/photo-1491528323818-fdd1faba62cc?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                alt="" />
            <img
                class="relative z-20 inline-block h-6 w-6 rounded-full ring-4 ring-card-background"
                src="https://images.unsplash.com/photo-1550525811-e5869dd03032?ixlib=rb-1.2.1&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                alt="" />
            <img
                class="relative z-10 inline-block h-6 w-6 rounded-full ring-4 ring-card-background"
                src="https://images.unsplash.com/photo-1500648767791-00dcc994a43e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2.25&w=256&h=256&q=80"
                alt="" />
            <img
                class="relative z-0 inline-block h-6 w-6 rounded-full ring-4 ring-card-background"
                src="https://images.unsplash.com/photo-1472099645785-5658abf4ff4e?ixlib=rb-1.2.1&ixid=eyJhcHBfaWQiOjEyMDd9&auto=format&fit=facearea&facepad=2&w=256&h=256&q=80"
                alt="" />
        </div>
    </div>
    <div
        class="whitespace-nowrap px-3 py-4 text-sm text-muted flex space-x-1 items-center font-medium">
        <CheckCircleIcon class="w-5"></CheckCircleIcon>
        <span>Active</span>
    </div>
    <div
        class="relative whitespace-nowrap flex items-center pl-3 text-right text-sm font-medium sm:pr-0 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
        <ProjectMoreOptionsDropdown
            :project="project"
            @delete="deleteProject"></ProjectMoreOptionsDropdown>
    </div>
</template>

<style scoped></style>
