<script setup lang="ts">
import MainContainer from '@/Pages/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FolderIcon, PlusIcon } from '@heroicons/vue/16/solid';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { computed, onMounted, ref } from 'vue';
import { useProjectsStore } from '@/utils/useProjects';
import { storeToRefs } from 'pinia';
import {
    ChevronRightIcon,
    CheckCircleIcon,
    UserGroupIcon,
} from '@heroicons/vue/20/solid';
import { Link } from '@inertiajs/vue3';
import TaskCreateModal from '@/Components/Common/Task/TaskCreateModal.vue';
import TaskTable from '@/Components/Common/Task/TaskTable.vue';
import CardTitle from '@/Components/Common/CardTitle.vue';
import Card from '@/Components/Common/Card.vue';
import ProjectMemberTable from '@/Components/Common/ProjectMember/ProjectMemberTable.vue';
import ProjectMemberCreateModal from '@/Components/Common/ProjectMember/ProjectMemberCreateModal.vue';
import { useProjectMembersStore } from '@/utils/useProjectMembers';
import { canCreateTasks, canViewProjectMembers } from '@/utils/permissions';

const { projects } = storeToRefs(useProjectsStore());

const project = computed(() => {
    return (
        projects.value.find(
            (project) => project.id === route().params.project
        ) ?? null
    );
});
const createTask = ref(false);
const createProjectMember = ref(false);
const projectId = route()?.params?.project as string;

const { projectMembers } = storeToRefs(useProjectMembersStore());

onMounted(() => {
    if (canViewProjectMembers()) {
        useProjectMembersStore().fetchProjectMembers(projectId);
    }
});
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
                                <span class="font-medium">Projects</span>
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
        </MainContainer>
        <MainContainer>
            <div class="grid lg:grid-cols-2 gap-x-6 pt-6">
                <div>
                    <CardTitle title="Tasks" :icon="CheckCircleIcon">
                        <template #actions>
                            <SecondaryButton
                                v-if="canCreateTasks()"
                                :icon="PlusIcon"
                                @click="createTask = true"
                                >Create Task
                            </SecondaryButton>
                            <TaskCreateModal
                                :project-id="projectId"
                                v-model:show="createTask"></TaskCreateModal>
                        </template>
                    </CardTitle>
                    <Card>
                        <TaskTable :project-id="projectId"></TaskTable>
                    </Card>
                </div>
                <div v-if="canViewProjectMembers()">
                    <CardTitle title="Project Members" :icon="UserGroupIcon">
                        <template #actions>
                            <SecondaryButton
                                :icon="PlusIcon"
                                @click="createProjectMember = true">
                                Add Member
                            </SecondaryButton>
                            <ProjectMemberCreateModal
                                :project-id="projectId"
                                :existing-members="projectMembers"
                                v-model:show="
                                    createProjectMember
                                "></ProjectMemberCreateModal>
                        </template>
                    </CardTitle>
                    <Card>
                        <ProjectMemberTable
                            :project-members="projectMembers"
                            :project-id="projectId"></ProjectMemberTable>
                    </Card>
                </div>
            </div>
        </MainContainer>
    </AppLayout>
</template>
