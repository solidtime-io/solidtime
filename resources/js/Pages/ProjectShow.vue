<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FolderIcon, PlusIcon } from '@heroicons/vue/20/solid';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { computed, ref } from 'vue';
import { useProjectsQuery } from '@/utils/useProjectsQuery';
import {
    ChevronRightIcon,
    CheckCircleIcon,
    UserGroupIcon,
    PencilSquareIcon,
} from '@heroicons/vue/20/solid';

import { Link } from '@inertiajs/vue3';
import TaskCreateModal from '@/Components/Common/Task/TaskCreateModal.vue';
import TaskTable from '@/Components/Common/Task/TaskTable.vue';
import CardTitle from '@/packages/ui/src/CardTitle.vue';
import Card from '@/Components/Common/Card.vue';
import ProjectMemberTable from '@/Components/Common/ProjectMember/ProjectMemberTable.vue';
import ProjectMemberCreateModal from '@/Components/Common/ProjectMember/ProjectMemberCreateModal.vue';
import { useProjectMembersQuery } from '@/utils/useProjectMembersQuery';
import { canCreateProjects, canCreateTasks, canViewProjectMembers } from '@/utils/permissions';
import TabBarItem from '@/Components/Common/TabBar/TabBarItem.vue';
import TabBar from '@/Components/Common/TabBar/TabBar.vue';
import { useTasksQuery } from '@/utils/useTasksQuery';
import ProjectEditModal from '@/Components/Common/Project/ProjectEditModal.vue';
import { Badge } from '@/packages/ui/src';
import { formatCents } from '../packages/ui/src/utils/money';
import { getOrganizationCurrencyString } from '../utils/money';
import { useOrganizationQuery } from '@/utils/useOrganizationQuery';
import { getCurrentOrganizationId } from '@/utils/useUser';

const { projects } = useProjectsQuery();

const { organization } = useOrganizationQuery(getCurrentOrganizationId()!);

const project = computed(() => {
    return projects.value.find((project) => project.id === route().params.project) ?? null;
});
const createTask = ref(false);
const createProjectMember = ref(false);
const projectId = route()?.params?.project as string;

// TanStack Query automatically fetches project members when component mounts
const { projectMembers } = canViewProjectMembers()
    ? useProjectMembersQuery(projectId)
    : { projectMembers: computed(() => []) };

const showEditProjectModal = ref(false);

const billableRateFormatted = computed(() => {
    if (project.value?.billable_rate) {
        return formatCents(
            project.value.billable_rate,
            getOrganizationCurrencyString(),
            organization.value?.currency_format,
            organization.value?.currency_symbol,
            organization.value?.number_format
        );
    }
    return null;
});

const activeTab = ref<'active' | 'done'>('active');

const { tasks } = useTasksQuery();

const shownTasks = computed(() => {
    return tasks.value.filter((task) => {
        if (activeTab.value === 'active') {
            return task.project_id === projectId && !task.is_done;
        }
        return task.project_id === projectId && task.is_done;
    });
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
                                class="flex items-center space-x-2 sm:space-x-2.5">
                                <FolderIcon class="w-5 text-icon-default"></FolderIcon>
                                <span class="text-sm sm:text-base font-medium">Projects</span>
                            </Link>
                        </div>
                    </li>
                    <li>
                        <div
                            class="flex items-center space-x-3 text-text-primary font-semibold text-base">
                            <ChevronRightIcon
                                class="h-5 w-5 flex-shrink-0 text-text-secondary"
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
                <div class="px-4">
                    <Badge v-if="project?.billable_rate">
                        {{ billableRateFormatted }}
                        / h
                    </Badge>
                    <Badge v-if="project?.is_billable && !project?.billable_rate">
                        Default Rate
                    </Badge>
                    <Badge v-if="!project?.is_billable"> Non-Billable </Badge>
                </div>
            </nav>
            <div>
                <SecondaryButton
                    v-if="canCreateProjects()"
                    :icon="PencilSquareIcon"
                    @click="showEditProjectModal = true">
                    Edit Project
                </SecondaryButton>
                <ProjectEditModal
                    v-if="project"
                    v-model:show="showEditProjectModal"
                    :original-project="project"></ProjectEditModal>
            </div>
        </MainContainer>
        <MainContainer>
            <div class="grid lg:grid-cols-2 gap-x-6 pt-6">
                <div>
                    <CardTitle title="Tasks" :icon="CheckCircleIcon">
                        <template #actions>
                            <div class="w-full items-center flex justify-between">
                                <div class="pl-6">
                                    <TabBar v-model="activeTab">
                                        <TabBarItem value="active">Active </TabBarItem>
                                        <TabBarItem value="done">Done </TabBarItem>
                                    </TabBar>
                                </div>
                                <SecondaryButton
                                    v-if="canCreateTasks()"
                                    :icon="PlusIcon"
                                    @click="createTask = true"
                                    >Create Task
                                </SecondaryButton>
                                <TaskCreateModal
                                    v-model:show="createTask"
                                    :project-id="projectId"></TaskCreateModal>
                            </div>
                        </template>
                    </CardTitle>
                    <Card>
                        <TaskTable :tasks="shownTasks" :project-id="projectId"></TaskTable>
                    </Card>
                </div>
                <div v-if="canViewProjectMembers()">
                    <CardTitle title="Project Members" :icon="UserGroupIcon">
                        <template #actions>
                            <SecondaryButton :icon="PlusIcon" @click="createProjectMember = true">
                                Add Member
                            </SecondaryButton>
                            <ProjectMemberCreateModal
                                v-model:show="createProjectMember"
                                :project-id="projectId"
                                :existing-members="projectMembers"></ProjectMemberCreateModal>
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
