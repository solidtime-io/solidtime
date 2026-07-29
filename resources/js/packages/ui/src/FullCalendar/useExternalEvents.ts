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
    left: string;
    width: string;
    timeLabel: string;
    event: ExternalCalendarEvent;
}

interface PositionedExternalEvent {
    box: Omit<ExternalEventBox, 'left' | 'width'>;
    startMin: number;
    endMin: number;
    col: number;
}

function assignColumns(events: Omit<PositionedExternalEvent, 'col'>[]): PositionedExternalEvent[] {
    const sorted = [...events].sort((a, b) => {
        if (a.startMin !== b.startMin) return a.startMin - b.startMin;
        return b.endMin - b.startMin - (a.endMin - a.startMin);
    });
    const columnEndMinutes: number[] = [];
    const positioned: PositionedExternalEvent[] = [];

    for (const event of sorted) {
        let col = 0;
        while (col < columnEndMinutes.length && columnEndMinutes[col]! > event.startMin) {
            col++;
        }
        columnEndMinutes[col] = event.endMin;
        positioned.push({ ...event, col });
    }

    return positioned;
}

function groupByOverlaps(events: PositionedExternalEvent[]): PositionedExternalEvent[][] {
    if (events.length === 0) return [];

    const sorted = [...events].sort((a, b) => a.startMin - b.startMin);
    const groups: PositionedExternalEvent[][] = [];
    let currentGroup: PositionedExternalEvent[] = [sorted[0]!];
    let currentGroupEnd = sorted[0]!.endMin;

    for (let i = 1; i < sorted.length; i++) {
        const event = sorted[i]!;
        if (event.startMin < currentGroupEnd) {
            currentGroup.push(event);
            currentGroupEnd = Math.max(currentGroupEnd, event.endMin);
        } else {
            groups.push(currentGroup);
            currentGroup = [event];
            currentGroupEnd = event.endMin;
        }
    }

    groups.push(currentGroup);
    return groups;
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
        const externalLeftPct = 55;
        const externalWidthPct = 45;

        for (const day of params.viewDays.value) {
            const dateStr = day.format('YYYY-MM-DD');
            const dayStart = day.startOf('day');
            const dayEnd = day.endOf('day');
            const dayBoxes: Omit<PositionedExternalEvent, 'col'>[] = [];

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
                    dayBoxes.push({
                        box: {
                            dateStr,
                            top,
                            height,
                            timeLabel: `${eventStart.format('HH:mm')} - ${eventEnd.format('HH:mm')}`,
                            event,
                        },
                        startMin: clampedStart,
                        endMin: clampedEnd,
                    });
                }
            }

            const withColumns = assignColumns(dayBoxes);
            const groups = groupByOverlaps(withColumns);
            for (const group of groups) {
                const totalCols = Math.max(...group.map((item) => item.col)) + 1;
                for (const item of group) {
                    const left = externalLeftPct + (item.col / totalCols) * externalWidthPct;
                    const width = externalWidthPct / totalCols;
                    boxes.push({
                        ...item.box,
                        left: `${left}%`,
                        width: `${width}%`,
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
