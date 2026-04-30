import { useQuery } from '@tanstack/vue-query';
import { api, type TimeEntryResponse, type TimeEntry } from '@/packages/api/src';
import { getCurrentMembershipId, getCurrentOrganizationId } from '@/utils/useUser';
import { computed, type Ref } from 'vue';
import type { Dayjs } from 'dayjs';
import { getDayJsInstance, localDateToUtc } from '@/packages/ui/src/utils/time';
import { getWeekStartDayNumber } from '@/packages/ui/src/utils/settings';

/**
 * Calculate expanded date range to include previous and next periods with timezone transformations.
 * This allows smooth navigation between calendar views without loading delays.
 */
export function getExpandedCalendarDateRange(
    calendarStart: Dayjs,
    calendarEnd: Dayjs
): { start: string; end: string } {
    const duration = calendarEnd.diff(calendarStart, 'milliseconds');

    // Calculate previous period
    const previousStart = calendarStart.subtract(duration, 'milliseconds');
    // Calculate next period
    const nextEnd = calendarEnd.add(duration, 'milliseconds');

    return {
        start: localDateToUtc(previousStart),
        end: localDateToUtc(nextEnd),
    };
}

/**
 * Get the initial week view date range based on user's week start preference.
 * Matches FullCalendar's timeGridWeek initial view.
 */
export function getInitialWeekRange(): { start: Dayjs; end: Dayjs } {
    const dayjs = getDayJsInstance();
    const firstDay = getWeekStartDayNumber();

    const now = dayjs();
    const currentDayOfWeek = now.day();
    const daysFromWeekStart = (currentDayOfWeek - firstDay + 7) % 7;
    const start = now.subtract(daysFromWeekStart, 'day').startOf('day');
    const end = start.add(7, 'day');

    return { start, end };
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
    calendarStart: Ref<Dayjs | undefined>,
    calendarEnd: Ref<Dayjs | undefined>
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
