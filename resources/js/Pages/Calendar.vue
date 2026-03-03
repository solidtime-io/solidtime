<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { useTimeEntriesCalendarQuery } from '@/utils/useTimeEntriesCalendarQuery';
import { useTimeEntriesMutations } from '@/utils/useTimeEntriesMutations';
import { computed, ref } from 'vue';
import { useQueryClient } from '@tanstack/vue-query';
import {
    type Client,
    type CreateClientBody,
    type CreateProjectBody,
    type Project,
    type TimeEntry,
} from '@/packages/api/src';
import { getDayJsInstance } from '@/packages/ui/src/utils/time';
import { TimeEntryCalendar } from '@/packages/ui/src';
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

const calendarStart = ref<Date | undefined>(undefined);
const calendarEnd = ref<Date | undefined>(undefined);

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

async function duplicateTimeEntry(entry: TimeEntry): Promise<void> {
    await createTimeEntryMutation({
        start: entry.start,
        end: entry.end,
        billable: entry.billable,
        description: entry.description,
        project_id: entry.project_id,
        task_id: entry.task_id,
        tags: entry.tags,
    });
}

async function splitTimeEntry(entry: TimeEntry): Promise<void> {
    if (!entry.end) return;
    const start = getDayJsInstance()(entry.start);
    const end = getDayJsInstance()(entry.end);
    const midpoint = start.add(end.diff(start) / 2, 'millisecond').startOf('minute');

    // Update the original entry to end at the midpoint
    await updateTimeEntryMutation({ ...entry, end: midpoint.utc().format() });

    // Create a new entry from midpoint to original end
    await createTimeEntryMutation({
        start: midpoint.utc().format(),
        end: entry.end,
        billable: entry.billable,
        description: entry.description,
        project_id: entry.project_id,
        task_id: entry.task_id,
        tags: entry.tags,
    });
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
        queryKey: ['timeEntries', 'calendar'],
    });
}
</script>

<template>
    <AppLayout title="Calendar" data-testid="calendar_view" main-class="p-0">
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
            :create-time-entry="createTimeEntry"
            :update-time-entry="updateTimeEntry"
            :delete-time-entry="deleteTimeEntry"
            :duplicate-time-entry="duplicateTimeEntry"
            :split-time-entry="splitTimeEntry"
            :create-client="createClient"
            :create-project="createProject"
            :create-tag="createTag"
            @dates-change="onDatesChange"
            @refresh="onRefresh" />
    </AppLayout>
</template>
