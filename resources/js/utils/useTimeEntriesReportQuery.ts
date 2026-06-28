import { useQuery, keepPreviousData } from '@tanstack/vue-query';
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
        // Keep the previous page's data (incl. meta.total) while the next page loads, so
        // pagination doesn't transiently see total=1 and clamp the page back to 1.
        placeholderData: keepPreviousData,
        staleTime: 1000 * 30, // 30 seconds
    });
}
