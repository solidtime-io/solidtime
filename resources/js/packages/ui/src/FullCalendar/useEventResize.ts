import { computed, ref, onUnmounted, type Ref, type ComputedRef } from 'vue';
import type { Dayjs } from 'dayjs';
import type { TimeEntry } from '@/packages/api/src';
import { getDayJsInstance, getLocalizedDayJs, getLocalizedDayJsFromMinutes } from '../utils/time';
import type { CalendarSettings } from './calendarSettings';
import type { CalendarEvent, DayEvent } from './calendarTypes';
import { SLOT_HEIGHT } from './calendarTypes';

function snapTo(value: number, step: number): number {
    return Math.round(value / step) * step;
}

export function useEventResize(params: {
    calendarSettings: Ref<CalendarSettings>;
    viewDays: ComputedRef<Dayjs[]>;
    eventsByDay: ComputedRef<Record<string, DayEvent[]>>;
    optimisticOverrides: Ref<Map<string, TimeEntry>>;
    updateTimeEntry: (entry: TimeEntry) => Promise<void>;
    emitRefresh: () => void;
    minutesToPixels: (minutes: number) => number;
    pixelsToMinutesFromMidnight: (px: number) => number;
    getDayFromClientX: (clientX: number) => string | null;
    clientYToGridPixels: (clientY: number) => number;
}) {
    const isResizing = ref(false);
    const resizeEventId = ref<string | null>(null);
    const resizeEdge = ref<'start' | 'end'>('end');
    const resizeCurrentTop = ref(0);
    const resizeCurrentHeight = ref(0);
    const resizeCurrentDay = ref<string | null>(null);
    // Reactive so resizeLiveDurationSeconds recomputes during cross-day resize
    const lastResizeClientY = ref(0);

    let resizeOriginalEvent: CalendarEvent | null = null;
    let resizeOriginalTop = 0;
    let resizeOriginalHeight = 0;
    let resizeOriginalDayStr = '';

    function getGridConstants() {
        const s = params.calendarSettings.value;
        const startMin = s.startHour * 60;
        const endMin = s.endHour * 60;
        const snapPx = (s.snapMinutes / s.slotMinutes) * SLOT_HEIGHT;
        const totalGridPx = params.minutesToPixels(endMin - startMin);
        return { s, startMin, endMin, snapPx, totalGridPx };
    }

    /**
     * Compute the resolved start/end times for the current resize state.
     * Used by both resizeLiveDurationSeconds and onResizePointerUp to avoid
     * duplicating the 4-way (edge × cross-day) branching logic.
     *
     * For cross-day resizes, the cursor position (clientY) determines the
     * snapped time on the target day. For same-day resizes, the already-snapped
     * pixel state (resizeCurrentTop/Height) is used instead.
     */
    function computeResizedTimes(clientY: number): { start: Dayjs; end: Dayjs } | null {
        if (!resizeOriginalEvent) return null;

        const { s } = getGridConstants();
        const d = getDayJsInstance();
        const isCrossDay =
            resizeCurrentDay.value !== null && resizeCurrentDay.value !== resizeOriginalDayStr;

        function snappedMinutesFromCursor(): number {
            return snapTo(
                params.pixelsToMinutesFromMidnight(params.clientYToGridPixels(clientY)),
                s.snapMinutes
            );
        }

        if (resizeEdge.value === 'end') {
            const start = d(resizeOriginalEvent.timeEntry.start);
            const endDay =
                isCrossDay && resizeCurrentDay.value
                    ? resizeCurrentDay.value
                    : resizeOriginalDayStr;
            const endMinutes =
                isCrossDay && resizeCurrentDay.value
                    ? snappedMinutesFromCursor()
                    : snapTo(
                          params.pixelsToMinutesFromMidnight(
                              resizeCurrentTop.value + resizeCurrentHeight.value
                          ),
                          s.snapMinutes
                      );
            return { start, end: getLocalizedDayJsFromMinutes(endDay, endMinutes) };
        } else {
            const end = resizeOriginalEvent.isRunning
                ? getLocalizedDayJs()
                : d(resizeOriginalEvent.timeEntry.end!);
            const startDay =
                isCrossDay && resizeCurrentDay.value
                    ? resizeCurrentDay.value
                    : resizeOriginalDayStr;
            const startMinutes =
                isCrossDay && resizeCurrentDay.value
                    ? snappedMinutesFromCursor()
                    : snapTo(
                          params.pixelsToMinutesFromMidnight(resizeCurrentTop.value),
                          s.snapMinutes
                      );
            return { start: getLocalizedDayJsFromMinutes(startDay, startMinutes), end };
        }
    }

    function onResizerPointerDown(
        e: PointerEvent,
        ev: CalendarEvent,
        dayEvent: DayEvent,
        edge: 'start' | 'end',
        dayStr: string
    ) {
        e.preventDefault();
        e.stopPropagation();

        resizeOriginalEvent = ev;
        resizeEdge.value = edge;
        resizeOriginalTop = dayEvent.top;
        resizeOriginalHeight = dayEvent.height;
        resizeEventId.value = ev.id;
        isResizing.value = true;
        resizeCurrentTop.value = dayEvent.top;
        resizeCurrentHeight.value = dayEvent.height;
        resizeOriginalDayStr = dayStr;
        resizeCurrentDay.value = dayStr;

        document.body.classList.add('fc-resizing-active');
        document.addEventListener('pointermove', onResizePointerMove);
        document.addEventListener('pointerup', onResizePointerUp);
    }

    function onResizePointerMove(e: PointerEvent) {
        if (!resizeOriginalEvent) return;
        const { s, snapPx, totalGridPx } = getGridConstants();

        const dayStr = params.getDayFromClientX(e.clientX);
        if (dayStr) resizeCurrentDay.value = dayStr;
        lastResizeClientY.value = e.clientY;

        const isCrossDay = resizeCurrentDay.value !== resizeOriginalDayStr;
        const cursorSnapped = snapTo(params.clientYToGridPixels(e.clientY), snapPx);

        if (resizeEdge.value === 'end') {
            if (isCrossDay && resizeCurrentDay.value) {
                resizeCurrentHeight.value =
                    resizeCurrentDay.value > resizeOriginalDayStr
                        ? totalGridPx - resizeOriginalTop
                        : snapPx;
            } else {
                const snappedEnd = Math.max(
                    resizeOriginalTop + snapPx,
                    Math.min(cursorSnapped, totalGridPx)
                );
                resizeCurrentHeight.value = snappedEnd - resizeOriginalTop;
            }
        } else {
            let maxTopForRunning = Infinity;
            if (resizeOriginalEvent.isRunning) {
                const now = getLocalizedDayJs();
                const nowMinutes = now.hour() * 60 + now.minute() + now.second() / 60;
                maxTopForRunning = params.minutesToPixels(
                    Math.min(nowMinutes, s.endHour * 60) - s.startHour * 60
                );
            }

            if (isCrossDay && resizeCurrentDay.value) {
                if (resizeCurrentDay.value < resizeOriginalDayStr) {
                    resizeCurrentTop.value = 0;
                    resizeCurrentHeight.value = resizeOriginalTop + resizeOriginalHeight;
                } else if (!resizeOriginalEvent.isRunning) {
                    const bottom = resizeOriginalTop + resizeOriginalHeight;
                    resizeCurrentTop.value = bottom - snapPx;
                    resizeCurrentHeight.value = snapPx;
                }
            } else {
                const bottom = resizeOriginalTop + resizeOriginalHeight;
                const upperLimit = Math.min(bottom - snapPx, maxTopForRunning);
                const snappedStart = Math.max(0, Math.min(cursorSnapped, upperLimit));
                resizeCurrentTop.value = snappedStart;
                resizeCurrentHeight.value = bottom - snappedStart;
            }
        }
    }

    const resizeCrossDayPreviewsByDay = computed<Record<string, Record<string, string>>>(() => {
        if (!isResizing.value || !resizeOriginalEvent) return {};
        if (resizeCurrentDay.value === resizeOriginalDayStr) return {};

        const { startMin, snapPx, totalGridPx } = getGridConstants();
        const snappedY = snapTo(params.clientYToGridPixels(lastResizeClientY.value), snapPx);

        const baseStyle = {
            position: 'absolute',
            left: '0',
            right: '0',
            backgroundColor: resizeOriginalEvent.backgroundColor,
            borderColor: resizeOriginalEvent.borderColor,
            opacity: '0.5',
            zIndex: '100',
            borderRadius: 'calc(var(--radius) - 4px)',
            border: '1px solid var(--border)',
        };

        const result: Record<string, Record<string, string>> = {};
        const allDays = params.viewDays.value.map((d) => d.format('YYYY-MM-DD'));
        const originIdx = allDays.indexOf(resizeOriginalDayStr);
        const cursorIdx = allDays.indexOf(resizeCurrentDay.value!);
        if (originIdx < 0 || cursorIdx < 0) return {};

        const minIdx = Math.min(originIdx, cursorIdx);
        const maxIdx = Math.max(originIdx, cursorIdx);

        for (let i = minIdx; i <= maxIdx; i++) {
            const dayStr = allDays[i]!;
            if (dayStr === resizeOriginalDayStr) continue;

            if (dayStr === resizeCurrentDay.value) {
                if (resizeEdge.value === 'end') {
                    const eventStartDay = resizeOriginalEvent.dayStart.format('YYYY-MM-DD');
                    let previewTop = 0;
                    if (dayStr === eventStartDay) {
                        const eventStartMinutes =
                            resizeOriginalEvent.dayStart.hour() * 60 +
                            resizeOriginalEvent.dayStart.minute();
                        previewTop = params.minutesToPixels(
                            Math.max(0, eventStartMinutes - startMin)
                        );
                    }
                    const clampedY = Math.max(previewTop + snapPx, Math.min(snappedY, totalGridPx));
                    result[dayStr] = {
                        ...baseStyle,
                        top: `${previewTop}px`,
                        height: `${clampedY - previewTop}px`,
                    };
                } else {
                    const eventEndDay = resizeOriginalEvent.dayEnd.format('YYYY-MM-DD');
                    let previewBottom = totalGridPx;
                    if (dayStr === eventEndDay) {
                        const eventEndMinutes =
                            resizeOriginalEvent.dayEnd.hour() * 60 +
                            resizeOriginalEvent.dayEnd.minute();
                        previewBottom = params.minutesToPixels(
                            Math.max(0, eventEndMinutes - startMin)
                        );
                    }
                    const clampedY = Math.max(0, Math.min(snappedY, previewBottom - snapPx));
                    result[dayStr] = {
                        ...baseStyle,
                        top: `${clampedY}px`,
                        height: `${previewBottom - clampedY}px`,
                    };
                }
            } else {
                result[dayStr] = { ...baseStyle, top: '0px', height: `${totalGridPx}px` };
            }
        }

        return result;
    });

    const resizeLiveDurationSeconds = computed<number | null>(() => {
        if (!isResizing.value || !resizeOriginalEvent) return null;
        const times = computeResizedTimes(lastResizeClientY.value);
        if (!times) return null;
        const diff = times.end.diff(times.start, 'second');
        return diff > 0 ? diff : 0;
    });

    function getResizeOriginalDayStr(): string {
        return resizeOriginalDayStr;
    }

    function resetResizeState() {
        resizeEventId.value = null;
        resizeCurrentDay.value = null;
    }

    async function onResizePointerUp(e: PointerEvent) {
        document.removeEventListener('pointermove', onResizePointerMove);
        document.removeEventListener('pointerup', onResizePointerUp);
        document.body.classList.remove('fc-resizing-active');

        const times = computeResizedTimes(e.clientY);
        isResizing.value = false;

        if (!resizeOriginalEvent || !times) {
            resetResizeState();
            return;
        }

        const timeEntry = resizeOriginalEvent.timeEntry;
        const isRunning = resizeOriginalEvent.isRunning;

        const updatedTimeEntry = {
            ...timeEntry,
            start: resizeEdge.value === 'start' ? times.start.utc().format() : timeEntry.start,
            end:
                resizeEdge.value === 'end'
                    ? times.end.utc().format()
                    : isRunning
                      ? null
                      : timeEntry.end,
        } as TimeEntry;

        const d = getDayJsInstance();

        // Prevent end before start
        if (
            updatedTimeEntry.end !== null &&
            !d(updatedTimeEntry.end).isAfter(d(updatedTimeEntry.start))
        ) {
            resetResizeState();
            return;
        }

        // Prevent start in the future for running entries
        if (updatedTimeEntry.end === null && d(updatedTimeEntry.start).isAfter(d())) {
            resetResizeState();
            return;
        }

        resetResizeState();

        params.optimisticOverrides.value = new Map(params.optimisticOverrides.value).set(
            updatedTimeEntry.id,
            updatedTimeEntry
        );

        try {
            await params.updateTimeEntry(updatedTimeEntry);
        } catch {
            const reverted = new Map(params.optimisticOverrides.value);
            reverted.delete(updatedTimeEntry.id);
            params.optimisticOverrides.value = reverted;
        }
        params.emitRefresh();
    }

    onUnmounted(() => {
        document.removeEventListener('pointermove', onResizePointerMove);
        document.removeEventListener('pointerup', onResizePointerUp);
        document.body.classList.remove('fc-resizing-active');
    });

    return {
        isResizing,
        resizeEventId,
        resizeEdge,
        resizeCurrentTop,
        resizeCurrentHeight,
        resizeCurrentDay,
        resizeCrossDayPreviewsByDay,
        resizeLiveDurationSeconds,
        getResizeOriginalDayStr,
        onResizerPointerDown,
    };
}
