import { getDayJsInstance } from '@/packages/ui/src/utils/time';
import { BREAK_GAP_TOLERANCE_MINUTES } from '@/packages/ui/src/utils/breakPlacement';

/**
 * Break placement solver for the timesheet.
 *
 * A break only means something sitting between work, ideally within a tolerance
 * of it on both sides (see BREAK_GAP_TOLERANCE_MINUTES). When a break is added
 * to a day we first try to drop it into an existing gap without touching any
 * other entry — preferring a gap where the tolerance holds, but accepting any
 * gap big enough to hold the break. The tolerance is a soft, read-time hint
 * (see getBreakPlacementHint), never a reason to rearrange entries the user
 * tracked deliberately. Only when no gap can physically hold the break does the
 * caller resolve it via a modal that either splits the single work entry or
 * moves the surrounding entries to open a slot — always keeping everything
 * inside the day.
 *
 * All timestamps are UTC ISO strings. Shift arithmetic is done in epoch
 * milliseconds so it is DST-safe (a wall-clock day can be 23h or 25h long).
 */

export const BREAK_GAP_TOLERANCE_SECONDS = BREAK_GAP_TOLERANCE_MINUTES * 60;

export interface Interval {
    start: string;
    end: string;
}

export interface MovableInterval extends Interval {
    id: string;
}

export interface MovePlan {
    breakSlot: Interval;
    // Entries whose start/end changed to make room for the break
    shifted: MovableInterval[];
}

export interface SplitPlan {
    firstHalf: Interval;
    breakSlot: Interval;
    secondHalf: Interval;
    // Entries pushed later to clear the extended second half.
    shifted: MovableInterval[];
}

/**
 * A break that could not be auto-placed within tolerance. The timesheet raises
 * one of these so the page can open the placement modal, where the user either
 * splits the single work entry or shifts entries to open a slot.
 */
export interface BreakPlacementRequest {
    date: string;
    durationSeconds: number;
    dayStart: string;
    dayEnd: string;
    // Work entries on the day (finished, movable), used to split or shift
    workEntries: MovableInterval[];
    // Existing breaks on the day (minus the one being re-placed). They shift
    // along with the surrounding work in move mode so a plan can never land
    // on top of them.
    otherEntries: MovableInterval[];
    defaultBreakStart: string;
    // When re-placing an existing break (an edit), the id to update in place
    replaceBreakId: string | null;
}

/**
 * How a request will be resolved: a single work entry is split around the
 * break; with several, the surrounding entries move to open a slot.
 */
export function placementMode(request: BreakPlacementRequest): 'split' | 'move' {
    return request.workEntries.length === 1 ? 'split' : 'move';
}

/** The minimal shape of a time entry the day-context builder needs. */
export interface DayEntryLike {
    id: string;
    start: string;
    end: string | null;
    type: string;
}

/**
 * Everything the placement flow needs to know about one local day:
 * finished work and break entries fully inside the day (both movable), and the
 * usable day window. Entries that reach across a day boundary belong partly to
 * another day and must not be moved — they shrink `dayStart`/`dayEnd` instead,
 * so no plan can shift anything into them.
 */
export interface DayPlacementContext {
    work: MovableInterval[];
    breaks: MovableInterval[];
    // Immovable blockers: a running entry keeps growing from its start, so it
    // blocks placement from there through the end of the day.
    blocked: Interval[];
    dayStart: string;
    dayEnd: string;
}

export type BreakPlacementDecision =
    | { kind: 'save'; slot: Interval }
    | { kind: 'place-in-free-window' }
    | { kind: 'needs-input'; request: BreakPlacementRequest }
    | { kind: 'reject' };

/**
 * Decide how a requested break should be placed without performing any writes.
 * Keeping this policy pure lets the composable focus on UI state and persistence.
 */
