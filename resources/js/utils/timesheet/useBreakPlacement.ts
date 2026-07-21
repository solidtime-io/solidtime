import { ref, type Ref } from 'vue';
import { useQueryClient } from '@tanstack/vue-query';
import { api, type TimeEntry } from '@/packages/api/src';
import { getDayJsInstance } from '@/packages/ui/src/utils/time';
import { getUserTimezone } from '@/packages/ui/src/utils/settings';
import { getCurrentMembershipId } from '@/utils/useUser';
import type { TimesheetRow } from '@/utils/useTimesheetGrid';
import { useNotificationsStore } from '@/utils/notification';
import { localDayBounds, NoFreeWindowError } from './cellMath';
import {
    buildDayPlacementContext,
    findValidBreakGap,
    findValidBreakGapNear,
    placementMode,
    planMoveInsert,
    planSplitEntry,
    suggestMovePlan,
    type BreakPlacementRequest,
    type DayPlacementContext,
} from './breakPlacementMath';

/** Signals the caller that a break create/edit is waiting on the placement modal. */
export class BreakPlacementDeferred extends Error {
    constructor() {
        super('Break placement deferred to modal');
        this.name = 'BreakPlacementDeferred';
    }
}

/**
 * Generic entry primitives the break subsystem borrows from the cell-mutation
 * layer. `createCell` drops an entry in the first free window (used when there
 * is no work to anchor a break to); `updateEntry`/`requireOrgId` are the shared
 * API helpers. Keeping them injected avoids a circular dependency and makes the
 * break flow unit-testable in isolation.
 */
export interface BreakPlacementDeps {
    weekDays: Ref<string[]>;
    timeEntries: Ref<TimeEntry[]>;
    requireOrgId: () => string;
    createCell: (
        row: TimesheetRow,
        dayIndex: number,
        totalSeconds: number,
        afterCursor?: string
    ) => Promise<void>;
    updateEntry: (entry: TimeEntry) => Promise<void>;
}

/**
 * Break-placement subsystem for the timesheet. Owns the placement-modal request
 * state and everything that positions a break relative to work — auto-placing it
 * into a valid gap when one exists, or deferring to the split/move modal when the
 * day has to be rearranged.
 */
