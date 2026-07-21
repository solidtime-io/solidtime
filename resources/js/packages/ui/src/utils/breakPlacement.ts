import type { TimeEntry } from '@/packages/api/src';
import { getDayJsInstance } from '@/packages/ui/src/utils/time';

export interface BreakPlacementHint {
    misplaced: boolean;
    // Closest work end at or before the break start (null if none is known)
    previousWorkEnd: string | null;
    // Closest work start at or after the break end (null if none is known)
    nextWorkStart: string | null;
    gapBeforeSeconds: number | null;
    gapAfterSeconds: number | null;
}

// How far a break may sit from the nearest work entry before it gets a placement hint
export const BREAK_GAP_TOLERANCE_MINUTES = 30;

/**
 * Grouped breaks collapse into a single summary row, so the row needs to know
 * whether any break in the group is misplaced (to show the warning) and which
 * one to navigate to (all grouped entries share the same day). Returns the first
 * misplaced break in the group, or null when none is flagged.
 */
export function findMisplacedBreak(
    entries: TimeEntry[],
    hints: Record<string, BreakPlacementHint | null>
): TimeEntry | null {
    return entries.find((entry) => hints[entry.id]?.misplaced) ?? null;
}

/**
 * A break only means something between work. This computes how far a break
 * sits from the nearest work entry on either side — everything non-work
 * (untracked gaps and other breaks) counts as distance. If either side has
 * more than the tolerated gap (or no work at all), the break gets a hint.
 *
 * Non-blocking by design: placement can not be validated at write time
 * (it depends on entries that may not exist yet), so it is derived at read
 * time from the loaded entries.
 */
export function getBreakPlacementHint(
    breakEntry: TimeEntry,
    allEntries: TimeEntry[]
): BreakPlacementHint | null {
    if (breakEntry.type !== 'break') {
        return null;
    }
    const dayjs = getDayJsInstance();
    const breakStart = dayjs.utc(breakEntry.start);
    const breakEnd = breakEntry.end === null ? dayjs.utc() : dayjs.utc(breakEntry.end);
    const toleranceSeconds = BREAK_GAP_TOLERANCE_MINUTES * 60;

    let previousWorkEnd: ReturnType<typeof dayjs> | null = null;
    let nextWorkStart: ReturnType<typeof dayjs> | null = null;

    for (const entry of allEntries) {
        if (entry.type === 'break' || entry.id === breakEntry.id) {
            continue;
        }
        const entryStart = dayjs.utc(entry.start);
        const entryEnd = entry.end === null ? dayjs.utc() : dayjs.utc(entry.end);

        // Work overlapping the break counts as touching on both sides
        if (entryEnd.isAfter(breakStart) && entryStart.isBefore(breakEnd)) {
            return {
                misplaced: false,
                previousWorkEnd: entryEnd.format(),
                nextWorkStart: entryStart.format(),
                gapBeforeSeconds: 0,
                gapAfterSeconds: 0,
            };
        }
        if (!entryEnd.isAfter(breakStart)) {
            if (previousWorkEnd === null || entryEnd.isAfter(previousWorkEnd)) {
                previousWorkEnd = entryEnd;
            }
        }
        if (!entryStart.isBefore(breakEnd)) {
            if (nextWorkStart === null || entryStart.isBefore(nextWorkStart)) {
                nextWorkStart = entryStart;
            }
        }
    }

    const gapBeforeSeconds =
        previousWorkEnd !== null ? breakStart.diff(previousWorkEnd, 'second') : null;
    const gapAfterSeconds = nextWorkStart !== null ? nextWorkStart.diff(breakEnd, 'second') : null;

    // A running break has no "after" side yet — only judge the before side
    const isRunning = breakEntry.end === null;

    const beforeMisplaced = gapBeforeSeconds === null || gapBeforeSeconds > toleranceSeconds;
    const afterMisplaced =
        !isRunning && (gapAfterSeconds === null || gapAfterSeconds > toleranceSeconds);

    return {
        misplaced: beforeMisplaced || afterMisplaced,
        previousWorkEnd: previousWorkEnd?.format() ?? null,
        nextWorkStart: nextWorkStart?.format() ?? null,
        gapBeforeSeconds,
        gapAfterSeconds,
    };
}
