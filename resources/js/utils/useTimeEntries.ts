import { defineStore } from 'pinia';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { api } from '../../../openapi.json.client';
import { reactive, ref } from 'vue';
import type { TimeEntry } from '@/utils/api';

export const useTimeEntriesStore = defineStore('timeEntries', () => {
    const timeEntries = ref<TimeEntry[]>(reactive([]));

    async function fetchTimeEntries() {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            const timeEntriesResponse = await api.getTimeEntries({
                params: {
                    organization: organizationId,
                },
            });
            timeEntries.value = timeEntriesResponse.data;
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
    };
});
