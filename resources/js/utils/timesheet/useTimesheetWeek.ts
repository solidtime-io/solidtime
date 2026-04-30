import { computed, ref, watch } from 'vue';
import type { Dayjs } from 'dayjs';
import { useQueryClient } from '@tanstack/vue-query';
import { getDayJsInstance } from '@/packages/ui/src/utils/time';
import { getUserTimezone } from '@/packages/ui/src/utils/settings';
import { prefetchTimesheetWeek } from '@/utils/useTimesheetQuery';
import { getInitialWeekRange } from '@/utils/useTimeEntriesCalendarQuery';

/**
 * Owns week-navigation state for the timesheet page.
 *
 * Exposes the current week start/end, the list of day strings, derived
 * display helpers (week number, today's date, whether this is the
 * current week), and navigation functions.
 *
 * Also prefetches the adjacent weeks whenever `weekStart` changes so
 * that clicking prev/next feels instant.
 */
export function useTimesheetWeek() {
    const dayjs = getDayJsInstance();
    const queryClient = useQueryClient();

    const weekStart = ref<Dayjs>(getInitialWeekRange().start);
    const weekEnd = computed(() => weekStart.value.add(7, 'day'));

    const weekDays = computed(() => {
        const days: string[] = [];
        for (let i = 0; i < 7; i++) {
            days.push(weekStart.value.add(i, 'day').format('YYYY-MM-DD'));
        }
        return days;
    });

    const weekNumber = computed(() => weekStart.value.week());

    const isCurrentWeek = computed(() =>
        weekStart.value.isSame(getInitialWeekRange().start, 'day')
    );

    const todayDate = computed(() => {
        const tz = getUserTimezone();
        return dayjs().tz(tz).format('YYYY-MM-DD');
    });

    // Prefetch adjacent weeks so prev/next feels instant.
    watch(
        weekStart,
        () => {
            const prevStart = weekStart.value.subtract(7, 'day');
            const prevEnd = weekStart.value;
            const nextStart = weekStart.value.add(7, 'day');
            const nextEnd = weekStart.value.add(14, 'day');
            prefetchTimesheetWeek(queryClient, prevStart, prevEnd);
            prefetchTimesheetWeek(queryClient, nextStart, nextEnd);
        },
        { immediate: true }
    );

    function goToPreviousWeek() {
        weekStart.value = weekStart.value.subtract(7, 'day');
    }

    function goToNextWeek() {
        weekStart.value = weekStart.value.add(7, 'day');
    }

    function goToCurrentWeek() {
        weekStart.value = getInitialWeekRange().start;
    }

    return {
        weekStart,
        weekEnd,
        weekDays,
        weekNumber,
        isCurrentWeek,
        todayDate,
        goToPreviousWeek,
        goToNextWeek,
        goToCurrentWeek,
    };
}
