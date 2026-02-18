import { useQuery } from '@tanstack/vue-query';
import { api, type TimeEntryResponse } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { computed, type Ref, type ComputedRef, unref } from 'vue';

export function useTimeEntriesReportQuery(
    filterParams: Ref<Record<string, unknown>> | ComputedRef<Record<string, unknown>>
) {
    return useQuery<TimeEntryResponse>({
        queryKey: computed(() => [
            'timeEntries',
            'detailed-report',
            getCurrentOrganizationId(),
            unref(filterParams),
        ]),
        enabled: computed(() => !!getCurrentOrganizationId()),
        queryFn: () =>
            api.getTimeEntries({
                params: {
                    organization: getCurrentOrganizationId() || '',
                },
                queries: { ...unref(filterParams) },
            }),
        staleTime: 1000 * 30, // 30 seconds
    });
}
