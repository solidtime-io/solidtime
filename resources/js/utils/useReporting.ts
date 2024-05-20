import { defineStore } from 'pinia';
import { api } from '../../../openapi.json.client';
import { computed, ref } from 'vue';
import type {
    AggregatedTimeEntries,
    AggregatedTimeEntriesQueryParams,
    ReportingResponse,
} from '@/utils/api';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';

export const useReportingStore = defineStore('reporting', () => {
    const reportingGraphResponse = ref<ReportingResponse | null>(null);
    const reportingTableResponse = ref<ReportingResponse | null>(null);

    const { handleApiRequestNotifications } = useNotificationsStore();

    async function fetchGraphReporting(
        params: AggregatedTimeEntriesQueryParams
    ) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            reportingGraphResponse.value = await handleApiRequestNotifications(
                api.getAggregatedTimeEntries({
                    params: {
                        organization: organization,
                    },
                    queries: params,
                }),
                undefined,
                'Failed to fetch reporting data'
            );
        }
    }

    async function fetchTableReporting(
        params: AggregatedTimeEntriesQueryParams
    ) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            reportingTableResponse.value = await handleApiRequestNotifications(
                api.getAggregatedTimeEntries({
                    params: {
                        organization: organization,
                    },
                    queries: params,
                }),
                undefined,
                'Failed to fetch reporting data'
            );
        }
    }

    const aggregatedGraphTimeEntries = computed<AggregatedTimeEntries>(() => {
        return reportingGraphResponse.value?.data as AggregatedTimeEntries;
    });

    const aggregatedTableTimeEntries = computed<AggregatedTimeEntries>(() => {
        return reportingTableResponse.value?.data as AggregatedTimeEntries;
    });

    return {
        aggregatedGraphTimeEntries,
        fetchGraphReporting,
        fetchTableReporting,
        aggregatedTableTimeEntries,
    };
});
