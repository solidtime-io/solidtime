import { ref, type Ref, type ComputedRef } from 'vue';
import type { Dayjs } from 'dayjs';
import type { TimeEntry } from '@/packages/api/src';
import { getDayJsInstance } from '../utils/time';
import { getUserTimezone } from '../utils/settings';
import type { CalendarSettings } from './calendarSettings';
import type { CalendarEvent } from './calendarTypes';

export function useContextMenu(params: {
    calendarSettings: Ref<CalendarSettings>;
    calendarEvents: ComputedRef<CalendarEvent[]>;
    pixelsToMinutesFromMidnight: (px: number) => number;
    getDayFromClientX: (clientX: number) => string | null;
    clientYToGridPixels: (clientY: number) => number;
    createTimeEntry: (
        entry: Omit<TimeEntry, 'id' | 'organization_id' | 'user_id'>
    ) => Promise<void>;
    updateTimeEntry: (entry: TimeEntry) => Promise<void>;
    deleteTimeEntry: (id: string) => Promise<void>;
    onEditEvent: (entry: TimeEntry) => void;
    onCreateEvent: (start: Dayjs, end: Dayjs) => void;
    emitRefresh: () => void;
}) {
    const contextMenuTimeEntry = ref<TimeEntry | null>(null);
    const contextMenuCreateTime = ref<{ start: Dayjs; end: Dayjs } | null>(null);

    function getTimeAtClickPosition(event: MouseEvent): { start: Dayjs; end: Dayjs } | null {
        const date = params.getDayFromClientX(event.clientX);
        if (!date) return null;

        const gridY = params.clientYToGridPixels(event.clientY);
        const minutesFromGridStart = params.pixelsToMinutesFromMidnight(gridY);

        const snap = params.calendarSettings.value.snapMinutes;
        const snappedMinutes = Math.floor(minutesFromGridStart / snap) * snap;

        const dayjs = getDayJsInstance();
        const startLocal = dayjs(`${date}T00:00:00`)
            .tz(getUserTimezone(), true)
            .add(snappedMinutes, 'minute');
        const snappedEnd = startLocal.add(snap, 'minute');

        return { start: startLocal.utc(), end: snappedEnd.utc() };
    }

    function handleCalendarContextMenu(event: MouseEvent) {
        const target = event.target as HTMLElement;
        const eventEl = target.closest<HTMLElement>('[data-event-id]');

        if (!eventEl) {
            contextMenuTimeEntry.value = null;
            const timeInfo = getTimeAtClickPosition(event);
            contextMenuCreateTime.value = timeInfo;
            return;
        }

        const eventId = eventEl.getAttribute('data-event-id');
        if (!eventId) return;

        const ev = params.calendarEvents.value.find((e) => e.id === eventId);
        if (!ev) return;

        contextMenuTimeEntry.value = ev.timeEntry;
        contextMenuCreateTime.value = null;
    }

    function handleContextEdit() {
        if (!contextMenuTimeEntry.value || contextMenuTimeEntry.value.end === null) return;
        params.onEditEvent(contextMenuTimeEntry.value);
    }

    async function handleContextDuplicate() {
        if (!contextMenuTimeEntry.value || contextMenuTimeEntry.value.end === null) return;
        const entry = contextMenuTimeEntry.value;
        await params.createTimeEntry({
            start: entry.start,
            end: entry.end,
            billable: entry.billable,
            description: entry.description,
            project_id: entry.project_id,
            task_id: entry.task_id,
            tags: entry.tags,
        });
        params.emitRefresh();
    }

    async function handleContextDelete() {
        if (!contextMenuTimeEntry.value || contextMenuTimeEntry.value.end === null) return;
        await params.deleteTimeEntry(contextMenuTimeEntry.value.id);
        params.emitRefresh();
    }

    async function handleContextSplit() {
        if (!contextMenuTimeEntry.value || contextMenuTimeEntry.value.end === null) return;
        const entry = contextMenuTimeEntry.value;
        if (!entry.end) return;
        const start = getDayJsInstance()(entry.start);
        const end = getDayJsInstance()(entry.end);
        const midpoint = start.add(end.diff(start) / 2, 'millisecond').startOf('minute');

        try {
            await params.updateTimeEntry({ ...entry, end: midpoint.utc().format() });
        } catch {
            // Update failed, don't proceed with create
            params.emitRefresh();
            return;
        }

        try {
            await params.createTimeEntry({
                start: midpoint.utc().format(),
                end: entry.end,
                billable: entry.billable,
                description: entry.description,
                project_id: entry.project_id,
                task_id: entry.task_id,
                tags: entry.tags,
            });
        } catch {
            // Create failed after update succeeded — restore original entry
            try {
                await params.updateTimeEntry({ ...entry });
            } catch {
                // Restoration also failed; refresh will show server state
            }
        }
        params.emitRefresh();
    }

    async function handleContextStop() {
        if (!contextMenuTimeEntry.value || contextMenuTimeEntry.value.end !== null) return;
        const entry = contextMenuTimeEntry.value;
        await params.updateTimeEntry({
            ...entry,
            end: getDayJsInstance()().utc().format(),
        });
        params.emitRefresh();
    }

    async function handleContextDiscard() {
        if (!contextMenuTimeEntry.value || contextMenuTimeEntry.value.end !== null) return;
        await params.deleteTimeEntry(contextMenuTimeEntry.value.id);
        params.emitRefresh();
    }

    function handleContextCreate() {
        if (contextMenuCreateTime.value) {
            params.onCreateEvent(
                contextMenuCreateTime.value.start,
                contextMenuCreateTime.value.end
            );
        } else {
            params.onCreateEvent(
                getDayJsInstance()().utc(),
                getDayJsInstance()().utc().add(1, 'hour')
            );
        }
    }

    return {
        contextMenuTimeEntry,
        contextMenuCreateTime,
        handleCalendarContextMenu,
        handleContextEdit,
        handleContextDuplicate,
        handleContextDelete,
        handleContextSplit,
        handleContextStop,
        handleContextDiscard,
        handleContextCreate,
    };
}
