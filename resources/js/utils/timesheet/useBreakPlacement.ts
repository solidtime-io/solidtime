import { ref, type Ref } from 'vue';
import { useMutation, useQueryClient } from '@tanstack/vue-query';
import { api, type CreateTimeEntryBody, type TimeEntry } from '@/packages/api/src';
import { getUserTimezone } from '@/packages/ui/src/utils/settings';
import { getCurrentMembershipId } from '@/utils/useUser';
import type { TimesheetRow } from '@/utils/useTimesheetGrid';
import { useNotificationsStore } from '@/utils/notification';
import { localDayBounds, NoFreeWindowError } from './cellMath';
import {
    buildDayPlacementContext,
    decideBreakPlacement,
    placementMode,
    planMoveInsert,
    planSplitEntry,
    type BreakPlacementRequest,
    type DayPlacementContext,
    type MovableInterval,
} from './breakPlacementMath';

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
    deleteEntry: (id: string) => Promise<void>;
    preventOverlappingTimeEntries?: () => boolean;
}

interface EntryChange {
    original: TimeEntry;
    next: TimeEntry;
}

interface PlacementCommit {
    updates: EntryChange[];
    creates: CreateTimeEntryBody[];
    entriesAdjusted: boolean;
}

export type PlaceBreakResult = 'committed' | 'needs-input';

type UndoOperation = () => Promise<void>;

export class BreakPlacementSagaError extends Error {
    constructor(
        public readonly operationError: unknown,
        public readonly rollbackErrors: unknown[]
    ) {
        super(operationError instanceof Error ? operationError.message : 'Break placement failed');
        this.name = 'BreakPlacementSagaError';
    }
}

/**
 * Break-placement subsystem for the timesheet. Owns the placement-modal request
 * state and everything that positions a break relative to work — auto-placing it
 * into a valid gap when one exists, or asking for input in the split/move modal when the
 * day has to be rearranged.
 */
