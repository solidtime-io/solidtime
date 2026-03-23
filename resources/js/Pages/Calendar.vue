<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { useTimeEntriesCalendarQuery } from '@/utils/useTimeEntriesCalendarQuery';
import { useTimeEntriesMutations } from '@/utils/useTimeEntriesMutations';
import { computed, ref, onMounted } from 'vue';
import { useQueryClient } from '@tanstack/vue-query';
import {
    type Client,
    type CreateClientBody,
    type CreateProjectBody,
    type Project,
} from '@/packages/api/src';
import { TimeEntryCalendar } from '@/packages/ui/src';
import type { ActivityPeriod } from '@/packages/ui/src/FullCalendar/activityTypes';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import { useTagsStore } from '@/utils/useTags';
import { useProjectsQuery } from '@/utils/useProjectsQuery';
import { useClientsQuery } from '@/utils/useClientsQuery';
import { useTasksQuery } from '@/utils/useTasksQuery';
import { useTagsQuery } from '@/utils/useTagsQuery';
import { useProjectsStore } from '@/utils/useProjects';
import { useClientsStore } from '@/utils/useClients';
import { getOrganizationCurrencyString } from '@/utils/money';
import { canCreateProjects } from '@/utils/permissions';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { useOrganizationQuery } from '@/utils/useOrganizationQuery';
import { getCurrentOrganizationId } from '@/utils/useUser';

const { organization } = useOrganizationQuery(getCurrentOrganizationId()!);
const calendarStart = ref<Date | undefined>(undefined);
const calendarEnd = ref<Date | undefined>(undefined);

// Test-injectable activity periods (for E2E testing).
// These hooks are no-ops in production — they only take effect when test code
// explicitly sets window globals, so they are safe to ship.
const testActivityPeriods = ref<ActivityPeriod[]>([]);

onMounted(() => {
    (window as unknown as Record<string, unknown>).__TEST_SET_ACTIVITY_PERIODS__ = (
        data: ActivityPeriod[]
    ) => {
        testActivityPeriods.value = data;
    };

    const windowData = (window as unknown as Record<string, unknown>).__TEST_ACTIVITY_PERIODS__;
    if (Array.isArray(windowData)) {
        setTimeout(() => {
            testActivityPeriods.value = windowData;
        }, 2000);
    }
});

const { data: timeEntryResponse, isLoading: timeEntriesLoading } = useTimeEntriesCalendarQuery(
    calendarStart,
    calendarEnd
);

const currentTimeEntries = computed(() => {
    return timeEntryResponse?.value?.data || [];
});

const {
    createTimeEntry: createTimeEntryMutation,
    updateTimeEntry: updateTimeEntryMutation,
    deleteTimeEntry: deleteTimeEntryMutation,
} = useTimeEntriesMutations();

// Wrap mutations to match expected Promise<void> return type
async function createTimeEntry(
    entry: Omit<import('@/packages/api/src').TimeEntry, 'id' | 'organization_id' | 'user_id'>
): Promise<void> {
    await createTimeEntryMutation(entry);
}

async function updateTimeEntry(entry: import('@/packages/api/src').TimeEntry): Promise<void> {
    await updateTimeEntryMutation(entry);
}

async function deleteTimeEntry(timeEntryId: string): Promise<void> {
    await deleteTimeEntryMutation(timeEntryId);
}

async function createTag(name: string) {
    return await useTagsStore().createTag(name);
}

async function createProject(project: CreateProjectBody): Promise<Project | undefined> {
    return await useProjectsStore().createProject(project);
}

async function createClient(body: CreateClientBody): Promise<Client | undefined> {
    return await useClientsStore().createClient(body);
}

const { projects } = useProjectsQuery();
const { tasks } = useTasksQuery();
const { clients } = useClientsQuery();
const { tags } = useTagsQuery();

const queryClient = useQueryClient();

function onDatesChange({ start, end }: { start: Date; end: Date }) {
    calendarStart.value = start;
    calendarEnd.value = end;
}

function onRefresh() {
    queryClient.invalidateQueries({
        queryKey: ['timeEntries'],
    });
    useCurrentTimeEntryStore().fetchCurrentTimeEntry();
}
</script>

<template>
    <AppLayout
        title="Calendar"
        data-testid="calendar_view"
        main-class="p-0 min-h-0 overflow-hidden">
        <TimeEntryCalendar
            :time-entries="currentTimeEntries"
            :projects="projects"
            :tasks="tasks"
            :clients="clients"
            :tags="tags"
            :loading="timeEntriesLoading"
            :enable-estimated-time="isAllowedToPerformPremiumAction()"
            :currency="getOrganizationCurrencyString()"
            :can-create-project="canCreateProjects()"
            :organization-billable-rate="organization?.billable_rate ?? null"
            :create-time-entry="createTimeEntry"
            :update-time-entry="updateTimeEntry"
            :delete-time-entry="deleteTimeEntry"
            :create-client="createClient"
            :create-project="createProject"
            :create-tag="createTag"
            :activity-periods="testActivityPeriods"
            @dates-change="onDatesChange"
            @refresh="onRefresh" />
    </AppLayout>
</template>
