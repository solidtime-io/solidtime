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
import { getCurrentOrganizationId, getCurrentMembershipId } from '@/utils/useUser';
import { computed, ref } from 'vue';
import { getDayJsInstance } from '@/packages/ui/src/utils/time';
import { TimeEntryCalendar } from '@/packages/ui/src';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import { useTimeEntriesStore } from '@/utils/useTimeEntries';
import { useTagsStore } from '@/utils/useTags';
import { useProjectsQuery } from '@/utils/useProjectsQuery';
import { useClientsQuery } from '@/utils/useClientsQuery';
import { useTasksQuery } from '@/utils/useTasksQuery';
import { useTagsQuery } from '@/utils/useTagsQuery';
import { useProjectsStore } from '@/utils/useProjects';
import { useClientsStore } from '@/utils/useClients';
import { getUserTimezone } from '@/packages/ui/src/utils/settings';
import { getOrganizationCurrencyString } from '@/utils/money';
import { canCreateProjects } from '@/utils/permissions';

const calendarStart = ref<Date | undefined>(undefined);
const calendarEnd = ref<Date | undefined>(undefined);

const enableCalendarQuery = computed(() => {
    return !!getCurrentOrganizationId() && !!calendarStart.value && !!calendarEnd.value;
});

// Calculate expanded date range to include previous and next periods with timezone transformations
const expandedDateRange = computed(() => {
    if (!calendarStart.value || !calendarEnd.value) {
        return { start: null, end: null };
    }

    const dayjs = getDayJsInstance();
    const duration = dayjs(calendarEnd.value).diff(dayjs(calendarStart.value), 'milliseconds');

    // Calculate previous period
    const previousStart = dayjs(calendarStart.value).subtract(duration, 'milliseconds');
    // Calculate next period
    const nextEnd = dayjs(calendarEnd.value).add(duration, 'milliseconds');

    // Apply timezone transformations
    const formattedStart = previousStart.utc().tz(getUserTimezone(), true).utc().format();
    const formattedEnd = nextEnd.utc().tz(getUserTimezone(), true).utc().format();

    return {
        start: formattedStart,
        end: formattedEnd,
    };
});

const { data: timeEntryResponse, isLoading: timeEntriesLoading } = useQuery<TimeEntryResponse>({
    queryKey: computed(() => [
        'timeEntry',
        'calendar',
        {
            start: expandedDateRange.value.start,
            end: expandedDateRange.value.end,
            organization: getCurrentOrganizationId(),
        },
    ]),
    enabled: enableCalendarQuery,
    placeholderData: (previousData) => previousData,
    queryFn: () =>
        api.getTimeEntries({
            params: {
                organization: getCurrentOrganizationId() || '',
            },
            queries: {
                start: expandedDateRange.value.start!,
                end: expandedDateRange.value.end!,
                member_id: getCurrentMembershipId(),
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
            :currency="getOrganizationCurrencyString()"
            :can-create-project="canCreateProjects()"
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
