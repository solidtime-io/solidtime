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
import { useProjectsQuery } from '@/utils/useProjectsQuery';
import { useTasksQuery } from '@/utils/useTasksQuery';
import { useTagsQuery } from '@/utils/useTagsQuery';
import { useClientsQuery } from '@/utils/useClientsQuery';
import { useTagsStore } from '@/utils/useTags';
import { useProjectsStore } from '@/utils/useProjects';
import TimeTrackerControls from '@/packages/ui/src/TimeTracker/TimeTrackerControls.vue';
import type {
    CreateClientBody,
    CreateProjectBody,
    CreateTimeEntryBody,
    Project,
    Tag,
} from '@/packages/api/src';
import TimeTrackerRunningInDifferentOrganizationOverlay from '@/packages/ui/src/TimeTracker/TimeTrackerRunningInDifferentOrganizationOverlay.vue';
import TimeTrackerMoreOptionsDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerMoreOptionsDropdown.vue';
import TimeEntryCreateModal from '@/packages/ui/src/TimeEntry/TimeEntryCreateModal.vue';
import { useClientsStore } from '@/utils/useClients';
import { getOrganizationCurrencyString } from '@/utils/money';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import { canCreateProjects } from '@/utils/permissions';
import { ref } from 'vue';
import { useTimeEntriesStore } from '@/utils/useTimeEntries';
import { useMutation, useQueryClient } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { useNotificationsStore } from '@/utils/notification';

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

const { projects } = useProjectsQuery();
const { tasks } = useTasksQuery();
const { clients } = useClientsQuery();

const emit = defineEmits<{
    change: [];
}>();

const showManualTimeEntryModal = ref(false);

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
async function createTag(tag: string): Promise<Tag | undefined> {
    return await useTagsStore().createTag(tag);
}

async function createTimeEntry(timeEntry: Omit<CreateTimeEntryBody, 'member_id'>) {
    await useTimeEntriesStore().createTimeEntry(timeEntry);
    showManualTimeEntryModal.value = false;
}

async function createTimeEntryFromCurrentEntry() {
    const { start, end, description, project_id, task_id, billable, tags } = currentTimeEntry.value;
    await createTimeEntry({ start, end, description, project_id, task_id, billable, tags });
    currentTimeEntryStore.$reset();
}

const { handleApiRequestNotifications } = useNotificationsStore();
const queryClient = useQueryClient();

const deleteTimeEntryMutation = useMutation({
    mutationFn: async (timeEntryId: string) => {
        const organizationId = getCurrentOrganizationId();
        if (!organizationId) {
            throw new Error('No organization selected');
        }
        return await api.deleteTimeEntry(undefined, {
            params: {
                organization: organizationId,
                timeEntry: timeEntryId,
            },
        });
    },
    onSuccess: async () => {
        await currentTimeEntryStore.fetchCurrentTimeEntry();
        await useTimeEntriesStore().fetchTimeEntries();
        queryClient.invalidateQueries({ queryKey: ['timeEntry'] });
        queryClient.invalidateQueries({ queryKey: ['timeEntries'] });
    },
});

async function discardCurrentTimeEntry() {
    if (currentTimeEntry.value.id) {
        await handleApiRequestNotifications(
            () => deleteTimeEntryMutation.mutateAsync(currentTimeEntry.value.id),
            'Time entry discarded successfully',
            'Failed to discard time entry'
        );
    }
}

const { tags } = useTagsQuery();
const { timeEntries } = storeToRefs(useTimeEntriesStore());
</script>

<template>
    <TimeEntryCreateModal
        v-model:show="showManualTimeEntryModal"
        :enable-estimated-time="isAllowedToPerformPremiumAction()"
        :create-project="createProject"
        :create-client="createClient"
        :create-tag="createTag"
        :create-time-entry="createTimeEntry"
        :currency="getOrganizationCurrencyString()"
        :can-create-project="canCreateProjects()"
        :projects
        :tasks
        :tags
        :clients></TimeEntryCreateModal>
    <CardTitle title="Time Tracker" :icon="ClockIcon"></CardTitle>
    <div class="relative">
        <TimeTrackerRunningInDifferentOrganizationOverlay
            v-if="isRunningInDifferentOrganization"
            @switch-organization="
                switchToTimeEntryOrganization
            "></TimeTrackerRunningInDifferentOrganizationOverlay>

        <div class="flex w-full items-center gap-2">
            <div class="flex w-full items-center gap-2">
                <div class="flex-1">
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
                        :time-entries
                        :create-tag
                        :is-active
                        :currency="getOrganizationCurrencyString()"
                        @start-live-timer="startLiveTimer"
                        @stop-live-timer="stopLiveTimer"
                        @start-timer="setActiveState(true)"
                        @stop-timer="setActiveState(false)"
                        @update-time-entry="updateTimeEntry"
                        @create-time-entry="createTimeEntryFromCurrentEntry"></TimeTrackerControls>
                </div>
                <TimeTrackerMoreOptionsDropdown
                    :has-active-timer="isActive"
                    @manual-entry="showManualTimeEntryModal = true"
                    @discard="discardCurrentTimeEntry"></TimeTrackerMoreOptionsDropdown>
            </div>
        </div>
    </div>
</template>
