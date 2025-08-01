<script setup lang="ts">
import { ClockIcon } from '@heroicons/vue/20/solid';
import CardTitle from '@/packages/ui/src/CardTitle.vue';
import { usePage } from '@inertiajs/vue3';
import { type User } from '@/types/models';
import { computed, onMounted, watch } from 'vue';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import duration from 'dayjs/plugin/duration';

import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { storeToRefs } from 'pinia';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { switchOrganization } from '@/utils/useOrganization';
import { useProjectsStore } from '@/utils/useProjects';
import { useTasksStore } from '@/utils/useTasks';
import { useTagsStore } from '@/utils/useTags';
import TimeTrackerControls from '@/packages/ui/src/TimeTracker/TimeTrackerControls.vue';
import type { CreateClientBody, CreateProjectBody, Project } from '@/packages/api/src';
import TimeTrackerRunningInDifferentOrganizationOverlay from '@/packages/ui/src/TimeTracker/TimeTrackerRunningInDifferentOrganizationOverlay.vue';
import { useClientsStore } from '@/utils/useClients';
import { getOrganizationCurrencyString } from '@/utils/money';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import { canCreateProjects } from '@/utils/permissions';

const page = usePage<{
    auth: {
        user: User;
    };
}>();
dayjs.extend(duration);

dayjs.extend(utc);

const currentTimeEntryStore = useCurrentTimeEntryStore();
const { currentTimeEntry, isActive, now } = storeToRefs(currentTimeEntryStore);
const { startLiveTimer, stopLiveTimer, setActiveState } = currentTimeEntryStore;

const projectStore = useProjectsStore();
const { projects } = storeToRefs(projectStore);
const taskStore = useTasksStore();
const { tasks } = storeToRefs(taskStore);
const clientStore = useClientsStore();
const { clients } = storeToRefs(clientStore);

const emit = defineEmits<{
    change: [];
}>();

watch(isActive, () => {
    if (isActive.value) {
        startLiveTimer();
    } else {
        stopLiveTimer();
    }
    emit('change');
});

onMounted(async () => {
    if (page.props.auth.user.current_team_id) {
        await currentTimeEntryStore.fetchCurrentTimeEntry();
        now.value = dayjs().utc();
    }
});

function updateTimeEntry() {
    if (currentTimeEntry.value.id) {
        useCurrentTimeEntryStore().updateTimer();
    }
}

const isRunningInDifferentOrganization = computed(() => {
    return (
        currentTimeEntry.value.organization_id &&
        getCurrentOrganizationId() &&
        currentTimeEntry.value.organization_id !== getCurrentOrganizationId()
    );
});

async function createProject(project: CreateProjectBody): Promise<Project | undefined> {
    const newProject = await useProjectsStore().createProject(project);
    if (newProject) {
        currentTimeEntry.value.project_id = newProject.id;
    }
    return newProject;
}
async function createClient(client: CreateClientBody) {
    return await useClientsStore().createClient(client);
}

function switchToTimeEntryOrganization() {
    if (currentTimeEntry.value.organization_id) {
        switchOrganization(currentTimeEntry.value.organization_id);
    }
}
async function createTag(tag: string) {
    return await useTagsStore().createTag(tag);
}

const { tags } = storeToRefs(useTagsStore());
</script>

<template>
    <CardTitle title="Time Tracker" :icon="ClockIcon"></CardTitle>
    <div class="relative">
        <TimeTrackerRunningInDifferentOrganizationOverlay
            v-if="isRunningInDifferentOrganization"
            @switch-organization="
                switchToTimeEntryOrganization
            "></TimeTrackerRunningInDifferentOrganizationOverlay>

        <TimeTrackerControls
            v-model:current-time-entry="currentTimeEntry"
            v-model:live-timer="now"
            :create-project
            :enable-estimated-time="isAllowedToPerformPremiumAction()"
            :can-create-project="canCreateProjects()"
            :create-client
            :clients
            :tags
            :tasks
            :projects
            :create-tag
            :is-active
            :currency="getOrganizationCurrencyString()"
            @start-live-timer="startLiveTimer"
            @stop-live-timer="stopLiveTimer"
            @start-timer="setActiveState(true)"
            @stop-timer="setActiveState(false)"
            @update-time-entry="updateTimeEntry"></TimeTrackerControls>
    </div>
</template>
