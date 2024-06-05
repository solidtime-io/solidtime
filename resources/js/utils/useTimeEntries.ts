import { defineStore } from 'pinia';
import {
    getCurrentMembershipId,
    getCurrentOrganizationId,
} from '@/utils/useUser';
import { api } from '../../../openapi.json.client';
import { reactive, ref } from 'vue';
import type { CreateTimeEntryBody, TimeEntry } from '@/utils/api';
import dayjs from 'dayjs';
import { useNotificationsStore } from '@/utils/notification';

export type TimeEntriesGroupedByType = TimeEntry & { timeEntries: TimeEntry[] };

export const useTimeEntriesStore = defineStore('timeEntries', () => {
    const timeEntries = ref<TimeEntry[]>(reactive([]));

    const allTimeEntriesLoaded = ref(false);
    const { handleApiRequestNotifications } = useNotificationsStore();

    async function fetchTimeEntries() {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            const timeEntriesResponse = await handleApiRequestNotifications(
                () =>
                    api.getTimeEntries({
                        params: {
                            organization: organizationId,
                        },
                        queries: {
                            only_full_dates: 'true',
                            member_id: getCurrentMembershipId(),
                        },
                    }),
                undefined,
                'Failed to fetch time entries'
            );
            if (timeEntriesResponse?.data) {
                timeEntries.value = timeEntriesResponse.data;
            }
        }
    }

    async function fetchMoreTimeEntries() {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            const latestTimeEntry =
                timeEntries.value[timeEntries.value.length - 1];
            dayjs(latestTimeEntry.start).utc().format('YYYY-MM-DD');

            const timeEntriesResponse = await handleApiRequestNotifications(
                () =>
                    api.getTimeEntries({
                        params: {
                            organization: organizationId,
                        },
                        queries: {
                            only_full_dates: 'true',
                            member_id: getCurrentMembershipId(),
                            end: dayjs(latestTimeEntry.start).utc().format(),
                        },
                    }),
                undefined,
                'Failed to fetch time entries'
            );
            if (
                timeEntriesResponse?.data &&
                timeEntriesResponse.data.length > 0
            ) {
                timeEntries.value = timeEntries.value.concat(
                    timeEntriesResponse.data
                );
            } else {
                allTimeEntriesLoaded.value = true;
            }
        }
    }

    async function updateTimeEntry(timeEntry: TimeEntry) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            await handleApiRequestNotifications(
                () =>
                    api.updateTimeEntry(timeEntry, {
                        params: {
                            organization: organizationId,
                            timeEntry: timeEntry.id,
                        },
                    }),
                'Time entry updated successfully',
                'Failed to update time entry'
            );
        }
    }

    async function createTimeEntry(
        timeEntry: Omit<CreateTimeEntryBody, 'member_id'>
    ) {
        const organizationId = getCurrentOrganizationId();
        const memberId = getCurrentMembershipId();
        if (organizationId && memberId !== undefined) {
            const newTimeEntry = {
                ...timeEntry,
                member_id: memberId,
            } as CreateTimeEntryBody;
            await handleApiRequestNotifications(
                () =>
                    api.createTimeEntry(newTimeEntry, {
                        params: {
                            organization: organizationId,
                        },
                    }),
                'Time entry created successfully',
                'Failed to create time entry'
            );
            await fetchTimeEntries();
        }
    }

    async function deleteTimeEntry(timeEntryId: string) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            await handleApiRequestNotifications(
                () =>
                    api.deleteTimeEntry(
                        {},
                        {
                            params: {
                                organization: organizationId,
                                timeEntry: timeEntryId,
                            },
                        }
                    ),
                'Time entry deleted successfully',
                'Failed to delete time entry'
            );
            await fetchTimeEntries();
        }
    }

    return {
        timeEntries,
        fetchTimeEntries,
        updateTimeEntry,
        createTimeEntry,
        deleteTimeEntry,
        fetchMoreTimeEntries,
        allTimeEntriesLoaded,
    };
});