export function useBreakPlacement(deps: BreakPlacementDeps) {
    const {
        weekDays,
        timeEntries,
        requireOrgId,
        createCell,
        updateEntry,
        deleteEntry,
        preventOverlappingTimeEntries = () => false,
    } = deps;
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

    function breakEntryBody(start: string, end: string, memberId?: string): CreateTimeEntryBody {
        const member = memberId ?? getCurrentMembershipId();
        if (!member) throw new Error('No member context');
        return {
            member_id: member,
            project_id: null,
            task_id: null,
            start,
            end,
            billable: false,
            type: 'break',
            description: null,
            tags: [],
        };
    }

    async function createEntry(body: CreateTimeEntryBody): Promise<string> {
        const response = await api.createTimeEntry(body, {
            params: { organization: requireOrgId() },
        });
        return response.data.id;
    }

    async function createBreakEntry(
        start: string,
        end: string,
        memberId?: string
    ): Promise<string> {
        return createEntry(breakEntryBody(start, end, memberId));
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

    /** Place or resize a break directly when possible, otherwise open the placement modal. */
    async function placeBreak(
        row: TimesheetRow,
        dayIndex: number,
        durationSeconds: number,
        replaceBreakId?: string
    ): Promise<PlaceBreakResult> {
        const date = weekDays.value[dayIndex]!;
        const tz = getUserTimezone();
        const context = dayPlacementContext(date, tz, replaceBreakId);
        const anchorStart = replaceBreakId
            ? (timeEntries.value.find((e) => e.id === replaceBreakId)?.start ?? null)
            : null;
        const decision = decideBreakPlacement({
            date,
            durationSeconds,
            context,
            anchorStart,
            replaceBreakId,
        });

        switch (decision.kind) {
            case 'save':
                await saveBreakEntry(decision.slot.start, decision.slot.end, replaceBreakId);
                return 'committed';
            case 'place-in-free-window':
                await createCell(row, dayIndex, durationSeconds);
                return 'committed';
            case 'needs-input':
                breakPlacementRequest.value = decision.request;
                return 'needs-input';
            case 'reject':
                throw new NoFreeWindowError(date, durationSeconds);
        }
    }

    function dismissBreakPlacement(): void {
        breakPlacementRequest.value = null;
    }

    function shiftChanges(shifted: MovableInterval[]): EntryChange[] {
        return shifted.map((shift) => {
            const original = timeEntries.value.find((e) => e.id === shift.id);
            if (!original) throw new Error('An entry to move no longer exists');
            return {
                original,
                next: { ...original, start: shift.start, end: shift.end },
            };
        });
    }

    function intervalsOverlap(
        left: Pick<TimeEntry, 'start' | 'end'>,
        right: Pick<TimeEntry, 'start' | 'end'>
    ): boolean {
        const leftEndMs = left.end === null ? Infinity : Date.parse(left.end);
        const rightEndMs = right.end === null ? Infinity : Date.parse(right.end);
        return Date.parse(left.start) < rightEndMs && Date.parse(right.start) < leftEndMs;
    }

    /** Order updates so each target is free when overlap prevention is enabled. */
    function nonOverlappingUpdateOrder(
        changes: EntryChange[],
        memberId: string
    ): EntryChange[] | null {
        if (!preventOverlappingTimeEntries()) return changes;

        const current = new Map(
            timeEntries.value
                .filter((entry) => entry.member_id === memberId)
                .map((entry) => [entry.id, entry] as const)
        );
        const pending = [...changes];
        const ordered: EntryChange[] = [];

        while (pending.length > 0) {
            const index = pending.findIndex((change) =>
                [...current.values()].every(
                    (entry) =>
                        entry.id === change.original.id || !intervalsOverlap(change.next, entry)
                )
            );
            if (index === -1) return null;

            const [change] = pending.splice(index, 1);
            ordered.push(change!);
            current.set(change!.original.id, change!.next);
        }
        return ordered;
    }

    async function applyUpdates(changes: EntryChange[], undo: UndoOperation[]): Promise<void> {
        for (const change of changes) {
            await updateEntry(change.next);
            undo.push(() => updateEntry(change.original));
        }
    }

    async function withCompensation<T>(
        operation: (undo: UndoOperation[]) => Promise<T>
    ): Promise<T> {
        const undo: UndoOperation[] = [];
        try {
            return await operation(undo);
        } catch (operationError) {
            const rollbackErrors: unknown[] = [];
            for (const rollback of [...undo].reverse()) {
                try {
                    await rollback();
                } catch (rollbackError) {
                    rollbackErrors.push(rollbackError);
                }
            }
            throw new BreakPlacementSagaError(operationError, rollbackErrors);
        }
    }

    function replacementChange(
        req: BreakPlacementRequest,
        start: string,
        end: string
    ): EntryChange | null {
        if (!req.replaceBreakId) return null;
        const original = timeEntries.value.find((entry) => entry.id === req.replaceBreakId);
        if (!original) throw new Error('Break to update no longer exists');
        return { original, next: { ...original, start, end } };
    }

    function buildPlacementCommit({
        req,
        breakStart,
        durationSeconds,
        memberId,
    }: {
        req: BreakPlacementRequest;
        breakStart: string;
        durationSeconds: number;
        memberId: string;
    }): PlacementCommit {
        if (placementMode(req) === 'split') {
            const original = timeEntries.value.find((e) => e.id === req.workEntries[0]!.id);
            const plan = planSplitEntry(req.workEntries[0]!, durationSeconds, breakStart, {
                dayStart: req.dayStart,
                dayEnd: req.dayEnd,
                otherEntries: req.otherEntries,
            });
            if (!original || !plan) throw new NoFreeWindowError(req.date, durationSeconds);

            const replacement = replacementChange(req, plan.breakSlot.start, plan.breakSlot.end);
            return {
                updates: [
                    {
                        original,
                        next: {
                            ...original,
                            start: plan.firstHalf.start,
                            end: plan.firstHalf.end,
                        },
                    },
                    ...(replacement ? [replacement] : []),
                    ...shiftChanges(plan.shifted),
                ],
                creates: [
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
                    ...(!replacement
                        ? [breakEntryBody(plan.breakSlot.start, plan.breakSlot.end, memberId)]
                        : []),
                ],
                entriesAdjusted: true,
            };
        }

        const plan = planMoveInsert(
            [...req.workEntries, ...req.otherEntries],
            req.dayStart,
            req.dayEnd,
            breakStart,
            durationSeconds
        );
        if (!plan) throw new NoFreeWindowError(req.date, durationSeconds);

        const replacement = replacementChange(req, plan.breakSlot.start, plan.breakSlot.end);
        return {
            updates: [...shiftChanges(plan.shifted), ...(replacement ? [replacement] : [])],
            creates: replacement
                ? []
                : [breakEntryBody(plan.breakSlot.start, plan.breakSlot.end, memberId)],
            entriesAdjusted: plan.shifted.length > 0,
        };
    }

    /** Apply a declarative commit with reverse-order compensation if a later write fails. */
    async function executePlacement({
        req,
        breakStart,
        durationSeconds,
        memberId,
    }: {
        req: BreakPlacementRequest;
        breakStart: string;
        durationSeconds: number;
        memberId: string;
    }): Promise<{ entriesAdjusted: boolean }> {
        const commit = buildPlacementCommit({ req, breakStart, durationSeconds, memberId });
        const updates = nonOverlappingUpdateOrder(commit.updates, memberId);
        if (!updates) throw new NoFreeWindowError(req.date, durationSeconds);

        return withCompensation(async (undo) => {
            await applyUpdates(updates, undo);
            for (const body of commit.creates) {
                const id = await createEntry(body);
                undo.push(() => deleteEntry(id));
            }
            return { entriesAdjusted: commit.entriesAdjusted };
        });
    }

    const { mutateAsync: commitPlacement } = useMutation({
        mutationKey: ['timesheet', 'break-placement'],
        scope: { id: 'timesheet-break-placement' },
        retry: false,
        mutationFn: executePlacement,
        onSuccess: ({ entriesAdjusted }, { req }) => {
            notifications.addNotification(
                'success',
                req.replaceBreakId ? 'Break updated' : 'Break added',
                entriesAdjusted
                    ? 'Your entries were adjusted to make room for the break.'
                    : 'The break was added at the selected time.'
            );
            if (breakPlacementRequest.value === req) breakPlacementRequest.value = null;
        },
        onError: (error, { req }) => {
            const operationError =
                error instanceof BreakPlacementSagaError ? error.operationError : error;
            if (error instanceof BreakPlacementSagaError && error.rollbackErrors.length > 0) {
                notifications.addNotification(
                    'error',
                    'Break placement needs attention',
                    'Some entries could not be restored. The timesheet has been refreshed.'
                );
                if (breakPlacementRequest.value === req) breakPlacementRequest.value = null;
            } else if (operationError instanceof NoFreeWindowError) {
                notifications.addNotification(
                    'error',
                    "This day can't fit the break",
                    'Try a shorter break or a different time.'
                );
            } else {
                notifications.addNotification(
                    'error',
                    req.replaceBreakId ? 'Failed to update break' : 'Failed to add break',
                    'Please try again later.'
                );
            }
        },
        onSettled: () => {
            queryClient.invalidateQueries({ queryKey: ['timeEntries'] });
        },
    });

    async function applyBreakPlacement(breakStart: string, durationSeconds: number): Promise<void> {
        const req = breakPlacementRequest.value;
        if (!req) return;
        const memberId = getCurrentMembershipId();
        if (!memberId) throw new Error('No member context');

        await commitPlacement({ req, breakStart, durationSeconds, memberId });
    }

    return {
        breakPlacementRequest,
        placeBreak,
        dismissBreakPlacement,
        applyBreakPlacement,
    };
}
