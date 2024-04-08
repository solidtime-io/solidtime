<script setup lang="ts">
import MainContainer from '@/Pages/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FolderIcon, PlusIcon } from '@heroicons/vue/16/solid';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { computed, ref } from 'vue';
import { useProjectsStore } from '@/utils/useProjects';
import { storeToRefs } from 'pinia';
import { ChevronRightIcon } from '@heroicons/vue/20/solid';
import { Link } from '@inertiajs/vue3';
import TaskCreateModal from '@/Components/Common/Task/TaskCreateModal.vue';
import TaskTable from '@/Components/Common/Task/TaskTable.vue';
const { projects } = storeToRefs(useProjectsStore());

const project = computed(() => {
    return (
        projects.value.find(
            (project) => project.id === route().params.project
        ) ?? null
    );
});
const createTask = ref(false);

const projectId: string = route().params.project;
</script>

<template>
    <AppLayout title="Projects" data-testid="projects_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <nav class="flex" aria-label="Breadcrumb">
                <ol role="list" class="flex items-center space-x-2">
                    <li>
                        <div class="flex items-center space-x-6">
                            <Link
                                :href="route('projects')"
                                class="flex items-center space-x-2.5">
                                <FolderIcon
                                    class="w-6 text-icon-default"></FolderIcon>
                                <span> Projects </span>
                            </Link>
                        </div>
                    </li>
                    <li>
                        <div
                            class="flex items-center space-x-3 text-white font-bold text-base">
                            <ChevronRightIcon
                                class="h-5 w-5 flex-shrink-0 text-muted"
                                aria-hidden="true" />
                            <div class="flex space-x-3 items-center">
                                <div
                                    :style="{
                                        backgroundColor: project?.color,
                                        boxShadow: `var(--tw-ring-inset) 0 0 0 calc(4px + var(--tw-ring-offset-width)) ${project?.color}30`,
                                    }"
                                    class="w-3 h-3 rounded-full"></div>
                                <span>{{ project?.name }}</span>
                            </div>
                        </div>
                    </li>
                </ol>
            </nav>
            <SecondaryButton :icon="PlusIcon" @click="createTask = true"
                >Create Task
            </SecondaryButton>
            <TaskCreateModal
                :project-id="projectId"
                v-model:show="createTask"></TaskCreateModal>
        </MainContainer>
        <TaskTable :project-id="projectId"></TaskTable>
    </AppLayout>
</template>
