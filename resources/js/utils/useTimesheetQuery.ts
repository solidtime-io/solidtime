import { useQuery, type QueryClient } from '@tanstack/vue-query';
import { api, type TimeEntry, type TimeEntryResponse } from '@/packages/api/src';
import { getCurrentMembershipId, getCurrentOrganizationId } from '@/utils/useUser';
import { computed, type Ref } from 'vue';
import type { Dayjs } from 'dayjs';
import { localDateToUtc } from '@/packages/ui/src/utils/time';

function createTimesheetQueryKey(
    start: string | null,
    end: string | null,
    organizationId: string | null
) {
    return ['timeEntries', 'timesheet', { start, end, organization: organizationId }] as const;
}

async function fetchTimesheetEntries(
    organizationId: string,
    memberId: string | undefined,
    start: string,
    end: string
): Promise<TimeEntryResponse> {
    const allEntries: TimeEntry[] = [];

    while (true) {
        const response = await api.getTimeEntries({
            params: { organization: organizationId },
            queries: {
                start,
                end,
                member_id: memberId,
                offset: allEntries.length || undefined,
            },
        });

        if (response.data.length === 0) {
            return { data: allEntries, meta: response.meta };
        }

        allEntries.push(...response.data);

        if (allEntries.length >= response.meta.total) {
            return { data: allEntries, meta: response.meta };
        }
    }
}

export function useTimesheetQuery(
    weekStart: Ref<Dayjs | undefined>,
    weekEnd: Ref<Dayjs | undefined>
) {
    const enabled = computed(() => {
        return !!getCurrentOrganizationId() && !!weekStart.value && !!weekEnd.value;
    });

    const dateRange = computed(() => {
        if (!weekStart.value || !weekEnd.value) return { start: null, end: null };
        return {
            start: localDateToUtc(weekStart.value),
            end: localDateToUtc(weekEnd.value),
        };
    });

    return useQuery<TimeEntryResponse>({
        queryKey: computed(() =>
            createTimesheetQueryKey(
                dateRange.value.start,
                dateRange.value.end,
                getCurrentOrganizationId()
            )
        ),
        enabled,
        queryFn: async () => {
            return fetchTimesheetEntries(
                getCurrentOrganizationId() || '',
                getCurrentMembershipId(),
                dateRange.value.start!,
                dateRange.value.end!
            );
        },
        staleTime: 1000 * 30,
        placeholderData: (previousData) => previousData,
    });
}

export function prefetchTimesheetWeek(queryClient: QueryClient, weekStart: Dayjs, weekEnd: Dayjs) {
    const start = localDateToUtc(weekStart);
    const end = localDateToUtc(weekEnd);
    const organizationId = getCurrentOrganizationId();
    const memberId = getCurrentMembershipId();

    if (!organizationId) return;

    const queryKey = createTimesheetQueryKey(start, end, organizationId);

    queryClient.prefetchQuery({
        queryKey,
        queryFn: () => fetchTimesheetEntries(organizationId, memberId, start, end),
        staleTime: 1000 * 30,
    });
}

export { fetchTimesheetEntries };
