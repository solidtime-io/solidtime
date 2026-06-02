import { ref, type Ref } from 'vue';
import { useQueryClient } from '@tanstack/vue-query';
import { api, type CreateTimeEntryBody, type TimeEntry } from '@/packages/api/src';
import { formatHumanReadableDuration, getDayJsInstance } from '@/packages/ui/src/utils/time';
import { getUserTimezone } from '@/packages/ui/src/utils/settings';
import { getCurrentMembershipId, getCurrentOrganizationId } from '@/utils/useUser';
import {
    makeRowKey,
    type TimesheetCell,
    type TimesheetRow,
    type TimesheetRowKey,
} from '@/utils/useTimesheetGrid';
import { useNotificationsStore } from '@/utils/notification';
import {
    findFreeWindowOnDay,
    freeGapSecondsAfter,
    NoFreeWindowError,
    workDayStartOn,
    type FreeWindow,
} from './cellMath';

export type CellSaveStatus = 'saving' | 'saved' | 'error';

/** Map key for a cell's save state (row + day). */
export function makeCellStatusKey(rowKey: TimesheetRowKey, dayIndex: number): string {
    return `${rowKey}:${dayIndex}`;
}

/** How long the saved/error state stays visible before fading. */
const SAVED_VISIBLE_MS = 2800;
const ERROR_VISIBLE_MS = 2500;

/**
 * Cell-level edit dispatcher. Picks one of four strategies based on
 * the diff between current and requested totals:
 *
 *   - deleteCell    — new total is 0
 *   - createCell    — empty cell, place in first free window
 *   - extendCell    — diff > 0, push the latest-ending entry forward,
 *                     splitting the remainder into a new entry if a
 *                     collision blocks the path
 *   - shrinkFromEnd — diff < 0, shorten / delete entries from most-
 *                     recent backwards
 *
 * Running entries (end === null) are treated as immutable. Both create
 * and extend can throw NoFreeWindowError when the day is too full.
 *
 * Calls the API directly (not via useTimeEntriesMutations) so a single
 * cell edit fanning into multiple mutations produces exactly one toast
 * and one cache invalidation.
 */