export function decideBreakPlacement({
    date,
    durationSeconds,
    context,
    anchorStart = null,
    replaceBreakId = null,
}: {
    date: string;
    durationSeconds: number;
    context: DayPlacementContext;
    anchorStart?: string | null;
    replaceBreakId?: string | null;
}): BreakPlacementDecision {
    const { work, breaks, blocked, dayStart, dayEnd } = context;
    const obstacles = [...breaks, ...blocked];
    const gap =
        (anchorStart !== null
            ? findValidBreakGapNear(work, durationSeconds, anchorStart, obstacles)
            : null) ?? findValidBreakGap(work, durationSeconds, obstacles);

    if (gap) return { kind: 'save', slot: gap };

    if (work.length === 0) {
        if (anchorStart === null) return { kind: 'place-in-free-window' };
        const slot = findBreakSlotNearInDay(
            dayStart,
            dayEnd,
            durationSeconds,
            anchorStart,
            obstacles
        );
        return slot ? { kind: 'save', slot } : { kind: 'reject' };
    }

    const defaultPlan =
        work.length === 1
            ? planSplitEntry(work[0]!, durationSeconds, undefined, {
                  dayStart,
                  dayEnd,
                  otherEntries: breaks,
              })
            : suggestMovePlan(work, dayStart, dayEnd, durationSeconds, breaks);

    if (!defaultPlan) {
        const adjacent = findAdjacentBreakSlot(work, dayStart, dayEnd, durationSeconds, obstacles);
        return adjacent ? { kind: 'save', slot: adjacent } : { kind: 'reject' };
    }

    return {
        kind: 'needs-input',
        request: {
            date,
            durationSeconds,
            dayStart,
            dayEnd,
            workEntries: work,
            otherEntries: breaks,
            defaultBreakStart: defaultPlan.breakSlot.start,
            replaceBreakId,
        },
    };
}

export function buildDayPlacementContext(
    entries: DayEntryLike[],
    dayStart: string,
    dayEnd: string,
    excludeBreakId: string | null = null
): DayPlacementContext {
    const dayjs = getDayJsInstance();
    const dayStartMs = dayjs.utc(dayStart).valueOf();
    const dayEndMs = dayjs.utc(dayEnd).valueOf();
    let effStartMs = dayStartMs;
    let effEndMs = dayEndMs;
    const work: MovableInterval[] = [];
    const breaks: MovableInterval[] = [];
    const blocked: Interval[] = [];

    for (const entry of entries) {
        if (entry.id === excludeBreakId) continue;
        const startMs = dayjs.utc(entry.start).valueOf();
        // A running entry keeps growing from its start: nothing can be placed
        // at or after it, so it caps the usable window and blocks the rest of
        // the day instead of being movable.
        if (entry.end === null) {
            if (startMs < dayEndMs) {
                if (startMs < effEndMs) effEndMs = startMs;
                blocked.push({ start: entry.start, end: dayEnd });
            }
            continue;
        }
        const endMs = dayjs.utc(entry.end).valueOf();
        if (startMs >= dayEndMs || endMs <= dayStartMs) continue;

        const crossesStart = startMs < dayStartMs;
        const crossesEnd = endMs > dayEndMs;
        if (crossesStart || crossesEnd) {
            if (crossesStart && endMs > effStartMs) effStartMs = endMs;
            if (crossesEnd && startMs < effEndMs) effEndMs = startMs;
            continue;
        }

        const interval = { id: entry.id, start: entry.start, end: entry.end };
        if (entry.type === 'break') {
            breaks.push(interval);
        } else {
            work.push(interval);
        }
    }

    return {
        work: sortByStart(work),
        breaks: sortByStart(breaks),
        blocked: sortByStart(blocked),
        dayStart: dayjs.utc(effStartMs).format(),
        dayEnd: dayjs.utc(effEndMs).format(),
    };
}

function sortByStart<T extends Interval>(intervals: T[]): T[] {
    return [...intervals].sort((a, b) => a.start.localeCompare(b.start));
}

interface IntervalMs {
    startMs: number;
    endMs: number;
}

