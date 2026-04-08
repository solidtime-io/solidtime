import { computed, type ComputedRef } from 'vue';
import type { Dayjs } from 'dayjs';
import type { ActivityPeriod, WindowActivityInPeriod } from './activityTypes';
import type { CalendarSettings } from './calendarSettings';
import type { ActivityBox } from './calendarTypes';
import type { Ref } from 'vue';
import { getLocalizedDayJs } from '../utils/time';

export function useActivityBoxes(params: {
    activityPeriods: () => ActivityPeriod[] | undefined;
    viewDays: ComputedRef<Dayjs[]>;
    calendarSettings: Ref<CalendarSettings>;
    minutesToPixels: (minutes: number) => number;
}) {
    function getActivityBoxLabel(box: ActivityBox): string {
        const periodStart = getLocalizedDayJs(box.period.start);
        const periodEnd = getLocalizedDayJs(box.period.end);
        const startText = periodStart.format('HH:mm');
        const endText = periodEnd.format('HH:mm');
        const status = box.isIdle ? 'Idling' : 'Active';
        return `${status} (${startText} - ${endText})`;
    }

    function getActivityBoxActivities(box: ActivityBox) {
        return box.period.windowActivities ?? [];
    }

    function getActivityPercentage(count: number, total: number): string {
        if (total === 0) return '0';
        return ((count / total) * 100).toFixed(0);
    }

    function getActivityText(activity: WindowActivityInPeriod): string {
        return activity.label ? `${activity.appName} - ${activity.label}` : activity.appName;
    }

    function getTopActivity(box: ActivityBox): WindowActivityInPeriod | null {
        const activities = box.period.windowActivities;
        if (!activities || activities.length === 0) return null;
        return activities.reduce<WindowActivityInPeriod>(
            (top, a) => (a.count > top.count ? a : top),
            activities[0]!
        );
    }

    const activityBoxes = computed<ActivityBox[]>(() => {
        const periods = params.activityPeriods();
        if (!periods || periods.length === 0) return [];

        const s = params.calendarSettings.value;
        const startMin = s.startHour * 60;
        const endMin = s.endHour * 60;
        const boxes: ActivityBox[] = [];

        for (const day of params.viewDays.value) {
            const dateStr = day.format('YYYY-MM-DD');
            const dayStart = day.startOf('day');
            const dayEnd = day.endOf('day');

            for (const period of periods) {
                const periodStart = getLocalizedDayJs(period.start);
                const periodEnd = getLocalizedDayJs(period.end);

                if (periodEnd.isBefore(dayStart) || periodStart.isAfter(dayEnd)) continue;

                const actualStart = periodStart.isAfter(dayStart) ? periodStart : dayStart;
                const actualEnd = periodEnd.isBefore(dayEnd) ? periodEnd : dayEnd;

                const actualStartMin = actualStart.hour() * 60 + actualStart.minute();
                const actualEndMin = actualEnd.hour() * 60 + actualEnd.minute();

                const clampedStart = Math.max(actualStartMin, startMin);
                const clampedEnd = Math.min(actualEndMin, endMin);

                if (clampedEnd <= clampedStart) continue;

                const top = params.minutesToPixels(clampedStart - startMin);
                const height = params.minutesToPixels(clampedEnd - clampedStart);

                if (height > 0) {
                    boxes.push({ dateStr, top, height, isIdle: period.isIdle, period });
                }
            }
        }

        return boxes;
    });

    function activityBoxesForDay(dateStr: string): ActivityBox[] {
        return activityBoxes.value.filter((b) => b.dateStr === dateStr);
    }

    function dayHasActivityStatus(dateStr: string): boolean {
        return activityBoxes.value.some((b) => b.dateStr === dateStr);
    }

    return {
        activityBoxes,
        activityBoxesForDay,
        dayHasActivityStatus,
        getActivityBoxLabel,
        getActivityBoxActivities,
        getActivityPercentage,
        getActivityText,
        getTopActivity,
    };
}