export function useBreakPlacement(deps: BreakPlacementDeps) {
    const { weekDays, timeEntries, requireOrgId, createCell, updateEntry } = deps;
    const dayjs = getDayJsInstance();
    const queryClient = useQueryClient();
    const notifications = useNotificationsStore();

    // Set when a break needs manual placement; the page shows the modal for it.
    const breakPlacementRequest = ref<BreakPlacementRequest | null>(null);

    /**
     * Movable work/breaks on the target local day plus the usable day window.
     * Entries crossing a day boundary shrink the window instead of being
     * movable (see buildDayPlacementContext) — the padded timesheet fetch
     * makes them visible even at the week edges.
     */
    function dayPlacementContext(
        date: string,
        tz: string,
        excludeBreakId?: string
    ): DayPlacementContext {
        const { dayStart, dayEnd } = localDayBounds(date, tz);
        return buildDayPlacementContext(
            timeEntries.value,
            dayStart.format(),
            dayEnd.format(),
            excludeBreakId ?? null
        );
    }

    async function createBreakEntry(start: string, end: string, memberId?: string): Promise<void> {
        const orgId = requireOrgId();
        const member = memberId ?? getCurrentMembershipId();
        if (!member) throw new Error('No member context');
        await api.createTimeEntry(
            {
                member_id: member,
                project_id: null,
                task_id: null,
                start,
                end,
                billable: false,
                type: 'break',
                description: null,
                tags: [],
            },
            { params: { organization: orgId } }
        );
    }

    async function saveBreakEntry(
        start: string,
        end: string,
        replaceBreakId?: string,
        memberId?: string
    ): Promise<void> {
        if (replaceBreakId) {
            const existing = timeEntries.value.find((entry) => entry.id === replaceBreakId);
            if (!existing) throw new Error('Break to update no longer exists');

            await updateEntry({ ...existing, start, end });
            return;
        }

        await createBreakEntry(start, end, memberId);
    }

    /**
     * Place a break on the day (new, or re-placing an existing one when `replaceBreakId`
     * is given). Prefers a gap that already satisfies the placement tolerance; otherwise
     * raises BreakPlacementDeferred so the page opens the modal. With no work to anchor to,
     * the break is just dropped in / resized in the first free window.
     */
    async function placeBreak(
        row: TimesheetRow,
        dayIndex: number,
        durationSeconds: number,
        replaceBreakId?: string
    ): Promise<void> {
        const date = weekDays.value[dayIndex]!;
        const tz = getUserTimezone();
        const { work, breaks, blocked, dayStart, dayEnd } = dayPlacementContext(
            date,
            tz,
            replaceBreakId
        );
        // Existing breaks block auto-placement into a gap (obstacles), but move
        // along with the surrounding work when a move plan shifts entries.
        // Running entries block everything from their start (never movable).
        const obstacles = [...breaks, ...blocked];

        // On edit, keep the break where it is when its current gap still fits it; only
        // fall back to the first-gap-centered placement when it can't stay put.
        const anchorStart = replaceBreakId
            ? (timeEntries.value.find((e) => e.id === replaceBreakId)?.start ?? null)
            : null;
        const validGap =
            (anchorStart !== null
                ? findValidBreakGapNear(work, durationSeconds, anchorStart, obstacles)
                : null) ?? findValidBreakGap(work, durationSeconds, obstacles);
        if (validGap) {
            await saveBreakEntry(validGap.start, validGap.end, replaceBreakId);
            return;
        }

        if (work.length === 0) {
            // No work to sit between: for an edit, resize the break in place; for a new
            // break, drop it in the first free window. Nothing to align to either way.
            if (replaceBreakId) {
                const existing = timeEntries.value.find((e) => e.id === replaceBreakId);
                if (existing) {
                    const newEnd = dayjs
                        .utc(existing.start)
                        .add(durationSeconds, 'second')
                        .format();
                    await updateEntry({ ...existing, end: newEnd });
                    return;
                }
            }
            await createCell(row, dayIndex, durationSeconds);
            return;
        }

        const mode: 'split' | 'move' = work.length === 1 ? 'split' : 'move';
        const defaultBreakStart =
            mode === 'split'
                ? (planSplitEntry(work[0]!, durationSeconds)?.breakSlot.start ?? null)
                : (suggestMovePlan(work, dayStart, dayEnd, durationSeconds, breaks)?.breakSlot
                      .start ?? null);

        if (!defaultBreakStart) {
            // Even splitting/moving can't open a slot on this day.
            throw new NoFreeWindowError(date, durationSeconds);
        }

        breakPlacementRequest.value = {
            date,
            durationSeconds,
            dayStart,
            dayEnd,
            workEntries: work,
            otherEntries: breaks,
            defaultBreakStart,
            replaceBreakId: replaceBreakId ?? null,
        };
        throw new BreakPlacementDeferred();
    }

    function dismissBreakPlacement(): void {
        breakPlacementRequest.value = null;
    }

    /**
     * Commit a break at `breakStart` by executing the split or move plan. Shifts
     * happen before the break is saved so its target slot is free first.
     */
    async function applyBreakPlacement(breakStart: string, durationSeconds: number): Promise<void> {
        const req = breakPlacementRequest.value;
        if (!req) return;

        // The timesheet is the current member's own, so all created/edited entries stay with them.
        const memberId = getCurrentMembershipId();
        if (!memberId) throw new Error('No member context');

        let entriesAdjusted = true;
        try {
            if (placementMode(req) === 'split') {
                const original = timeEntries.value.find((e) => e.id === req.workEntries[0]!.id);
                const plan = planSplitEntry(req.workEntries[0]!, durationSeconds, breakStart);
                if (!original || !plan) throw new NoFreeWindowError(req.date, durationSeconds);
                // Shrink the original to the first half, then add the second half + break.
                await updateEntry({
                    ...original,
                    start: plan.firstHalf.start,
                    end: plan.firstHalf.end,
                });
                await api.createTimeEntry(
                    {
                        member_id: memberId,
                        project_id: original.project_id,
                        task_id: original.task_id,
                        start: plan.secondHalf.start,
                        end: plan.secondHalf.end,
                        billable: original.billable,
                        type: 'work',
                        description: original.description ?? null,
                        tags: original.tags ?? [],
                    },
                    { params: { organization: requireOrgId() } }
                );
                await saveBreakEntry(
                    plan.breakSlot.start,
                    plan.breakSlot.end,
                    req.replaceBreakId ?? undefined,
                    memberId
                );
            } else {
                const plan = planMoveInsert(
                    [...req.workEntries, ...req.otherEntries],
                    req.dayStart,
                    req.dayEnd,
                    breakStart,
                    durationSeconds
                );
                if (!plan) throw new NoFreeWindowError(req.date, durationSeconds);
                entriesAdjusted = plan.shifted.length > 0;
                // Order the shifts so no intermediate step overlaps (matters when the org
                // prevents overlapping entries): entries moving earlier are updated left-to-right,
                // entries moving later right-to-left, so each one vacates before its neighbour moves.
                const shifts = plan.shifted
                    .map((shift) => ({
                        shift,
                        original: timeEntries.value.find((e) => e.id === shift.id),
                    }))
                    .filter(
                        (x): x is { shift: (typeof plan.shifted)[number]; original: TimeEntry } =>
                            !!x.original
                    );
                const movingEarlier = shifts
                    .filter((x) => x.shift.start < x.original.start)
                    .sort((a, b) => a.original.start.localeCompare(b.original.start));
                const movingLater = shifts
                    .filter((x) => x.shift.start >= x.original.start)
                    .sort((a, b) => b.original.start.localeCompare(a.original.start));
                for (const { shift, original } of [...movingEarlier, ...movingLater]) {
                    await updateEntry({ ...original, start: shift.start, end: shift.end });
                }
                await saveBreakEntry(
                    plan.breakSlot.start,
                    plan.breakSlot.end,
                    req.replaceBreakId ?? undefined,
                    memberId
                );
            }
            notifications.addNotification(
                'success',
                req.replaceBreakId ? 'Break updated' : 'Break added',
                entriesAdjusted
                    ? 'Your entries were adjusted to make room for the break.'
                    : 'The break was added at the selected time.'
            );
        } catch (err) {
            if (err instanceof NoFreeWindowError) {
                notifications.addNotification(
                    'error',
                    "This day can't fit the break",
                    'Try a shorter break or a different time.'
                );
            } else {
                notifications.addNotification(
                    'error',
                    'Failed to add break',
                    'Please try again later.'
                );
            }
            throw err;
        } finally {
            breakPlacementRequest.value = null;
            queryClient.invalidateQueries({ queryKey: ['timeEntries'] });
        }
    }

    return {
        breakPlacementRequest,
        placeBreak,
        dismissBreakPlacement,
        applyBreakPlacement,
    };
}
