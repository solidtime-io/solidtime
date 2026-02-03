import { useQuery } from '@tanstack/vue-query';
import { api, type TimeEntryResponse, type TimeEntry } from '@/packages/api/src';
import { getCurrentMembershipId, getCurrentOrganizationId } from '@/utils/useUser';
import { computed, type Ref } from 'vue';
import { getDayJsInstance } from '@/packages/ui/src/utils/time';
import { getUserTimezone, getWeekStart } from '@/packages/ui/src/utils/settings';

const weekStartMap: Record<string, number> = {
    sunday: 0,
    monday: 1,
    tuesday: 2,
    wednesday: 3,
    thursday: 4,
    friday: 5,
    saturday: 6,
};

/**
 * Calculate expanded date range to include previous and next periods with timezone transformations.
 * This allows smooth navigation between calendar views without loading delays.
 */
export function getExpandedCalendarDateRange(
    calendarStart: Date,
    calendarEnd: Date
): { start: string; end: string } {
    const dayjs = getDayJsInstance();
    const duration = dayjs(calendarEnd).diff(dayjs(calendarStart), 'milliseconds');

    // Calculate previous period
    const previousStart = dayjs(calendarStart).subtract(duration, 'milliseconds');
    // Calculate next period
    const nextEnd = dayjs(calendarEnd).add(duration, 'milliseconds');

    // Apply timezone transformations
    const timezone = getUserTimezone();
    const formattedStart = previousStart.utc().tz(timezone, true).utc().format();
    const formattedEnd = nextEnd.utc().tz(timezone, true).utc().format();

    return {
        start: formattedStart,
        end: formattedEnd,
    };
}

/**
 * Get the initial week view date range based on user's week start preference.
 * Matches FullCalendar's timeGridWeek initial view.
 */
export function getInitialWeekRange(): { start: Date; end: Date } {
    const dayjs = getDayJsInstance();
    const weekStart = getWeekStart();
    const firstDay = weekStartMap[weekStart] ?? 1;

    const now = dayjs();
    const currentDayOfWeek = now.day();
    const daysFromWeekStart = (currentDayOfWeek - firstDay + 7) % 7;
    const calendarStart = now.subtract(daysFromWeekStart, 'day').startOf('day');
    const calendarEnd = calendarStart.add(7, 'day');

    return {
        start: calendarStart.toDate(),
        end: calendarEnd.toDate(),
    };
}

/**
 * Create the query key for calendar time entries.
 */
export function createCalendarQueryKey(
    start: string | null,
    end: string | null,
    organizationId: string | null
): readonly [
    'timeEntries',
    'calendar',
    { start: string | null; end: string | null; organization: string | null },
] {
    return ['timeEntries', 'calendar', { start, end, organization: organizationId }] as const;
}

/**
 * Fetch all calendar entries with pagination.
 */
export async function fetchAllCalendarEntries(
    organizationId: string,
    memberId: string | undefined,
    start: string,
    end: string
): Promise<TimeEntryResponse> {
    const allEntries: TimeEntry[] = [];

    while (true) {
        const response = await api.getTimeEntries({
            params: {
                organization: organizationId,
            },
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

export function useTimeEntriesCalendarQuery(
    calendarStart: Ref<Date | undefined>,
    calendarEnd: Ref<Date | undefined>
) {
    const enableCalendarQuery = computed(() => {
        return !!getCurrentOrganizationId() && !!calendarStart.value && !!calendarEnd.value;
    });

    const expandedDateRange = computed(() => {
        if (!calendarStart.value || !calendarEnd.value) {
            return { start: null, end: null };
        }
        return getExpandedCalendarDateRange(calendarStart.value, calendarEnd.value);
    });

    return useQuery<TimeEntryResponse>({
        queryKey: computed(() =>
            createCalendarQueryKey(
                expandedDateRange.value.start,
                expandedDateRange.value.end,
                getCurrentOrganizationId()
            )
        ),
        enabled: enableCalendarQuery,
        placeholderData: (previousData) => previousData,
        queryFn: async () => {
            return fetchAllCalendarEntries(
                getCurrentOrganizationId() || '',
                getCurrentMembershipId(),
                expandedDateRange.value.start!,
                expandedDateRange.value.end!
            );
        },
        staleTime: 1000 * 30, // 30 seconds
    });
}
