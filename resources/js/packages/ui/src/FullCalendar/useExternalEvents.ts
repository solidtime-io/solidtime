import { computed, type ComputedRef, type Ref } from 'vue';
import type { Dayjs } from 'dayjs';
import type { CalendarSettings } from './calendarSettings';
import { getLocalizedDayJs } from '../utils/time';

export interface ExternalCalendarEvent {
    id: string;
    title: string;
    start: string;
    end: string;
    allDay: boolean;
    htmlLink: string | null;
}

export interface ExternalEventBox {
    dateStr: string;
    top: number;
    height: number;
    timeLabel: string;
    event: ExternalCalendarEvent;
}

/**
 * Positions external calendar events (e.g. Google Calendar meetings) as an
 * overlay layer in the day columns, using the same clamping rules as
 * useActivityBoxes. All-day events are not shown in the time grid.
 */
export function useExternalEvents(params: {
    externalEvents: () => ExternalCalendarEvent[] | undefined;
    viewDays: ComputedRef<Dayjs[]>;
    calendarSettings: Ref<CalendarSettings>;
    minutesToPixels: (minutes: number) => number;
}) {
    const externalEventBoxes = computed<ExternalEventBox[]>(() => {
        const events = params.externalEvents();
        if (!events || events.length === 0) return [];

        const s = params.calendarSettings.value;
        const startMin = s.startHour * 60;
        const endMin = s.endHour * 60;
        const boxes: ExternalEventBox[] = [];

        for (const day of params.viewDays.value) {
            const dateStr = day.format('YYYY-MM-DD');
            const dayStart = day.startOf('day');
            const dayEnd = day.endOf('day');

            for (const event of events) {
                if (event.allDay) continue;

                const eventStart = getLocalizedDayJs(event.start);
                const eventEnd = getLocalizedDayJs(event.end);

                if (eventEnd.isBefore(dayStart) || eventStart.isAfter(dayEnd)) continue;

                const actualStart = eventStart.isAfter(dayStart) ? eventStart : dayStart;
                const actualEnd = eventEnd.isBefore(dayEnd) ? eventEnd : dayEnd;

                const actualStartMin = actualStart.hour() * 60 + actualStart.minute();
                const actualEndMin = actualEnd.hour() * 60 + actualEnd.minute();

                const clampedStart = Math.max(actualStartMin, startMin);
                const clampedEnd = Math.min(actualEndMin, endMin);

                if (clampedEnd <= clampedStart) continue;

                const top = params.minutesToPixels(clampedStart - startMin);
                const height = params.minutesToPixels(clampedEnd - clampedStart);

                if (height > 0) {
                    boxes.push({
                        dateStr,
                        top,
                        height,
                        timeLabel: `${eventStart.format('HH:mm')} - ${eventEnd.format('HH:mm')}`,
                        event,
                    });
                }
            }
        }

        return boxes;
    });

    function externalEventsForDay(dateStr: string): ExternalEventBox[] {
        return externalEventBoxes.value.filter((b) => b.dateStr === dateStr);
    }

    return {
        externalEventBoxes,
        externalEventsForDay,
    };
}
