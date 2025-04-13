<script setup lang="ts">
import ProjectMoreOptionsDropdown from '@/Components/Common/Project/ProjectMoreOptionsDropdown.vue';
import type { Project } from '@/packages/api/src';
import { computed, ref } from 'vue';
import { CheckCircleIcon } from '@heroicons/vue/20/solid';
import { useClientsStore } from '@/utils/useClients';
import { storeToRefs } from 'pinia';
import { useTasksStore } from '@/utils/useTasks';
import { useProjectsStore } from '@/utils/useProjects';
import TableRow from '@/Components/TableRow.vue';
import ProjectEditModal from '@/Components/Common/Project/ProjectEditModal.vue';
import { formatCents } from '@/packages/ui/src/utils/money';
import { getOrganizationCurrencyString } from '@/utils/money';
import EstimatedTimeProgress from '@/packages/ui/src/EstimatedTimeProgress.vue';
import UpgradeBadge from '@/Components/Common/UpgradeBadge.vue';
import { formatHumanReadableDuration } from '../../../packages/ui/src/utils/time';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';

const { clients } = storeToRefs(useClientsStore());
const { tasks } = storeToRefs(useTasksStore());

const props = defineProps<{
    project: Project;
    showBillableRate: boolean;
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

function archiveProject() {
    useProjectsStore().updateProject(props.project.id, {
        ...props.project,
        is_archived: !props.project.is_archived,
    });
}

const billableRateInfo = computed(() => {
    if (props.project.is_billable) {
        if (props.project.billable_rate) {
            return formatCents(
                props.project.billable_rate,
                getOrganizationCurrencyString()
            );
        } else {
            return 'Default Rate';
        }
    }
    return '--';
});

const showEditProjectModal = ref(false);
</script>

<template>
    <ProjectEditModal
        v-model:show="showEditProjectModal"
        :original-project="project"></ProjectEditModal>
    <TableRow :href="route('projects.show', { project: project.id })">
        <div
            class="whitespace-nowrap min-w-0 flex items-center space-x-5 3xl:pl-12 py-4 pr-3 text-sm font-medium text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
            <div
                :style="{
                    backgroundColor: project.color,
                    boxShadow: `var(--tw-ring-inset) 0 0 0 calc(4px + var(--tw-ring-offset-width)) ${project.color}30`,
                }"
                class="w-3 h-3 rounded-full"></div>
            <span class="overflow-ellipsis overflow-hidden">
                {{ project.name }}
            </span>
            <span class="text-text-secondary"> {{ projectTasksCount }} Tasks </span>
        </div>
        <div class="whitespace-nowrap min-w-0 px-3 py-4 text-sm text-text-secondary">
            <div
                v-if="project.client_id"
                class="overflow-ellipsis overflow-hidden">
                {{ client?.name }}
            </div>
            <div v-else>No client</div>
        </div>
        <div class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary">
            <div v-if="project.spent_time">
                {{ formatHumanReadableDuration(project.spent_time) }}
            </div>
            <div v-else>--</div>
        </div>
        <div
            class="whitespace-nowrap px-3 flex items-center text-sm text-text-secondary">
            <UpgradeBadge
                v-if="!isAllowedToPerformPremiumAction()"></UpgradeBadge>
            <EstimatedTimeProgress
                v-else-if="project.estimated_time"
                :estimated="project.estimated_time"
                :current="project.spent_time"></EstimatedTimeProgress>
            <span v-else> -- </span>
        </div>
        <div
            v-if="showBillableRate"
            class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary">
            {{ billableRateInfo }}
        </div>
        <div
            class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary flex space-x-1 items-center font-medium">
            <CheckCircleIcon class="w-5"></CheckCircleIcon>
            <span>Active</span>
        </div>
        <div
            class="relative whitespace-nowrap flex items-center pl-3 text-right text-sm font-medium pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
            <ProjectMoreOptionsDropdown
                :project="project"
                @edit="showEditProjectModal = true"
                @archive="archiveProject"
                @delete="deleteProject"></ProjectMoreOptionsDropdown>
        </div>
    </TableRow>
</template>

<style scoped></style>
