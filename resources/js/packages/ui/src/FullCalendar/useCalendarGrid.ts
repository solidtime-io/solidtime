import { computed, type Ref } from 'vue';
import type { ComputedRef } from 'vue';
import type { Dayjs } from 'dayjs';
import type { Organization } from '@/packages/api/src';
import type { CalendarSettings } from './calendarSettings';
import { SLOT_HEIGHT } from './calendarTypes';

export function useCalendarGrid(
    calendarSettings: Ref<CalendarSettings>,
    organization: ComputedRef<Organization> | undefined,
    scrollerRef: Ref<HTMLElement | null>,
    rootRef: Ref<HTMLElement | null>
) {
    const slots = computed(() => {
        const s = calendarSettings.value;
        const result: { time: string; isHour: boolean; minutes: number }[] = [];
        const startMin = s.startHour * 60;
        const endMin = s.endHour * 60;

        for (let m = startMin; m < endMin; m += s.slotMinutes) {
            const hours = Math.floor(m / 60);
            const mins = m % 60;
            const time = `${String(hours).padStart(2, '0')}:${String(mins).padStart(2, '0')}:00`;
            const isHour = mins === 0;
            result.push({ time, isHour, minutes: m });
        }
        return result;
    });

    const totalGridHeight = computed(() => slots.value.length * SLOT_HEIGHT);

    function formatSlotLabel(hour: number): string {
        const timeFormat = organization?.value?.time_format || '24-hours';

        if (timeFormat === '12-hours') {
            const period = hour >= 12 ? 'PM' : 'AM';
            const h = hour % 12 || 12;
            return `${h} ${period}`;
        }

        return `${String(hour).padStart(2, '0')}:00`;
    }

    function minutesToPixels(minutes: number): number {
        const s = calendarSettings.value;
        return (minutes / s.slotMinutes) * SLOT_HEIGHT;
    }

    function pixelsToMinutesFromMidnight(px: number): number {
        const s = calendarSettings.value;
        return (px / SLOT_HEIGHT) * s.slotMinutes + s.startHour * 60;
    }

    function timeToMinutesFromMidnight(time: Dayjs): number {
        return time.hour() * 60 + time.minute() + time.second() / 60;
    }

    function snapStartToGrid(time: Dayjs, snapMinutes: number): Dayjs {
        const minutes = time.hour() * 60 + time.minute();
        const snapped = Math.floor(minutes / snapMinutes) * snapMinutes;
        return time.startOf('day').add(snapped, 'minute');
    }

    function snapEndToGrid(time: Dayjs, snapMinutes: number): Dayjs {
        const minutes = time.hour() * 60 + time.minute();
        const snapped = Math.ceil(minutes / snapMinutes) * snapMinutes;
        return time.startOf('day').add(snapped, 'minute');
    }

    function snapToNearestGrid(time: Dayjs, snapMinutes: number): Dayjs {
        const minutes = time.hour() * 60 + time.minute();
        const snapped = Math.round(minutes / snapMinutes) * snapMinutes;
        return time.startOf('day').add(snapped, 'minute');
    }

    function getDayColumnBounds(): { dateStr: string; left: number; right: number }[] {
        if (!rootRef.value) return [];
        const cols = rootRef.value.querySelectorAll<HTMLElement>('.fc-timegrid-col');
        const bounds: { dateStr: string; left: number; right: number }[] = [];
        cols.forEach((col) => {
            const rect = col.getBoundingClientRect();
            bounds.push({
                dateStr: col.dataset.date || '',
                left: rect.left,
                right: rect.right,
            });
        });
        return bounds;
    }

    function getDayFromClientX(clientX: number): string | null {
        const bounds = getDayColumnBounds();
        for (const b of bounds) {
            if (clientX >= b.left && clientX < b.right) {
                return b.dateStr;
            }
        }
        let closest: string | null = null;
        let minDist = Infinity;
        for (const b of bounds) {
            const center = (b.left + b.right) / 2;
            const dist = Math.abs(clientX - center);
            if (dist < minDist) {
                minDist = dist;
                closest = b.dateStr;
            }
        }
        return closest;
    }

    function getScrollerTop(): number {
        if (!scrollerRef.value) return 0;
        return scrollerRef.value.getBoundingClientRect().top + scrollerRef.value.scrollTop;
    }

    function clientYToGridPixels(clientY: number): number {
        if (!scrollerRef.value) return 0;
        const scrollerRect = scrollerRef.value.getBoundingClientRect();
        return clientY - scrollerRect.top + scrollerRef.value.scrollTop;
    }

    return {
        slots,
        totalGridHeight,
        formatSlotLabel,
        minutesToPixels,
        pixelsToMinutesFromMidnight,
        timeToMinutesFromMidnight,
        snapStartToGrid,
        snapEndToGrid,
        snapToNearestGrid,
        getDayColumnBounds,
        getDayFromClientX,
        getScrollerTop,
        clientYToGridPixels,
    };
}
