<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import {
    api,
    type Client,
    type CreateClientBody,
    type CreateProjectBody,
    type Project,
    type TimeEntryResponse,
} from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { computed, ref } from 'vue';
import { getDayJsInstance } from '@/packages/ui/src/utils/time';
import { TimeEntryCalendar } from '@/packages/ui/src';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import { useTimeEntriesStore } from '@/utils/useTimeEntries';
import { useTagsStore } from '@/utils/useTags';
import { useProjectsStore } from '@/utils/useProjects';
import { useClientsStore } from '@/utils/useClients';
import { storeToRefs } from 'pinia';
import { useTasksStore } from '@/utils/useTasks';
import { getUserTimezone } from '@/packages/ui/src/utils/settings';

const calendarStart = ref<Date | undefined>(undefined);
const calendarEnd = ref<Date | undefined>(undefined);

const enableCalendarQuery = computed(() => {
    return !!getCurrentOrganizationId() && !!calendarStart.value && !!calendarEnd.value;
});

const formattedStartDate = computed(() => {
    return calendarStart.value
        ? getDayJsInstance()(calendarStart.value).utc().tz(getUserTimezone(), true).utc().format()
        : null;
});

const formattedEndDate = computed(() => {
    return calendarEnd.value
        ? getDayJsInstance()(calendarEnd.value).utc().tz(getUserTimezone(), true).utc().format()
        : null;
});

const { data: timeEntryResponse, isLoading: timeEntriesLoading } = useQuery<TimeEntryResponse>({
    queryKey: computed(() => [
        'timeEntry',
        'calendar',
        {
            start: formattedStartDate.value,
            end: formattedEndDate.value,
            organization: getCurrentOrganizationId(),
        },
    ]),
    enabled: enableCalendarQuery,
    queryFn: () =>
        api.getTimeEntries({
            params: {
                organization: getCurrentOrganizationId() || '',
            },
            queries: {
                start: formattedStartDate.value!,
                end: formattedEndDate.value!,
            },
        }),
});

const currentTimeEntries = computed(() => {
    return timeEntryResponse?.value?.data || [];
});

const { createTimeEntry, updateTimeEntry, deleteTimeEntry } = useTimeEntriesStore();

async function createTag(name: string) {
    return await useTagsStore().createTag(name);
}

async function createProject(project: CreateProjectBody): Promise<Project | undefined> {
    return await useProjectsStore().createProject(project);
}

async function createClient(body: CreateClientBody): Promise<Client | undefined> {
    return await useClientsStore().createClient(body);
}

const projectStore = useProjectsStore();
const { projects } = storeToRefs(projectStore);
const taskStore = useTasksStore();
const { tasks } = storeToRefs(taskStore);
const clientStore = useClientsStore();
const { clients } = storeToRefs(clientStore);
const tagsStore = useTagsStore();
const { tags } = storeToRefs(tagsStore);

const queryClient = useQueryClient();

// Helper functions to calculate adjacent date ranges for prefetching
function calculatePreviousRange(start: Date, end: Date): { start: Date; end: Date } {
    const dayjs = getDayJsInstance();
    const duration = dayjs(end).diff(dayjs(start), 'milliseconds');
    const previousEnd = dayjs(start);
    const previousStart = previousEnd.subtract(duration, 'milliseconds');
    return {
        start: previousStart.toDate(),
        end: previousEnd.toDate(),
    };
}

function calculateNextRange(start: Date, end: Date): { start: Date; end: Date } {
    const dayjs = getDayJsInstance();
    const duration = dayjs(end).diff(dayjs(start), 'milliseconds');
    const nextStart = dayjs(end);
    const nextEnd = nextStart.add(duration, 'milliseconds');
    return {
        start: nextStart.toDate(),
        end: nextEnd.toDate(),
    };
}

// Prefetch function for time entries
async function prefetchTimeEntries(start: Date, end: Date) {
    if (!getCurrentOrganizationId()) return;

    const startFormatted = getDayJsInstance()(start)
        .utc()
        .tz(getUserTimezone(), true)
        .utc()
        .format();
    const endFormatted = getDayJsInstance()(end).utc().tz(getUserTimezone(), true).utc().format();

    await queryClient.prefetchQuery({
        queryKey: [
            'timeEntry',
            'calendar',
            {
                start: startFormatted,
                end: endFormatted,
                organization: getCurrentOrganizationId(),
            },
        ],
        queryFn: () =>
            api.getTimeEntries({
                params: {
                    organization: getCurrentOrganizationId() || '',
                },
                queries: {
                    start: startFormatted,
                    end: endFormatted,
                },
            }),
    });
}

function onDatesChange({ start, end }: { start: Date; end: Date }) {
    calendarStart.value = start;
    calendarEnd.value = end;

    // Prefetch adjacent time ranges for better UX
    const previousRange = calculatePreviousRange(start, end);
    const nextRange = calculateNextRange(start, end);

    // Prefetch previous and next ranges
    prefetchTimeEntries(previousRange.start, previousRange.end);
    prefetchTimeEntries(nextRange.start, nextRange.end);
}

function onRefresh() {
    queryClient.invalidateQueries({
        queryKey: ['timeEntry', 'calendar'],
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
            :create-time-entry="createTimeEntry"
            :update-time-entry="updateTimeEntry"
            :delete-time-entry="deleteTimeEntry"
            :create-client="createClient"
            :create-project="createProject"
            :create-tag="createTag"
            @dates-change="onDatesChange"
            @refresh="onRefresh" />
    </AppLayout>
</template>
