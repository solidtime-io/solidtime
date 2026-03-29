import { computed, ref, onUnmounted, type Ref, type ComputedRef } from 'vue';
import type { Dayjs } from 'dayjs';
import type { TimeEntry } from '@/packages/api/src';
import { getLocalizedDayJs, getLocalizedDayJsFromMinutes } from '../utils/time';
import type { CalendarSettings } from './calendarSettings';
import type { CalendarEvent, DayEvent } from './calendarTypes';
import { SLOT_HEIGHT, DRAG_THRESHOLD } from './calendarTypes';

export function useEventDrag(params: {
    calendarSettings: Ref<CalendarSettings>;
    viewDays: ComputedRef<Dayjs[]>;
    optimisticOverrides: Ref<Map<string, TimeEntry>>;
    updateTimeEntry: (entry: TimeEntry) => Promise<void>;
    emitRefresh: () => void;
    minutesToPixels: (minutes: number) => number;
    pixelsToMinutesFromMidnight: (px: number) => number;
    getDayFromClientX: (clientX: number) => string | null;
    clientYToGridPixels: (clientY: number) => number;
    onClickEvent: (ev: CalendarEvent) => void;
}) {
    const isDragging = ref(false);
    const dragEventId = ref<string | null>(null);
    const dragOffsetMinutes = ref(0);
    const dragCurrentTop = ref(0);
    const dragCurrentDay = ref<string | null>(null);
    const dragOriginalDayStr = ref<string | null>(null);
    const dragOriginalHeight = ref(0);
    const dragVisibleDurationMinutes = ref(0);

    // Non-reactive state
    let dragStartClientX = 0;
    let dragStartClientY = 0;
    let dragStartEventTop = 0;
    let dragOriginalEvent: CalendarEvent | null = null;
    let dragFullDurationMinutes = 0;
    let dragEventStartOffsetMinutes = 0;
    let hasMoved = false;

    function onEventPointerDown(e: PointerEvent, ev: CalendarEvent, dayEvent: DayEvent) {
        if (e.button !== 0) return;
        const target = e.target as HTMLElement;
        if (target.closest('.fc-event-resizer')) return;
        if (ev.isRunning) return;

        e.preventDefault();

        dragStartClientX = e.clientX;
        dragStartClientY = e.clientY;
        dragStartEventTop = dayEvent.top;
        dragOriginalEvent = ev;
        hasMoved = false;
        dragOriginalHeight.value = dayEvent.height;

        const s = params.calendarSettings.value;
        dragVisibleDurationMinutes.value = (dayEvent.height / SLOT_HEIGHT) * s.slotMinutes;

        const originDay = params.getDayFromClientX(e.clientX);
        dragOriginalDayStr.value = originDay;

        if (ev.timeEntry.end) {
            const evStart = getLocalizedDayJs(ev.timeEntry.start);
            const evEnd = getLocalizedDayJs(ev.timeEntry.end);
            dragFullDurationMinutes = evEnd.diff(evStart, 'minute');
        } else {
            dragFullDurationMinutes = dragVisibleDurationMinutes.value;
        }

        if (dayEvent.isClippedStart && originDay && ev.timeEntry.end) {
            const dayMidnight = getLocalizedDayJsFromMinutes(originDay, 0);
            const evStart = getLocalizedDayJs(ev.timeEntry.start);
            const eventStartFromGridStart = evStart.diff(dayMidnight, 'minute') - s.startHour * 60;
            const segmentTopMinutes = (dayEvent.top / SLOT_HEIGHT) * s.slotMinutes;
            dragEventStartOffsetMinutes = segmentTopMinutes - eventStartFromGridStart;
        } else {
            dragEventStartOffsetMinutes = 0;
        }

        const gridY = params.clientYToGridPixels(e.clientY);
        dragOffsetMinutes.value =
            params.pixelsToMinutesFromMidnight(gridY) -
            params.pixelsToMinutesFromMidnight(dayEvent.top);

        document.addEventListener('pointermove', onDragPointerMove);
        document.addEventListener('pointerup', onDragPointerUp);
    }

    function onDragPointerMove(e: PointerEvent) {
        const dx = e.clientX - dragStartClientX;
        const dy = e.clientY - dragStartClientY;

        if (!hasMoved && Math.sqrt(dx * dx + dy * dy) < DRAG_THRESHOLD) {
            return;
        }

        if (!hasMoved) {
            hasMoved = true;
            isDragging.value = true;
            dragEventId.value = dragOriginalEvent!.id;
        }

        const gridY = params.clientYToGridPixels(e.clientY);
        const s = params.calendarSettings.value;
        const startMin = s.startHour * 60;

        const rawMinutes = params.pixelsToMinutesFromMidnight(gridY) - dragOffsetMinutes.value;
        const snappedMinutes = Math.floor(rawMinutes / s.snapMinutes) * s.snapMinutes;
        const lowerBound = startMin - 4 * 60;
        const clampedMinutes = Math.max(lowerBound, Math.min(snappedMinutes, s.endHour * 60));
        dragCurrentTop.value = params.minutesToPixels(clampedMinutes - startMin);

        const dayStr = params.getDayFromClientX(e.clientX);
        if (dayStr) {
            dragCurrentDay.value = dayStr;
        }
    }

    async function onDragPointerUp(e: PointerEvent) {
        document.removeEventListener('pointermove', onDragPointerMove);
        document.removeEventListener('pointerup', onDragPointerUp);

        if (!hasMoved) {
            isDragging.value = false;
            dragEventId.value = null;
            dragOriginalDayStr.value = null;
            dragCurrentDay.value = null;
            if (dragOriginalEvent && !dragOriginalEvent.isRunning) {
                params.onClickEvent(dragOriginalEvent);
            }
            return;
        }

        const targetDateStr =
            dragCurrentDay.value ||
            dragOriginalDayStr.value ||
            params.viewDays.value[0]!.format('YYYY-MM-DD');
        const savedOriginalDayStr = dragOriginalDayStr.value || targetDateStr;

        isDragging.value = false;
        dragEventId.value = null;
        dragOriginalDayStr.value = null;
        dragCurrentDay.value = null;

        if (!dragOriginalEvent) return;
        const timeEntry = dragOriginalEvent.timeEntry;
        if (!timeEntry.end) return;

        const s = params.calendarSettings.value;
        const gridY = params.clientYToGridPixels(e.clientY);
        const rawMinutes = params.pixelsToMinutesFromMidnight(gridY) - dragOffsetMinutes.value;
        const snappedMinutes = Math.floor(rawMinutes / s.snapMinutes) * s.snapMinutes;
        const startMin = s.startHour * 60;
        const lowerBound = startMin - 4 * 60;
        const clampedMinutes = Math.max(lowerBound, Math.min(snappedMinutes, s.endHour * 60));

        const originalSegmentStart = getLocalizedDayJsFromMinutes(
            savedOriginalDayStr,
            startMin + params.pixelsToMinutesFromMidnight(dragStartEventTop)
        );
        const newSegmentStart = getLocalizedDayJsFromMinutes(targetDateStr, clampedMinutes);
        const deltaMs = newSegmentStart.diff(originalSegmentStart);

        const origStart = getLocalizedDayJs(timeEntry.start);
        const origEnd = getLocalizedDayJs(timeEntry.end);
        const durationMs = origEnd.diff(origStart);
        const newStartLocal = origStart.add(deltaMs, 'millisecond');
        const newEndLocal = newStartLocal.add(durationMs, 'millisecond');

        const updatedTimeEntry = {
            ...timeEntry,
            start: newStartLocal.utc().format(),
            end: newEndLocal.utc().format(),
        } as TimeEntry;

        params.optimisticOverrides.value = new Map(params.optimisticOverrides.value).set(
            updatedTimeEntry.id,
            updatedTimeEntry
        );

        try {
            await params.updateTimeEntry(updatedTimeEntry);
        } catch {
            // Revert optimistic override on failure; mutation layer already shows error notification
            const reverted = new Map(params.optimisticOverrides.value);
            reverted.delete(updatedTimeEntry.id);
            params.optimisticOverrides.value = reverted;
        }
        params.emitRefresh();
    }

    /**
     * Computes a preview style for every day column that the dragged event
     * would span. Derives the actual start/end datetime of the moved event,
     * then clips each view day's grid to show the visible portion.
     */
    const dragPreviewsByDay = computed<Record<string, Record<string, string>>>(() => {
        if (!isDragging.value || !dragOriginalEvent) return {};
        if (!dragCurrentDay.value) return {};

        const s = params.calendarSettings.value;
        const gridTotalMinutes = (s.endHour - s.startHour) * 60;
        const startMin = s.startHour * 60;
        const currentTopMinutes = (dragCurrentTop.value / SLOT_HEIGHT) * s.slotMinutes;

        const offset =
            dragCurrentDay.value === dragOriginalDayStr.value ? dragEventStartOffsetMinutes : 0;

        // Minutes from grid-start on cursor day where the event visually begins
        const eventStartOnGrid = currentTopMinutes - offset;

        const baseStyle = {
            position: 'absolute',
            left: '0',
            right: '0',
            backgroundColor: dragOriginalEvent.backgroundColor,
            borderColor: dragOriginalEvent.borderColor,
            opacity: '0.7',
            zIndex: '100',
            borderRadius: 'calc(var(--radius) - 4px)',
            border: '1px solid var(--border)',
        };

        // Single-day fast path: event fits within cursor day's grid
        const eventEndOnGrid = eventStartOnGrid + dragFullDurationMinutes;
        if (eventEndOnGrid <= gridTotalMinutes && eventStartOnGrid >= 0) {
            const previewTop = params.minutesToPixels(eventStartOnGrid);
            const previewHeight = params.minutesToPixels(
                Math.max(s.snapMinutes, dragFullDurationMinutes)
            );
            return {
                [dragCurrentDay.value]: {
                    ...baseStyle,
                    top: `${previewTop}px`,
                    height: `${previewHeight}px`,
                },
            };
        }

        // Multi-day: compute actual start/end datetimes, then clip per day
        const eventStartAbsolute = getLocalizedDayJsFromMinutes(
            dragCurrentDay.value,
            startMin + eventStartOnGrid
        );
        const eventEndAbsolute = getLocalizedDayJsFromMinutes(
            dragCurrentDay.value,
            startMin + eventStartOnGrid + dragFullDurationMinutes
        );

        const result: Record<string, Record<string, string>> = {};

        for (const viewDay of params.viewDays.value) {
            const dayStr = viewDay.format('YYYY-MM-DD');
            const dayGridStart = viewDay.startOf('day').add(s.startHour, 'hour');
            const dayGridEnd = viewDay.startOf('day').add(s.endHour, 'hour');

            // Does the event overlap this day's grid window?
            if (eventEndAbsolute.isAfter(dayGridStart) && eventStartAbsolute.isBefore(dayGridEnd)) {
                const segStart = eventStartAbsolute.isAfter(dayGridStart)
                    ? eventStartAbsolute
                    : dayGridStart;
                const segEnd = eventEndAbsolute.isBefore(dayGridEnd)
                    ? eventEndAbsolute
                    : dayGridEnd;
                const segStartMin = segStart.diff(dayGridStart, 'minute');
                const segEndMin = segEnd.diff(dayGridStart, 'minute');
                const segHeight = Math.max(s.snapMinutes, segEndMin - segStartMin);

                result[dayStr] = {
                    ...baseStyle,
                    top: `${params.minutesToPixels(segStartMin)}px`,
                    height: `${params.minutesToPixels(segHeight)}px`,
                };
            }
        }

        return result;
    });

    onUnmounted(() => {
        document.removeEventListener('pointermove', onDragPointerMove);
        document.removeEventListener('pointerup', onDragPointerUp);
    });

    return {
        isDragging,
        dragEventId,
        dragCurrentTop,
        dragCurrentDay,
        dragOriginalDayStr,
        dragOriginalHeight,
        dragVisibleDurationMinutes,
        dragPreviewsByDay,
        onEventPointerDown,
    };
}
