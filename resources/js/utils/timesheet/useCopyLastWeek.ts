import { ref, type Ref } from 'vue';
import type { Dayjs } from 'dayjs';
import axios from 'axios';
import { useQueryClient } from '@tanstack/vue-query';
import {
    api,
    type CreateTimeEntryBody,
    type TimeEntry,
    type TimeEntryResponse,
} from '@/packages/api/src';
import {
    getDayJsInstance,
    getLocalizedDateFromTimestamp,
    localDateToUtc,
} from '@/packages/ui/src/utils/time';
import { getUserTimezone } from '@/packages/ui/src/utils/settings';
import { fetchTimesheetEntries } from '@/utils/useTimesheetQuery';
import { getCurrentMembershipId, getCurrentOrganizationId } from '@/utils/useUser';
import { makeRowKey, type TimesheetRow } from '@/utils/useTimesheetGrid';
import { useNotificationsStore } from '@/utils/notification';
import { findFreeWindowOnDay, workDayStartOn } from './cellMath';

/**
 * Implements both variants of "Copy last week":
 *
 *   - `copyLastWeekRows()`       — only add rows for each distinct
 *                                   (project, task) pair from last week
 *                                   that doesn't already exist
 *   - `copyLastWeekWithTime()`   — same, but also duplicates each
 *                                   previous-week entry into the same
 *                                   day-of-week in the current week,
 *                                   stacking copies after any existing
 *                                   work on that day
 */
