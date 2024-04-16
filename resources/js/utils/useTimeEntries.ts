import { defineStore } from 'pinia';
import { getCurrentOrganizationId, getCurrentUserId } from '@/utils/useUser';
import { api } from '../../../openapi.json.client';
import { reactive, ref } from 'vue';
import type { TimeEntry } from '@/utils/api';
import dayjs from 'dayjs';
import { useNotificationsStore } from '@/utils/notification';

export const useTimeEntriesStore = defineStore('timeEntries', () => {
    const timeEntries = ref<TimeEntry[]>(reactive([]));

    const allTimeEntriesLoaded = ref(false);
    const { handleApiRequestNotifications } = useNotificationsStore();
    async function fetchTimeEntries() {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            const timeEntriesResponse = await handleApiRequestNotifications(
                api.getTimeEntries({
                    params: {
                        organization: organizationId,
                    },
                    queries: {
                        only_full_dates: 'true',
                        user_id: getCurrentUserId(),
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
                api.getTimeEntries({
                    params: {
                        organization: organizationId,
                    },
                    queries: {
                        only_full_dates: 'true',
                        user_id: getCurrentUserId(),
                        before: dayjs(latestTimeEntry.start).utc().format(),
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

    async function createTimeEntry(timeEntry: TimeEntry) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            await handleApiRequestNotifications(
                api.createTimeEntry(timeEntry, {
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
