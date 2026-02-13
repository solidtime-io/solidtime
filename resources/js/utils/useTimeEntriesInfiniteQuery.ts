import { useInfiniteQuery } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { getCurrentMembershipId, getCurrentOrganizationId } from '@/utils/useUser';
import dayjs from 'dayjs';
import { computed } from 'vue';

export function useTimeEntriesInfiniteQuery() {
    const organizationId = computed(() => getCurrentOrganizationId());
    const memberId = computed(() => getCurrentMembershipId());

    return useInfiniteQuery({
        queryKey: computed(() => [
            'timeEntries',
            'infinite',
            { organizationId: organizationId.value, memberId: memberId.value },
        ]),
        queryFn: async ({ pageParam }) => {
            const orgId = organizationId.value;
            if (!orgId) return { data: [] };

            const queries: Record<string, string | undefined> = {
                only_full_dates: 'true',
                member_id: memberId.value,
                limit: '50',
            };

            if (pageParam) {
                queries.end = pageParam;
            }

            const response = await api.getTimeEntries({
                params: {
                    organization: orgId,
                },
                queries: queries,
            });

            return response;
        },
        initialPageParam: undefined as string | undefined,
        getNextPageParam: (lastPage) => {
            if (!lastPage?.data || lastPage.data.length === 0) {
                return undefined;
            }
            const latestTimeEntry = lastPage.data[lastPage.data.length - 1]!;
            return dayjs(latestTimeEntry.start).utc().format();
        },
        enabled: computed(() => !!organizationId.value),
        staleTime: 1000 * 30, // 30 seconds
        gcTime: 1000 * 60 * 10, // 10 minutes
    });
}