function toIntervalMs(interval: Interval): IntervalMs {
    const dayjs = getDayJsInstance();
    return {
        startMs: dayjs.utc(interval.start).valueOf(),
        endMs: dayjs.utc(interval.end).valueOf(),
    };
}

function slotAt(startMs: number, durationMs: number): Interval {
    const dayjs = getDayJsInstance();
    return {
        start: dayjs.utc(startMs).format(),
        end: dayjs.utc(startMs + durationMs).format(),
    };
}

/**
 * Merge overlapping/touching work intervals so the space between two
 * consecutive merged intervals is genuinely work-free. Without this, an entry
 * contained in a longer one would fabricate a "gap" that overlaps work.
 */
function mergedWorkMs(work: Interval[]): IntervalMs[] {
    const sorted = work.map(toIntervalMs).sort((a, b) => a.startMs - b.startMs);
    const merged: IntervalMs[] = [];
    for (const current of sorted) {
        const last = merged[merged.length - 1];
        if (last && current.startMs <= last.endMs) {
            last.endMs = Math.max(last.endMs, current.endMs);
        } else {
            merged.push({ ...current });
        }
    }
    return merged;
}

/** Work-free gaps between consecutive merged work intervals, in day order. */
function workFreeGapsMs(work: Interval[]): IntervalMs[] {
    const merged = mergedWorkMs(work);
    const gaps: IntervalMs[] = [];
    for (let i = 0; i < merged.length - 1; i++) {
        gaps.push({ startMs: merged[i]!.endMs, endMs: merged[i + 1]!.startMs });
    }
    return gaps;
}

/**
 * Find a gap between work entries that can hold a break of `durationSeconds`,
 * without touching any other entry.
 *
 * Preference order: first a gap where the centered break stays within
 * `toleranceSeconds` of work on both sides. When no such gap exists, any gap
 * big enough to physically hold the break is accepted — the break is placed
 * flush after the preceding work (sliding past obstacles such as existing
 * breaks) and the rest of the gap is left untouched. Such a break may end up
 * further from work than the tolerance; that is surfaced as a read-time hint
 * (getBreakPlacementHint), not treated as infeasible. Returns null only when
 * no work-free gap can hold the break at all.
 */
export function findValidBreakGap(
    work: Interval[],
    durationSeconds: number,
    obstacles: Interval[] = [],
    toleranceSeconds: number = BREAK_GAP_TOLERANCE_SECONDS
): Interval | null {
    if (durationSeconds <= 0) return null;
    const durationMs = durationSeconds * 1000;
    const gaps = workFreeGapsMs(work);
    const obstaclesMs = obstacles.map(toIntervalMs);

    const blockers = (startMs: number): IntervalMs[] =>
        obstaclesMs.filter((o) => startMs < o.endMs && o.startMs < startMs + durationMs);

    // Pass 1: a gap where the centered break keeps both sides within tolerance.
    for (const gap of gaps) {
        const gapMs = gap.endMs - gap.startMs;
        if (gapMs < durationMs || gapMs > durationMs + 2 * toleranceSeconds * 1000) continue;
        const startMs = gap.startMs + Math.floor((gapMs - durationMs) / 2000) * 1000;
        if (blockers(startMs).length > 0) continue;
        return slotAt(startMs, durationMs);
    }

    // Pass 2: any gap that can physically hold the break. Start flush after the
    // preceding work and slide right past obstacles until the slot is free.
    for (const gap of gaps) {
        let startMs = gap.startMs;
        while (startMs + durationMs <= gap.endMs) {
            const blocking = blockers(startMs);
            if (blocking.length === 0) return slotAt(startMs, durationMs);
            startMs = Math.max(...blocking.map((o) => o.endMs));
        }
    }
    return null;
}

