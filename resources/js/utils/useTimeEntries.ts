import { defineStore } from 'pinia';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { api } from '../../../openapi.json.client';
import { reactive, ref } from 'vue';
import type { TimeEntry } from '@/utils/api';
import dayjs from 'dayjs';

export const useTimeEntriesStore = defineStore('timeEntries', () => {
    const timeEntries = ref<TimeEntry[]>(reactive([]));

    const allTimeEntriesLoaded = ref(false);

    async function fetchTimeEntries() {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            const timeEntriesResponse = await api.getTimeEntries({
                params: {
                    organization: organizationId,
                },
                queries: {
                    only_full_dates: true,
                },
            });
            timeEntries.value = timeEntriesResponse.data;
        }
    }

    async function fetchMoreTimeEntries() {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            const latestTimeEntry =
                timeEntries.value[timeEntries.value.length - 1];
            dayjs(latestTimeEntry.start).utc().format('YYYY-MM-DD');

            const timeEntriesResponse = await api.getTimeEntries({
                params: {
                    organization: organizationId,
                },
                queries: {
                    only_full_dates: true,
                    before: dayjs(latestTimeEntry.start).utc().format(),
                },
            });
            if (timeEntriesResponse.data.length > 0) {
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
            await api.updateTimeEntry(timeEntry, {
                params: {
                    organization: organizationId,
                    timeEntry: timeEntry.id,
                },
            });
        }
    }

    async function createTimeEntry(timeEntry: TimeEntry) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            await api.createTimeEntry(timeEntry, {
                params: {
                    organization: organizationId,
                },
            });
            await fetchTimeEntries();
        }
    }

    async function deleteTimeEntry(timeEntryId: string) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            await api.deleteTimeEntry(
                {},
                {
                    params: {
                        organization: organizationId,
                        timeEntry: timeEntryId,
                    },
                }
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
