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
    const dayjs = getDayJsInstance();
    const durationMs = durationSeconds * 1000;
    const gaps = workFreeGapsMs(work);
    const obstaclesMs = obstacles.map(toIntervalMs);

    const blockers = (startMs: number): IntervalMs[] =>
        obstaclesMs.filter((o) => startMs < o.endMs && o.startMs < startMs + durationMs);
    const slot = (startMs: number): Interval => ({
        start: dayjs.utc(startMs).format(),
        end: dayjs.utc(startMs + durationMs).format(),
    });

    // Pass 1: a gap where the centered break keeps both sides within tolerance.
    for (const gap of gaps) {
        const gapMs = gap.endMs - gap.startMs;
        if (gapMs < durationMs || gapMs > durationMs + 2 * toleranceSeconds * 1000) continue;
        const startMs = gap.startMs + Math.floor((gapMs - durationMs) / 2000) * 1000;
        if (blockers(startMs).length > 0) continue;
        return slot(startMs);
    }

    // Pass 2: any gap that can physically hold the break. Start flush after the
    // preceding work and slide right past obstacles until the slot is free.
    for (const gap of gaps) {
        let startMs = gap.startMs;
        while (startMs + durationMs <= gap.endMs) {
            const blocking = blockers(startMs);
            if (blocking.length === 0) return slot(startMs);
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

    for (const gap of workFreeGapsMs(work)) {
        // The anchor must fall inside this gap for it to be "where the break is".
        if (anchorMs < gap.startMs || anchorMs >= gap.endMs) continue;
        if (gap.endMs - gap.startMs < durationMs) return null;

        // Walk the gap's free windows around obstacles and pick the start
        // closest to the anchor, so the break moves as little as possible
        // from where the user left it.
        const blockers = obstacles
            .map(toIntervalMs)
            .filter((o) => o.startMs < gap.endMs && o.endMs > gap.startMs)
            .sort((a, b) => a.startMs - b.startMs);
        let best: number | null = null;
        const consider = (winStartMs: number, winEndMs: number) => {
            if (winEndMs - winStartMs < durationMs) return;
            const candidate = Math.min(Math.max(anchorMs, winStartMs), winEndMs - durationMs);
            if (best === null || Math.abs(candidate - anchorMs) < Math.abs(best - anchorMs)) {
                best = candidate;
            }
        };
        let cursor = gap.startMs;
        for (const blocker of blockers) {
            consider(cursor, blocker.startMs);
            cursor = Math.max(cursor, blocker.endMs);
        }
        consider(cursor, gap.endMs);

        if (best === null) return null;
        return { start: dayjs.utc(best).format(), end: dayjs.utc(best + durationMs).format() };
    }
    return null;
}

// A split must leave a meaningful chunk of work on each side of the break;
// hair-thin fragments would only exist to make a bad placement "fit".
export const MIN_SPLIT_FRAGMENT_SECONDS = 60;

/**
 * Split a single work entry to insert a break. `breakStart` (UTC ISO) lets the
 * caller position it; without one the break is centered. Returns null when the
 * entry is too short to leave at least MIN_SPLIT_FRAGMENT_SECONDS of work on
 * both sides of the break, or when an explicit `breakStart` would not — an
 * out-of-range request is rejected rather than clamped, because silently
 * relocating the break would contradict the time the user picked.
 */
export function planSplitEntry(
    entry: Interval,
    durationSeconds: number,
    breakStart?: string
): SplitPlan | null {
    if (durationSeconds <= 0) return null;
    const dayjs = getDayJsInstance();
    const entryStart = dayjs.utc(entry.start);
    const entryEnd = dayjs.utc(entry.end);
    const total = entryEnd.diff(entryStart, 'second');
    if (total < durationSeconds + 2 * MIN_SPLIT_FRAGMENT_SECONDS) return null;

    const earliest = entryStart.add(MIN_SPLIT_FRAGMENT_SECONDS, 'second');
    const latest = entryEnd.subtract(durationSeconds + MIN_SPLIT_FRAGMENT_SECONDS, 'second');

    let bStart = breakStart
        ? dayjs.utc(breakStart)
        : entryStart.add(Math.floor((total - durationSeconds) / 2), 'second');

    if (breakStart) {
        if (bStart.isBefore(earliest) || bStart.isAfter(latest)) return null;
    } else {
        // Safety net for rounding of the centered position only.
        if (bStart.isBefore(earliest)) bStart = earliest;
        if (bStart.isAfter(latest)) bStart = latest;
    }

    const bEnd = bStart.add(durationSeconds, 'second');
    if (!bStart.isAfter(entryStart) || !bEnd.isBefore(entryEnd)) return null;

    return {
        firstHalf: { start: entryStart.format(), end: bStart.format() },
        breakSlot: { start: bStart.format(), end: bEnd.format() },
        secondHalf: { start: bEnd.format(), end: entryEnd.format() },
    };
}

/**
 * Insert a break at `breakStart`, shifting the surrounding entries only as much
 * as needed to clear the slot. Entries starting before the break form the left
 * block: when it reaches into the slot it is translated earlier so its latest
 * end meets the break start. The rest form the right block: when the slot
 * reaches into it, it is translated later so its earliest start meets the break
 * end. Blocks that already clear the slot are left untouched — existing gaps
 * are preserved, never tightened. Returns null if a required shift would push
 * an entry outside `[dayStart, dayEnd]`.
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