/**
 * Re-place an existing break as close to `anchorStart` as possible, instead of
 * jumping to the first gap (which findValidBreakGap does). Only the gap the
 * anchor currently sits in is considered — the break keeps its position when
 * that gap can still physically hold the new duration, clamped only to stay
 * inside the gap (how far it then sits from work is a soft read-time hint, not
 * a constraint). Obstacles (other breaks) don't evict the break from its gap:
 * it settles into the free window of the gap closest to the anchor, sliding
 * just past whatever is in the way. Returns null only when the anchor sits in
 * no work-free gap or that gap has no free window big enough; the caller then
 * falls back to findValidBreakGap.
 */
export function findValidBreakGapNear(
    work: Interval[],
    durationSeconds: number,
    anchorStart: string,
    obstacles: Interval[] = []
): Interval | null {
    if (durationSeconds <= 0) return null;
    const dayjs = getDayJsInstance();
    const durationMs = durationSeconds * 1000;
    const anchorMs = dayjs.utc(anchorStart).valueOf();
    const obstaclesMs = obstacles.map(toIntervalMs);

    for (const gap of workFreeGapsMs(work)) {
        // The anchor must fall inside this gap for it to be "where the break is".
        if (anchorMs < gap.startMs || anchorMs >= gap.endMs) continue;
        const best = closestFreeStartMs(gap, durationMs, anchorMs, obstaclesMs);
        if (best === null) return null;
        return slotAt(best, durationMs);
    }
    return null;
}

/** Closest obstacle-free start to `anchorMs` that fits inside `range`. */
function closestFreeStartMs(
    range: IntervalMs,
    durationMs: number,
    anchorMs: number,
    obstaclesMs: IntervalMs[]
): number | null {
    if (range.endMs - range.startMs < durationMs) return null;
    const blockers = obstaclesMs
        .filter((o) => o.startMs < range.endMs && o.endMs > range.startMs)
        .sort((a, b) => a.startMs - b.startMs);

    let best: number | null = null;
    const consider = (winStartMs: number, winEndMs: number) => {
        if (winEndMs - winStartMs < durationMs) return;
        const candidate = Math.min(Math.max(anchorMs, winStartMs), winEndMs - durationMs);
        if (best === null || Math.abs(candidate - anchorMs) < Math.abs(best - anchorMs)) {
            best = candidate;
        }
    };
    let cursor = range.startMs;
    for (const blocker of blockers) {
        consider(cursor, blocker.startMs);
        cursor = Math.max(cursor, blocker.endMs);
    }
    consider(cursor, range.endMs);
    return best;
}

/** Keep a workless-day break near its current start without leaving the day. */
export function findBreakSlotNearInDay(
    dayStart: string,
    dayEnd: string,
    durationSeconds: number,
    anchorStart: string,
    obstacles: Interval[] = []
): Interval | null {
    if (durationSeconds <= 0) return null;
    const dayjs = getDayJsInstance();
    const durationMs = durationSeconds * 1000;
    const range = {
        startMs: dayjs.utc(dayStart).valueOf(),
        endMs: dayjs.utc(dayEnd).valueOf(),
    };
    const best = closestFreeStartMs(
        range,
        durationMs,
        dayjs.utc(anchorStart).valueOf(),
        obstacles.map(toIntervalMs)
    );
    if (best === null) return null;
    return slotAt(best, durationMs);
}

export const MIN_SPLIT_FRAGMENT_SECONDS = 60;

export interface SplitOptions {
    // Omitted bounds are unbounded.
    dayStart?: string;
    dayEnd?: string;
    // Existing breaks that may block or move with the split.
    otherEntries?: MovableInterval[];
}