export function useCopyLastWeek(
    weekStart: Ref<Dayjs>,
    weekDays: Ref<string[]>,
    rows: Ref<TimesheetRow[]>,
    timeEntries: Ref<TimeEntry[]>,
    addSlot: (
        projectId: string | null,
        taskId: string | null,
        billable: boolean,
        tags: string[]
    ) => string
) {
    const dayjs = getDayJsInstance();
    const queryClient = useQueryClient();
    const { addNotification } = useNotificationsStore();

    const isCopyingLastWeek = ref(false);

    async function fetchLastWeekEntries(): Promise<TimeEntryResponse | null> {
        const prevStart = weekStart.value.subtract(7, 'day');
        const prevEnd = weekStart.value;

        const orgId = getCurrentOrganizationId();
        const memberId = getCurrentMembershipId();
        if (!orgId) return null;

        return await fetchTimesheetEntries(
            orgId,
            memberId,
            localDateToUtc(prevStart),
            localDateToUtc(prevEnd)
        );
    }

    /**
     * For every entry in `prevEntries`, if the current week doesn't
     * already have a row for that (project, task) combination, add one.
     * Deduplicates so each combination is added at most once.
     */
    function addMissingRowsFromPreviousWeek(prevEntries: TimeEntry[]): void {
        const existingIdentities = new Set(
            rows.value.map((r) => makeRowKey(r.projectId, r.taskId, r.billable, r.tags))
        );
        const addedIdentities = new Set<string>();

        for (const entry of prevEntries) {
            const tags = entry.tags ?? [];
            const identity = makeRowKey(entry.project_id, entry.task_id, entry.billable, tags);
            if (!existingIdentities.has(identity) && !addedIdentities.has(identity)) {
                addedIdentities.add(identity);
                addSlot(entry.project_id, entry.task_id, entry.billable, tags);
            }
        }
    }

    async function copyLastWeekRows(): Promise<void> {
        isCopyingLastWeek.value = true;
        try {
            const prev = await fetchLastWeekEntries();
            if (!prev) return;
            addMissingRowsFromPreviousWeek(prev.data);
        } finally {
            isCopyingLastWeek.value = false;
        }
    }

    async function copyLastWeekWithTime(): Promise<void> {
        isCopyingLastWeek.value = true;
        try {
            const prev = await fetchLastWeekEntries();
            if (!prev) return;

            const orgId = getCurrentOrganizationId();
            const memberId = getCurrentMembershipId();
            if (!orgId || !memberId) return;

            const tz = getUserTimezone();

            addMissingRowsFromPreviousWeek(prev.data);

            const prevWeekStart = weekStart.value.subtract(7, 'day');

            // Working copy of the current week's entries; placed copies
            // are appended so subsequent placement queries see them as
            // obstacles (timeEntries.value isn't refreshed until the
            // queryClient.invalidate at the end of the loop).
            const workingEntries: TimeEntry[] = [...timeEntries.value];

            let attempted = 0;
            let succeeded = 0;
            let overlapFailures = 0;
            let otherFailures = 0;

            for (const entry of prev.data) {
                if (!entry.end || !entry.duration) continue;

                // Map previous-week date → same day-of-week in current week.
                const entryDate = getLocalizedDateFromTimestamp(entry.start);
                const dayOffset = dayjs(entryDate).diff(prevWeekStart, 'day');
                const newDate = weekDays.value[dayOffset];
                if (!newDate) continue;

                // Try the source's wall-clock time on the target day first
                // (preserves "Monday 14:00 meeting" → "Monday 14:00 meeting"
                // when the slot is free); fall back to 09:00, then to
                // anywhere on the day.
                const sourceTimeOfDay = dayjs.utc(entry.start).tz(tz).format('HH:mm:ss');
                const sourceStartOnTarget = dayjs
                    .tz(`${newDate} ${sourceTimeOfDay}`, tz)
                    .utc()
                    .format();

                const window =
                    findFreeWindowOnDay(
                        workingEntries,
                        newDate,
                        entry.duration,
                        tz,
                        sourceStartOnTarget
                    ) ??
                    findFreeWindowOnDay(
                        workingEntries,
                        newDate,
                        entry.duration,
                        tz,
                        workDayStartOn(newDate, tz)
                    ) ??
                    findFreeWindowOnDay(workingEntries, newDate, entry.duration, tz);

                if (!window) {
                    attempted++;
                    otherFailures++;
                    continue;
                }

                const body: CreateTimeEntryBody = {
                    member_id: memberId,
                    project_id: entry.project_id,
                    task_id: entry.task_id,
                    start: window.start,
                    end: window.end,
                    billable: entry.billable,
                    description: entry.description ?? null,
                    tags: entry.tags ?? [],
                };

                attempted++;
                try {
                    await api.createTimeEntry(body, { params: { organization: orgId } });
                    succeeded++;
                    workingEntries.push({
                        start: window.start,
                        end: window.end,
                    } as TimeEntry);
                } catch (error) {
                    if (
                        axios.isAxiosError(error) &&
                        error.response?.data?.key === 'overlapping_time_entry'
                    ) {
                        overlapFailures++;
                    } else {
                        otherFailures++;
                    }
                }
            }

            queryClient.invalidateQueries({ queryKey: ['timeEntries'] });

            if (attempted === 0) return;

            if (succeeded === attempted) {
                addNotification(
                    'success',
                    `Copied ${succeeded} ${succeeded === 1 ? 'entry' : 'entries'} from last week`
                );
            } else if (succeeded > 0) {
                const skipped = overlapFailures + otherFailures;
                const detail =
                    overlapFailures > 0 && otherFailures === 0
                        ? `${overlapFailures} overlapping`
                        : otherFailures > 0 && overlapFailures === 0
                          ? `${otherFailures} failed`
                          : `${skipped} skipped`;
                addNotification(
                    'error',
                    `Copied ${succeeded} of ${attempted} entries from last week`,
                    `${detail}.`
                );
            } else {
                addNotification(
                    'error',
                    'Failed to copy entries from last week',
                    overlapFailures > 0 && otherFailures === 0
                        ? 'All entries would overlap with existing time entries.'
                        : 'Please try again later.'
                );
            }
        } finally {
            isCopyingLastWeek.value = false;
        }
    }

    return {
        isCopyingLastWeek,
        copyLastWeekRows,
        copyLastWeekWithTime,
    };
}