export function useTimesheetCellMutations(
    weekDays: Ref<string[]>,
    timeEntries: Ref<TimeEntry[]>,
    rows: Ref<TimesheetRow[]>,
    removeSlot: (key: TimesheetRowKey) => void
) {
    const dayjs = getDayJsInstance();
    const queryClient = useQueryClient();
    const notifications = useNotificationsStore();

    // Save status + the optimistic value shown while saving, so a saved cell
    // doesn't flicker back to its old total before the refetch lands.
    const cellStatus = ref<Record<string, CellSaveStatus>>({});
    const cellPendingSeconds = ref<Record<string, number>>({});
    const statusClearTimers: Record<string, ReturnType<typeof setTimeout>> = {};

    function clearStatusTimer(key: string): void {
        clearTimeout(statusClearTimers[key]);
        delete statusClearTimers[key];
    }

    function beginSaving(key: string, seconds: number): void {
        clearStatusTimer(key);
        cellPendingSeconds.value[key] = seconds;
        cellStatus.value[key] = 'saving';
    }

    function markSaved(key: string): void {
        clearStatusTimer(key);
        cellStatus.value[key] = 'saved';
        statusClearTimers[key] = setTimeout(() => {
            delete cellStatus.value[key];
            delete cellPendingSeconds.value[key];
            delete statusClearTimers[key];
        }, SAVED_VISIBLE_MS);
    }

    function markError(key: string): void {
        clearStatusTimer(key);
        cellStatus.value[key] = 'error';
        // Drop the optimistic value so the cell shows server truth after refetch.
        delete cellPendingSeconds.value[key];
        statusClearTimers[key] = setTimeout(() => {
            delete cellStatus.value[key];
            delete statusClearTimers[key];
        }, ERROR_VISIBLE_MS);
    }

    async function handleCellUpdate(
        row: TimesheetRow,
        dayIndex: number,
        newTotalSeconds: number
    ): Promise<void> {
        const statusKey = makeCellStatusKey(row.key, dayIndex);
        if (cellStatus.value[statusKey] === 'saving') return;

        const cell = row.cells.get(dayIndex);
        const existingSeconds = cell?.totalSeconds ?? 0;
        if (newTotalSeconds === existingSeconds) return;

        beginSaving(statusKey, newTotalSeconds);

        // Capture row state before the mutation: a row that was empty
        // and shares identity with another slot collapses after the
        // first entry lands, so the entry naturally identity-routes to
        // the surviving slot.
        const wasEmpty = row.totalSeconds === 0;

        try {
            await dispatchCellUpdate(row, dayIndex, newTotalSeconds);

            if (wasEmpty && newTotalSeconds > 0 && hasDuplicateIdentitySlot(row)) {
                removeSlot(row.key);
                notifications.addNotification(
                    'success',
                    'Merged into matching row',
                    'Another row with the same project, task, billable status and tags already exists.'
                );
            }
            markSaved(statusKey);
        } catch (err) {
            markError(statusKey);
            if (err instanceof NoFreeWindowError) {
                const friendlyDuration = formatHumanReadableDuration(
                    err.requiredSeconds,
                    'hours-minutes',
                    'point'
                );
                notifications.addNotification(
                    'error',
                    "This day can't fit any more work",
                    `Couldn't fit ${friendlyDuration} on ${err.date} without overlapping existing entries.`
                );
                return;
            }
            notifications.addNotification(
                'error',
                'Failed to update timesheet',
                'Please try again later.'
            );
        } finally {
            queryClient.invalidateQueries({ queryKey: ['timeEntries'] });
        }
    }

    function hasDuplicateIdentitySlot(row: TimesheetRow): boolean {
        const target = makeRowKey(row.projectId, row.taskId, row.billable, row.tags);
        return rows.value.some(
            (r) =>
                r.key !== row.key &&
                makeRowKey(r.projectId, r.taskId, r.billable, r.tags) === target
        );
    }

    async function dispatchCellUpdate(
        row: TimesheetRow,
        dayIndex: number,
        newTotalSeconds: number
    ): Promise<void> {
        const cell = row.cells.get(dayIndex);
        const existingSeconds = cell?.totalSeconds ?? 0;
        const diff = newTotalSeconds - existingSeconds;

        if (newTotalSeconds === 0 && cell) {
            await deleteCell(cell);
            return;
        }

        if (!cell || existingSeconds === 0) {
            await createCell(row, dayIndex, newTotalSeconds);
            return;
        }

        if (diff > 0) {
            await extendCell(row, dayIndex, cell, diff);
            return;
        }

        await shrinkFromEnd(cell, -diff);
    }

    async function deleteCell(cell: TimesheetCell): Promise<void> {
        const orgId = requireOrgId();
        await api.deleteTimeEntries(undefined, {
            queries: { ids: cell.entries.map((e) => e.id) },
            params: { organization: orgId },
        });
    }

    /**
     * Place a new entry on the cell's day. Without `afterCursor`, prefers
     * 09:00 local with a fall-back to start-of-day. With `afterCursor`,
     * places strictly at-or-after that timestamp (used by extendCell to
     * skip past a just-written extension that timeEntries.value doesn't
     * yet reflect). Throws NoFreeWindowError if nothing fits.
     */
    async function createCell(
        row: TimesheetRow,
        dayIndex: number,
        totalSeconds: number,
        afterCursor?: string
    ): Promise<void> {
        const date = weekDays.value[dayIndex]!;
        const tz = getUserTimezone();

        let window: FreeWindow | null;
        if (afterCursor) {
            window = findFreeWindowOnDay(timeEntries.value, date, totalSeconds, tz, afterCursor);
        } else {
            window =
                findFreeWindowOnDay(
                    timeEntries.value,
                    date,
                    totalSeconds,
                    tz,
                    workDayStartOn(date, tz)
                ) ?? findFreeWindowOnDay(timeEntries.value, date, totalSeconds, tz);
        }

        if (!window) throw new NoFreeWindowError(date, totalSeconds);

        const orgId = requireOrgId();
        const memberId = getCurrentMembershipId();
        if (!memberId) throw new Error('No member context');

        const body: CreateTimeEntryBody = {
            member_id: memberId,
            project_id: row.projectId,
            task_id: row.taskId,
            start: window.start,
            end: window.end,
            billable: row.billable,
            description: null,
            tags: row.tags,
        };
        await api.createTimeEntry(body, { params: { organization: orgId } });
    }

    /**
     * Push the latest-ending entry's end forward by `addSeconds`, and if
     * a collision blocks the path before that's exhausted, place the
     * remainder as a fresh entry in the next free window on the day.
     */
    async function extendCell(
        row: TimesheetRow,
        dayIndex: number,
        cell: TimesheetCell,
        addSeconds: number
    ): Promise<void> {
        const date = weekDays.value[dayIndex]!;
        const tz = getUserTimezone();

        // Latest END (not latest start) — extending a nested inner entry
        // would leave the outer one as the true tail.
        const candidate = pickLatestEndedEntry(cell);

        // Running timer (or no ended entry): can't extend, place it all
        // as a new entry instead.
        if (!candidate || !candidate.end) {
            await createCell(row, dayIndex, addSeconds);
            return;
        }

        const gap = freeGapSecondsAfter(timeEntries.value, date, tz, candidate.end);
        const extendBy = Math.min(addSeconds, gap);
        const remainder = addSeconds - extendBy;
        const projectedNewEnd = dayjs.utc(candidate.end).add(extendBy, 'second').format();

        // Pre-flight: if there's a remainder, make sure it'll fit in a
        // window after `projectedNewEnd` BEFORE we issue the extend PATCH.
        // Otherwise a successful extend followed by a no-fit createCell
        // would leave the entry persistently lengthened on the server
        // while the user sees a "can't fit" error.
        if (remainder > 0) {
            const fit = findFreeWindowOnDay(
                timeEntries.value,
                date,
                remainder,
                tz,
                projectedNewEnd
            );
            if (!fit) throw new NoFreeWindowError(date, addSeconds);
        }

        if (extendBy > 0) {
            await updateEntry({ ...candidate, end: projectedNewEnd });
        }
        if (remainder <= 0) return;

        // timeEntries.value is stale here (still shows candidate's old
        // end). Force the placement search past projectedNewEnd so it
        // can't propose a window that overlaps the just-extended candidate.
        await createCell(row, dayIndex, remainder, projectedNewEnd);
    }

    async function shrinkFromEnd(cell: TimesheetCell, removeSeconds: number): Promise<void> {
        let toRemove = removeSeconds;

        // Shrink doesn't introduce overlaps, so latest-START is fine here.
        const sortedEntries = [...cell.entries].sort((a, b) => b.start.localeCompare(a.start));

        for (const entry of sortedEntries) {
            if (toRemove <= 0) break;
            if (!entry.end) continue; // running entries are immutable

            const entryDuration = entry.duration ?? 0;

            if (entryDuration <= toRemove) {
                await deleteEntry(entry.id);
                toRemove -= entryDuration;
            } else {
                const newEnd = dayjs
                    .utc(entry.start)
                    .add(entryDuration - toRemove, 'second')
                    .format();
                await updateEntry({ ...entry, end: newEnd });
                toRemove = 0;
            }
        }
    }

    // ── api helpers ───────────────────────────────────────────────

    function requireOrgId(): string {
        const id = getCurrentOrganizationId();
        if (!id) throw new Error('No organization context');
        return id;
    }

    async function updateEntry(entry: TimeEntry) {
        const orgId = requireOrgId();
        await api.updateTimeEntry(entry, {
            params: { organization: orgId, timeEntry: entry.id },
        });
    }

    async function deleteEntry(id: string) {
        const orgId = requireOrgId();
        await api.deleteTimeEntry(undefined, {
            params: { organization: orgId, timeEntry: id },
        });
    }

    function pickLatestEndedEntry(cell: TimesheetCell): TimeEntry | null {
        let best: TimeEntry | null = null;
        for (const entry of cell.entries) {
            if (!best) {
                best = entry;
                continue;
            }
            // Running entries are treated as "infinite" — they win.
            if (!entry.end) {
                best = entry;
                continue;
            }
            if (best.end && entry.end > best.end) {
                best = entry;
            }
        }
        return best;
    }

    return { handleCellUpdate, cellStatus, cellPendingSeconds };
}