/** Insert a break while preserving work duration and respecting the supplied bounds. */
export function planSplitEntry(
    entry: Interval,
    durationSeconds: number,
    breakStart?: string,
    options: SplitOptions = {}
): SplitPlan | null {
    if (durationSeconds <= 0) return null;
    const dayjs = getDayJsInstance();
    const toMs = (iso: string) => dayjs.utc(iso).valueOf();
    const iso = (ms: number) => dayjs.utc(ms).format();

    const entryStartMs = toMs(entry.start);
    const totalMs = toMs(entry.end) - entryStartMs;
    const minMs = MIN_SPLIT_FRAGMENT_SECONDS * 1000;
    if (totalMs < 2 * minMs) return null;
    const durationMs = durationSeconds * 1000;
    const dayEndMs = options.dayEnd !== undefined ? toMs(options.dayEnd) : Infinity;

    // Pull the block earlier only enough to keep it inside the day.
    const blockStartMs = Math.min(entryStartMs, dayEndMs - totalMs - durationMs);

    let bStartMs = breakStart ? toMs(breakStart) : blockStartMs + Math.floor(totalMs / 2000) * 1000;
    if (breakStart) {
        if (bStartMs < blockStartMs + minMs || bStartMs > blockStartMs + totalMs - minMs) {
            return null;
        }
    } else {
        // Safety net for rounding of the centered position only.
        bStartMs = Math.min(
            Math.max(bStartMs, blockStartMs + minMs),
            blockStartMs + totalMs - minMs
        );
    }
    const bEndMs = bStartMs + durationMs;
    const firstHalfMs = bStartMs - blockStartMs;

    const secondHalfEndMs = bEndMs + totalMs - firstHalfMs;

    // Carry the occupied end through the collision chain; stop at the first gap.
    const others = options.otherEntries ?? [];
    const later = others
        .filter((other) => toMs(other.start) >= bStartMs)
        .sort((a, b) => toMs(a.start) - toMs(b.start));
    const shifted: MovableInterval[] = [];
    let occupiedEndMs = secondHalfEndMs;
    for (const other of later) {
        const otherStartMs = toMs(other.start);
        if (otherStartMs >= occupiedEndMs) break;

        const otherEndMs = toMs(other.end);
        const shiftMs = occupiedEndMs - otherStartMs;
        const shiftedEndMs = otherEndMs + shiftMs;
        if (shiftedEndMs > dayEndMs) return null;
        shifted.push({
            id: other.id,
            start: iso(otherStartMs + shiftMs),
            end: iso(shiftedEndMs),
        });
        occupiedEndMs = shiftedEndMs;
    }

    // Earlier breaks constrain how far the block may move back.
    let earliestStartMs = options.dayStart !== undefined ? toMs(options.dayStart) : -Infinity;
    for (const other of others) {
        const otherEndMs = toMs(other.end);
        if (
            toMs(other.start) < bStartMs &&
            otherEndMs <= bStartMs &&
            otherEndMs > earliestStartMs
        ) {
            earliestStartMs = otherEndMs;
        }
    }
    if (blockStartMs < earliestStartMs) return null;

    return {
        firstHalf: { start: iso(blockStartMs), end: iso(bStartMs) },
        breakSlot: { start: iso(bStartMs), end: iso(bEndMs) },
        secondHalf: { start: iso(bEndMs), end: iso(secondHalfEndMs) },
        shifted,
    };
}

/** Last resort: place the break after the last work entry or before the first. */
export function findAdjacentBreakSlot(
    work: Interval[],
    dayStart: string,
    dayEnd: string,
    durationSeconds: number,
    obstacles: Interval[] = []
): Interval | null {
    if (durationSeconds <= 0 || work.length === 0) return null;
    const dayjs = getDayJsInstance();
    const durationMs = durationSeconds * 1000;
    const dayStartMs = dayjs.utc(dayStart).valueOf();
    const dayEndMs = dayjs.utc(dayEnd).valueOf();

    const merged = mergedWorkMs(work);
    const blockers = [...merged, ...obstacles.map(toIntervalMs)];
    const candidates = [merged[merged.length - 1]!.endMs, merged[0]!.startMs - durationMs];

    for (const startMs of candidates) {
        if (startMs < dayStartMs || startMs + durationMs > dayEndMs) continue;
        const clear = blockers.every(
            (blocker) => blocker.endMs <= startMs || blocker.startMs >= startMs + durationMs
        );
        if (clear) return slotAt(startMs, durationMs);
    }
    return null;
}

