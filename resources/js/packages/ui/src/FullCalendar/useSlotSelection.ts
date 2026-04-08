import { computed, ref, onUnmounted, type Ref, type ComputedRef } from 'vue';
import type { Dayjs } from 'dayjs';
import { getLocalizedDayJsFromMinutes } from '../utils/time';

import type { CalendarSettings } from './calendarSettings';
import { SLOT_HEIGHT } from './calendarTypes';

export function useSlotSelection(params: {
    calendarSettings: Ref<CalendarSettings>;
    viewDays: ComputedRef<Dayjs[]>;
    totalGridHeight: ComputedRef<number>;
    pixelsToMinutesFromMidnight: (px: number) => number;
    getDayFromClientX: (clientX: number) => string | null;
    clientYToGridPixels: (clientY: number) => number;
    onSelectionComplete: (start: Dayjs, end: Dayjs) => void;
}) {
    const isSelecting = ref(false);
    const selectionDay = ref<string | null>(null);
    const selectionTop = ref(0);
    const selectionHeight = ref(0);
    const selectionEndDay = ref<string | null>(null);
    const selectionEndTop = ref(0);
    const selectionEndHeight = ref(0);

    // Non-reactive state
    let selectionStartGridY = 0;
    let selectionStartDay = '';

    function onSlotPointerDown(e: PointerEvent) {
        if (e.button !== 0) return;
        const target = e.target as HTMLElement;
        if (target.closest('.fc-event')) return;

        const dateStr = params.getDayFromClientX(e.clientX);
        if (!dateStr) return;

        e.preventDefault();

        const gridY = params.clientYToGridPixels(e.clientY);
        const s = params.calendarSettings.value;
        const snapPx = (s.snapMinutes / s.slotMinutes) * SLOT_HEIGHT;
        const snappedY = Math.floor(gridY / snapPx) * snapPx;

        selectionStartGridY = snappedY;
        selectionStartDay = dateStr;
        selectionDay.value = dateStr;
        selectionTop.value = snappedY;
        selectionHeight.value = snapPx;
        selectionEndDay.value = null;
        selectionEndTop.value = 0;
        selectionEndHeight.value = 0;
        isSelecting.value = true;

        document.addEventListener('pointermove', onSelectionPointerMove);
        document.addEventListener('pointerup', onSelectionPointerUp);
    }

    function onSelectionPointerMove(e: PointerEvent) {
        if (!isSelecting.value) return;
        const gridY = params.clientYToGridPixels(e.clientY);
        const s = params.calendarSettings.value;
        const snapPx = (s.snapMinutes / s.slotMinutes) * SLOT_HEIGHT;
        const maxPx = params.totalGridHeight.value;

        const currentDay = params.getDayFromClientX(e.clientX);

        if (currentDay && currentDay !== selectionStartDay) {
            selectionTop.value = selectionStartGridY;
            selectionHeight.value = maxPx - selectionStartGridY;

            selectionEndDay.value = currentDay;
            const snappedEnd = Math.ceil(gridY / snapPx) * snapPx;
            selectionEndTop.value = 0;
            selectionEndHeight.value = Math.max(snapPx, Math.min(snappedEnd, maxPx));
        } else {
            selectionEndDay.value = null;

            if (gridY >= selectionStartGridY) {
                const snappedEnd = Math.ceil(gridY / snapPx) * snapPx;
                selectionTop.value = selectionStartGridY;
                selectionHeight.value = Math.max(
                    snapPx,
                    Math.min(snappedEnd - selectionStartGridY, maxPx - selectionStartGridY)
                );
            } else {
                const snappedStart = Math.floor(gridY / snapPx) * snapPx;
                selectionTop.value = Math.max(0, snappedStart);
                selectionHeight.value = Math.max(
                    snapPx,
                    selectionStartGridY + snapPx - selectionTop.value
                );
            }
        }
    }

    function onSelectionPointerUp() {
        document.removeEventListener('pointermove', onSelectionPointerMove);
        document.removeEventListener('pointerup', onSelectionPointerUp);

        if (!isSelecting.value) return;
        isSelecting.value = false;

        const s = params.calendarSettings.value;
        const snap = s.snapMinutes;
        const startMinutes = params.pixelsToMinutesFromMidnight(selectionTop.value);
        const snappedStartMin = Math.floor(startMinutes / snap) * snap;

        let startLocal;
        let endLocal;

        if (selectionEndDay.value && selectionEndDay.value !== selectionStartDay) {
            const endMinutes = params.pixelsToMinutesFromMidnight(
                selectionEndTop.value + selectionEndHeight.value
            );
            let snappedEndMin = Math.ceil(endMinutes / snap) * snap;
            if (snappedEndMin <= 0) snappedEndMin = snap;

            let startDateStr = selectionStartDay;
            let endDateStr = selectionEndDay.value;
            let startMin = snappedStartMin;
            let endMin = snappedEndMin;

            // Normalize: ensure start day is before end day (handle right-to-left selection)
            if (endDateStr < startDateStr) {
                [startDateStr, endDateStr] = [endDateStr, startDateStr];
                // Cursor position on earlier day = bottom of end-day box
                startMin =
                    Math.floor(
                        params.pixelsToMinutesFromMidnight(
                            selectionEndTop.value + selectionEndHeight.value
                        ) / snap
                    ) * snap;
                // Click position on later day = top of start-day box
                endMin =
                    Math.ceil(params.pixelsToMinutesFromMidnight(selectionTop.value) / snap) * snap;
                if (endMin <= 0) endMin = snap;
            }

            startLocal = getLocalizedDayJsFromMinutes(startDateStr, startMin);
            endLocal = getLocalizedDayJsFromMinutes(endDateStr, endMin);
        } else {
            const startDateStr = selectionStartDay;
            const endMinutes = params.pixelsToMinutesFromMidnight(
                selectionTop.value + selectionHeight.value
            );
            let snappedEndMin = Math.ceil(endMinutes / snap) * snap;
            if (snappedEndMin <= snappedStartMin) {
                snappedEndMin = snappedStartMin + snap;
            }
            startLocal = getLocalizedDayJsFromMinutes(startDateStr, snappedStartMin);
            endLocal = getLocalizedDayJsFromMinutes(startDateStr, snappedEndMin);
        }

        params.onSelectionComplete(startLocal.utc(), endLocal.utc());
    }

    // Intermediate days between start and end day (excluding both) that need full-height selection
    const selectionIntermediateDays = computed<Set<string>>(() => {
        if (!selectionDay.value || !selectionEndDay.value) return new Set();
        const allDays = params.viewDays.value.map((d) => d.format('YYYY-MM-DD'));
        const startIdx = allDays.indexOf(selectionDay.value);
        const endIdx = allDays.indexOf(selectionEndDay.value);
        if (startIdx < 0 || endIdx < 0) return new Set();
        const minIdx = Math.min(startIdx, endIdx);
        const maxIdx = Math.max(startIdx, endIdx);
        const days = new Set<string>();
        for (let i = minIdx + 1; i < maxIdx; i++) {
            days.add(allDays[i]!);
        }
        return days;
    });

    function clearSelection() {
        selectionDay.value = null;
        selectionEndDay.value = null;
    }

    onUnmounted(() => {
        document.removeEventListener('pointermove', onSelectionPointerMove);
        document.removeEventListener('pointerup', onSelectionPointerUp);
    });

    return {
        isSelecting,
        selectionDay,
        selectionTop,
        selectionHeight,
        selectionEndDay,
        selectionEndTop,
        selectionEndHeight,
        selectionIntermediateDays,
        onSlotPointerDown,
        clearSelection,
    };
}
