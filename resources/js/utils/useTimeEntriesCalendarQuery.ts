import { useQuery } from '@tanstack/vue-query';
import { api, type TimeEntryResponse, type TimeEntry } from '@/packages/api/src';
import { getCurrentMembershipId, getCurrentOrganizationId } from '@/utils/useUser';
import { computed, type Ref } from 'vue';
import { getDayJsInstance } from '@/packages/ui/src/utils/time';
import { getUserTimezone } from '@/packages/ui/src/utils/settings';

export function useTimeEntriesCalendarQuery(
    calendarStart: Ref<Date | undefined>,
    calendarEnd: Ref<Date | undefined>
) {
    const enableCalendarQuery = computed(() => {
        return !!getCurrentOrganizationId() && !!calendarStart.value && !!calendarEnd.value;
    });

    // Calculate expanded date range to include previous and next periods with timezone transformations
    const expandedDateRange = computed(() => {
        if (!calendarStart.value || !calendarEnd.value) {
            return { start: null, end: null };
        }

        const dayjs = getDayJsInstance();
        const duration = dayjs(calendarEnd.value).diff(dayjs(calendarStart.value), 'milliseconds');

        // Calculate previous period
        const previousStart = dayjs(calendarStart.value).subtract(duration, 'milliseconds');
        // Calculate next period
        const nextEnd = dayjs(calendarEnd.value).add(duration, 'milliseconds');

        // Apply timezone transformations
        const formattedStart = previousStart.utc().tz(getUserTimezone(), true).utc().format();
        const formattedEnd = nextEnd.utc().tz(getUserTimezone(), true).utc().format();

        return {
            start: formattedStart,
            end: formattedEnd,
        };
    });

    return useQuery<TimeEntryResponse>({
        queryKey: computed(() => [
            'timeEntries',
            'calendar',
            {
                start: expandedDateRange.value.start,
                end: expandedDateRange.value.end,
                organization: getCurrentOrganizationId(),
            },
        ]),
        enabled: enableCalendarQuery,
        placeholderData: (previousData) => previousData,
        queryFn: async () => {
            const allEntries: TimeEntry[] = [];

            while (true) {
                const response = await api.getTimeEntries({
                    params: {
                        organization: getCurrentOrganizationId() || '',
                    },
                    queries: {
                        start: expandedDateRange.value.start!,
                        end: expandedDateRange.value.end!,
                        member_id: getCurrentMembershipId(),
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
        },
        staleTime: 1000 * 30, // 30 seconds
    });
}