/**
 * Insert a break by translating overlapping entries on either side just enough
 * to clear it. Existing gaps remain unchanged. Returns null if a shift would
 * leave the day.
 */
export function planMoveInsert(
    entries: MovableInterval[],
    dayStart: string,
    dayEnd: string,
    breakStart: string,
    durationSeconds: number
): MovePlan | null {
    if (durationSeconds <= 0) return null;
    const dayjs = getDayJsInstance();
    const bStartMs = dayjs.utc(breakStart).valueOf();
    const bEndMs = bStartMs + durationSeconds * 1000;
    const dayStartMs = dayjs.utc(dayStart).valueOf();
    const dayEndMs = dayjs.utc(dayEnd).valueOf();
    if (bStartMs < dayStartMs || bEndMs > dayEndMs) return null;

    const toMs = (iso: string) => dayjs.utc(iso).valueOf();
    const left = entries.filter((e) => toMs(e.start) < bStartMs);
    const right = entries.filter((e) => toMs(e.start) >= bStartMs);

    const shifted: MovableInterval[] = [];
    const translate = (block: MovableInterval[], shiftMs: number) => {
        for (const e of block) {
            shifted.push({
                id: e.id,
                start: dayjs.utc(toMs(e.start) + shiftMs).format(),
                end: dayjs.utc(toMs(e.end) + shiftMs).format(),
            });
        }
    };

    if (left.length > 0) {
        const maxLeftEnd = Math.max(...left.map((e) => toMs(e.end)));
        const minLeftStart = Math.min(...left.map((e) => toMs(e.start)));
        // Only pull earlier when the block overlaps the slot, never later.
        const shift = Math.min(0, bStartMs - maxLeftEnd);
        if (shift !== 0) {
            if (minLeftStart + shift < dayStartMs) return null;
            translate(left, shift);
        }
    }
    if (right.length > 0) {
        const minRightStart = Math.min(...right.map((e) => toMs(e.start)));
        const maxRightEnd = Math.max(...right.map((e) => toMs(e.end)));
        // Only push later when the slot overlaps the block, never earlier.
        const shift = Math.max(0, bEndMs - minRightStart);
        if (shift !== 0) {
            if (maxRightEnd + shift > dayEndMs) return null;
            translate(right, shift);
        }
    }

    return {
        breakSlot: {
            start: dayjs.utc(bStartMs).format(),
            end: dayjs.utc(bEndMs).format(),
        },
        shifted,
    };
}

/**
 * Pick a feasible default break position for the move case — only reached when
 * no work-free gap can hold the break, so opening a slot requires shifting.
 * Only boundaries *between* two consecutive work entries are considered, so the
 * break always ends up flanked by work (a break before the first entry or after
 * the last one would be misplaced). For each boundary it tries pushing the
 * right block later first, then pulling the left block earlier, and returns the
 * first placement whose shifts stay inside the day. `otherEntries` (existing
 * breaks) shift along with the work around them. Null when nothing fits.
 */
export function suggestMovePlan(
    work: MovableInterval[],
    dayStart: string,
    dayEnd: string,
    durationSeconds: number,
    otherEntries: MovableInterval[] = []
): MovePlan | null {
    const dayjs = getDayJsInstance();
    const sorted = sortByStart(work);
    const movable = [...work, ...otherEntries];

    for (let i = 0; i < sorted.length - 1; i++) {
        // Push the right block later: break starts where the earlier entry ends.
        const pushRight = planMoveInsert(
            movable,
            dayStart,
            dayEnd,
            sorted[i]!.end,
            durationSeconds
        );
        if (pushRight) return pushRight;

        // Pull the left block earlier: break ends where the later entry starts.
        const before = dayjs
            .utc(sorted[i + 1]!.start)
            .subtract(durationSeconds, 'second')
            .format();
        const pullLeft = planMoveInsert(movable, dayStart, dayEnd, before, durationSeconds);
        if (pullLeft) return pullLeft;
    }
    return null;
}
