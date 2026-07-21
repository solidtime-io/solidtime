<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import { useTimeEntriesCalendarQuery } from '@/utils/useTimeEntriesCalendarQuery';
import { useTimeEntriesMutations } from '@/utils/useTimeEntriesMutations';
import { computed, ref, onMounted } from 'vue';
import type { Dayjs } from 'dayjs';
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
import {
    useGoogleCalendarEventsQuery,
    useGoogleCalendarStatusQuery,
} from '@/utils/useGoogleCalendarQuery';
import type { ExternalCalendarEvent } from '@/packages/ui/src/FullCalendar/useExternalEvents';
import { useLocalStorage } from '@vueuse/core';
import { XMarkIcon } from '@heroicons/vue/20/solid';

const { organization } = useOrganizationQuery(getCurrentOrganizationId()!);
const calendarStart = ref<Dayjs | undefined>(undefined);
const calendarEnd = ref<Dayjs | undefined>(undefined);

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

const { data: googleCalendarStatus } = useGoogleCalendarStatusQuery();
const googleCalendarConnected = computed(() => googleCalendarStatus.value?.connected === true);
const {
    data: googleCalendarEvents,
    error: googleCalendarEventsError,
    refetch: refetchGoogleCalendarEvents,
} = useGoogleCalendarEventsQuery(calendarStart, calendarEnd, googleCalendarConnected);

const externalEvents = computed<ExternalCalendarEvent[]>(() => {
    return (googleCalendarEvents.value ?? []).map((event) => ({
        id: event.id,
        title: event.summary || '(No title)',
        start: event.start,
        end: event.end,
        allDay: event.all_day,
        htmlLink: event.html_link,
    }));
});

const googleCalendarConnectionBroken = computed(() => {
    const error = googleCalendarEventsError.value as {
        response?: { data?: { key?: string } };
    } | null;
    return error?.response?.data?.key === 'google_calendar_connection_broken';
});

const googleCalendarHintDismissed = useLocalStorage(
    'solidtime:google-calendar-hint-dismissed',
    false
);
const showGoogleCalendarConnectHint = computed(() => {
    return (
        googleCalendarStatus.value?.available === true &&
        !googleCalendarConnected.value &&
        !googleCalendarHintDismissed.value
    );
});

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

function onDatesChange({ start, end }: { start: Dayjs; end: Dayjs }) {
    calendarStart.value = start;
    calendarEnd.value = end;
}

function onRefresh() {
    queryClient.invalidateQueries({
        queryKey: ['timeEntries'],
    });
    useCurrentTimeEntryStore().fetchCurrentTimeEntry();
    if (googleCalendarConnected.value) {
        refetchGoogleCalendarEvents();
    }
}
</script>

<template>
    <AppLayout
        title="Calendar"
        data-testid="calendar_view"
        main-class="p-0 min-h-0 overflow-hidden">
        <div
            v-if="showGoogleCalendarConnectHint"
            class="flex items-center justify-between px-4 py-2 text-sm border-b border-border bg-secondary text-text-secondary">
            <span>
                See your meetings next to your time entries —
                <a
                    href="/settings/google-calendar/connect"
                    class="underline text-text-primary hover:opacity-80">
                    connect your Google Calendar</a
                >.
            </span>
            <button
                type="button"
                aria-label="Dismiss"
                class="p-1 hover:opacity-70"
                @click="googleCalendarHintDismissed = true">
                <XMarkIcon class="w-4 h-4" />
            </button>
        </div>
        <div
            v-if="googleCalendarConnectionBroken"
            class="flex items-center px-4 py-2 text-sm border-b border-border bg-secondary text-text-secondary">
            <span>
                Your Google Calendar connection is no longer valid —
                <a
                    href="/settings/google-calendar/connect"
                    class="underline text-text-primary hover:opacity-80">
                    reconnect your Google Calendar</a
                >.
            </span>
        </div>
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
            :external-events="externalEvents"
            @dates-change="onDatesChange"
            @refresh="onRefresh" />
    </AppLayout>
</template>
