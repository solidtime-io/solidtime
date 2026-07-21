import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import { describe, expect, it } from 'vitest';
import {
    BREAK_GAP_TOLERANCE_SECONDS,
    buildDayPlacementContext,
    findValidBreakGap,
    findValidBreakGapNear,
    planMoveInsert,
    planSplitEntry,
    suggestMovePlan,
    type MovableInterval,
} from './breakPlacementMath';

dayjs.extend(utc);

const HALF_HOUR = 1800;
const HOUR = 3600;
const DAY = '2026-07-14';
const dayStart = `${DAY}T00:00:00Z`;
const dayEnd = `${DAY}T24:00:00Z`;

function iv(startH: number, endH: number) {
    const h = (n: number) => {
        const totalMin = Math.round(n * 60);
        const hh = Math.floor(totalMin / 60);
        const mm = totalMin % 60;
        return `${DAY}T${String(hh).padStart(2, '0')}:${String(mm).padStart(2, '0')}:00Z`;
    };
    return { start: h(startH), end: h(endH) };
}

describe('findValidBreakGap', () => {
    it('centers the break in a gap that fits within tolerance', () => {
        // 09-12 and 13-17 → 1h gap, 30m break → centered at 12:15-12:45
        const gap = findValidBreakGap([iv(9, 12), iv(13, 17)], HALF_HOUR);
        expect(gap).toEqual({ start: `${DAY}T12:15:00Z`, end: `${DAY}T12:45:00Z` });
    });

    it('rejects a gap that is too small for the break', () => {
        // 09-12 and 12:15-17 → 15m gap, 30m break does not fit
        expect(findValidBreakGap([iv(9, 12), iv(12.25, 17)], HALF_HOUR)).toBeNull();
    });

    it('places the break flush after work in an oversized gap instead of rejecting it', () => {
        // 09-12 and 14-17 → 2h gap. No placement keeps both sides within tolerance,
        // but the gap easily holds the break — place it flush after the first entry
        // and leave the gap otherwise untouched (distance to work is only a soft hint).
        expect(findValidBreakGap([iv(9, 12), iv(14, 17)], HALF_HOUR)).toEqual({
            start: `${DAY}T12:00:00Z`,
            end: `${DAY}T12:30:00Z`,
        });
    });

    it('prefers a within-tolerance gap over an earlier oversized gap', () => {
        // 09-10, 13-14, 15-16: the first gap (3h) is oversized, the second (1h) is
        // valid → center in the second instead of going flush-left in the first.
        expect(findValidBreakGap([iv(9, 10), iv(13, 14), iv(15, 16)], HALF_HOUR)).toEqual({
            start: `${DAY}T14:15:00Z`,
            end: `${DAY}T14:45:00Z`,
        });
    });

    it('slides past an obstacle when placing into an oversized gap', () => {
        // 09-12 and 16-17 with an existing break flush at 12:00 → the new break
        // lands right after that break.
        expect(findValidBreakGap([iv(9, 12), iv(16, 17)], HALF_HOUR, [iv(12, 12.75)])).toEqual({
            start: `${DAY}T12:45:00Z`,
            end: `${DAY}T13:15:00Z`,
        });
    });

    it('does not fabricate a gap from an entry contained in a longer one', () => {
        // 10-11 sits inside 09-17; the only real gap is 17:00-18:00 → centered there.
        expect(findValidBreakGap([iv(9, 17), iv(10, 11), iv(18, 19)], HALF_HOUR)).toEqual({
            start: `${DAY}T17:15:00Z`,
            end: `${DAY}T17:45:00Z`,
        });
    });

    it('accepts a gap exactly at duration + 2*tolerance', () => {
        const gapEnd = 12 + (HALF_HOUR + 2 * BREAK_GAP_TOLERANCE_SECONDS) / HOUR;
        const gap = findValidBreakGap([iv(9, 12), iv(gapEnd, gapEnd + 1)], HALF_HOUR);
        expect(gap).not.toBeNull();
    });

    it('returns null when there is only one work entry', () => {
        expect(findValidBreakGap([iv(9, 17)], HALF_HOUR)).toBeNull();
    });

    it('skips a gap already occupied by another break', () => {
        // The only valid gap (12:15–12:45) is taken by an existing break → no auto placement
        expect(
            findValidBreakGap([iv(9, 12), iv(13, 17)], HALF_HOUR, [
                { start: `${DAY}T12:15:00Z`, end: `${DAY}T12:45:00Z` },
            ])
        ).toBeNull();
    });

    it('ignores obstacles that fall outside the chosen gap', () => {
        expect(findValidBreakGap([iv(9, 12), iv(13, 17)], HALF_HOUR, [iv(20, 21)])).toEqual({
            start: `${DAY}T12:15:00Z`,
            end: `${DAY}T12:45:00Z`,
        });
    });
});

describe('planSplitEntry', () => {
    it('splits a single entry and centers the break', () => {
        const plan = planSplitEntry(iv(9, 17), HALF_HOUR);
        expect(plan).not.toBeNull();
        expect(plan!.firstHalf.start).toBe(`${DAY}T09:00:00Z`);
        expect(plan!.breakSlot.start).toBe(plan!.firstHalf.end);
        expect(plan!.secondHalf.start).toBe(plan!.breakSlot.end);
        expect(plan!.secondHalf.end).toBe(`${DAY}T17:00:00Z`);
        // break is 30m and centered → 12:45-13:15
        expect(plan!.breakSlot).toEqual({ start: `${DAY}T12:45:00Z`, end: `${DAY}T13:15:00Z` });
    });

    it('honors an explicit break start', () => {
        const plan = planSplitEntry(iv(9, 17), HALF_HOUR, `${DAY}T10:00:00Z`);
        expect(plan!.firstHalf).toEqual({ start: `${DAY}T09:00:00Z`, end: `${DAY}T10:00:00Z` });
        expect(plan!.secondHalf.start).toBe(`${DAY}T10:30:00Z`);
    });

    it('returns null when the entry is too short to leave work on both sides', () => {
        expect(planSplitEntry(iv(9, 9.25), HALF_HOUR)).toBeNull();
    });

    it('rejects an explicit break start before the entry instead of clamping it', () => {
        // 07:00 lies before the 09:00-17:00 entry — relocating it silently would
        // leave a hair-thin first fragment at a time the user never picked.
        expect(planSplitEntry(iv(9, 17), HALF_HOUR, `${DAY}T07:00:00Z`)).toBeNull();
    });

    it('rejects an explicit break start whose break would reach past the entry end', () => {
        expect(planSplitEntry(iv(9, 17), HALF_HOUR, `${DAY}T16:45:00Z`)).toBeNull();
    });

    it('rejects an explicit break start that leaves less than the minimum fragment', () => {
        // 09:00:30 would leave only 30s of work before the break.
        expect(planSplitEntry(iv(9, 17), HALF_HOUR, `${DAY}T09:00:30Z`)).toBeNull();
    });

    it('accepts an explicit break start leaving exactly the minimum fragment on each side', () => {
        // 09:01 leaves 60s before; on a 09:00-09:32 entry a 30m break also leaves 60s after.
        const plan = planSplitEntry(iv(9, 9 + 32 / 60), HALF_HOUR, `${DAY}T09:01:00Z`);
        expect(plan).not.toBeNull();
        expect(plan!.firstHalf).toEqual({ start: `${DAY}T09:00:00Z`, end: `${DAY}T09:01:00Z` });
        expect(plan!.secondHalf).toEqual({ start: `${DAY}T09:31:00Z`, end: `${DAY}T09:32:00Z` });
    });

    it('returns null when the entry cannot hold the break plus a minimum fragment per side', () => {
        // 31 minutes of work cannot hold a 30m break with 60s of work on each side.
        expect(planSplitEntry(iv(9, 9 + 31 / 60), HALF_HOUR)).toBeNull();
    });
});

describe('planMoveInsert', () => {
    const movable = (id: string, startH: number, endH: number): MovableInterval => ({
        id,
        ...iv(startH, endH),
    });

    it('pushes the right block later to open a slot for the break', () => {
        // Back-to-back 09-12 and 12-17. Insert 30m break at 12:00.
        const plan = planMoveInsert(
            [movable('a', 9, 12), movable('b', 12, 17)],
            dayStart,
            dayEnd,
            `${DAY}T12:00:00Z`,
            HALF_HOUR
        );
        expect(plan).not.toBeNull();
        expect(plan!.breakSlot).toEqual({ start: `${DAY}T12:00:00Z`, end: `${DAY}T12:30:00Z` });
        // 'a' untouched (not in shifted), 'b' shifted +30m
        expect(plan!.shifted).toEqual([
            { id: 'b', start: `${DAY}T12:30:00Z`, end: `${DAY}T17:30:00Z` },
        ]);
    });

    it('leaves an oversized gap alone instead of pulling the right block flush', () => {
        // 09-12 and 15-17 (3h gap). Break flush after first at 12:00 fits in the gap
        // → nothing moves; the user's gap is preserved.
        const plan = planMoveInsert(
            [movable('a', 9, 12), movable('b', 15, 17)],
            dayStart,
            dayEnd,
            `${DAY}T12:00:00Z`,
            HALF_HOUR
        );
        expect(plan!.breakSlot).toEqual({ start: `${DAY}T12:00:00Z`, end: `${DAY}T12:30:00Z` });
        expect(plan!.shifted).toEqual([]);
    });

    it('does not drag entries flush when the break sits mid-gap', () => {
        // Break at 13:00 in the middle of the 12:00-15:00 gap → neither side moves.
        const plan = planMoveInsert(
            [movable('a', 9, 12), movable('b', 15, 17)],
            dayStart,
            dayEnd,
            `${DAY}T13:00:00Z`,
            HALF_HOUR
        );
        expect(plan!.shifted).toEqual([]);
    });

    it('shifts each side only as much as needed to clear the slot', () => {
        // Break 14:45-15:15 overlaps only the start of 'b' → 'b' pushed 15m later,
        // 'a' untouched.
        const plan = planMoveInsert(
            [movable('a', 9, 12), movable('b', 15, 17)],
            dayStart,
            dayEnd,
            `${DAY}T14:45:00Z`,
            HALF_HOUR
        );
        expect(plan!.shifted).toEqual([
            { id: 'b', start: `${DAY}T15:15:00Z`, end: `${DAY}T17:15:00Z` },
        ]);
    });

    it('pulls the left block earlier only when it overlaps the slot', () => {
        // Break 11:45-12:15 overlaps the end of 'a' → 'a' pulled 15m earlier;
        // 'b' (15-17) already clears the slot and stays put.
        const plan = planMoveInsert(
            [movable('a', 9, 12), movable('b', 15, 17)],
            dayStart,
            dayEnd,
            `${DAY}T11:45:00Z`,
            HALF_HOUR
        );
        expect(plan!.shifted).toEqual([
            { id: 'a', start: `${DAY}T08:45:00Z`, end: `${DAY}T11:45:00Z` },
        ]);
    });

    it('shifts the left block earlier when the right block cannot move within the day', () => {
        // Right entry ends at 23:50; pushing it later would cross midnight, so the
        // left block must move earlier instead.
        const plan = planMoveInsert(
            [movable('a', 9, 12), movable('b', 12, 23 + 50 / 60)],
            dayStart,
            dayEnd,
            `${DAY}T12:00:00Z`,
            HALF_HOUR
        );
        // Not feasible by pushing right; solver returns null (caller lets the user pick another spot)
        expect(plan).toBeNull();
    });

    it('returns null when the break itself would fall outside the day', () => {
        expect(
            planMoveInsert([movable('a', 9, 12)], dayStart, dayEnd, `${DAY}T23:50:00Z`, HALF_HOUR)
        ).toBeNull();
    });

    it('places a break between entries without shifting when they already have exactly the gap', () => {
        const plan = planMoveInsert(
            [movable('a', 9, 12), movable('b', 12.5, 17)],
            dayStart,
            dayEnd,
            `${DAY}T12:00:00Z`,
            HALF_HOUR
        );
        // gap is exactly 30m → 'b' already starts at break end, nothing to shift
        expect(plan!.breakSlot).toEqual({ start: `${DAY}T12:00:00Z`, end: `${DAY}T12:30:00Z` });
        expect(plan!.shifted).toEqual([]);
    });
});

describe('suggestMovePlan', () => {
    const movable = (id: string, startH: number, endH: number): MovableInterval => ({
        id,
        ...iv(startH, endH),
    });

    it('finds a flush-after placement for back-to-back entries', () => {
        const plan = suggestMovePlan(
            [movable('a', 9, 12), movable('b', 12, 17)],
            dayStart,
            dayEnd,
            HALF_HOUR
        );
        expect(plan!.breakSlot).toEqual({ start: `${DAY}T12:00:00Z`, end: `${DAY}T12:30:00Z` });
    });

    it('falls back to a flush-before placement when the day is nearly full at the end', () => {
        // 09-12 and 12-23:50: pushing right past midnight is impossible, so the break
        // is placed just before the second entry, pulling the first entry earlier.
        const plan = suggestMovePlan(
            [movable('a', 9, 12), movable('b', 12, 23 + 50 / 60)],
            dayStart,
            dayEnd,
            HALF_HOUR
        );
        expect(plan).not.toBeNull();
        expect(plan!.breakSlot).toEqual({ start: `${DAY}T11:30:00Z`, end: `${DAY}T12:00:00Z` });
        // first entry pulled 30m earlier, second untouched
        expect(plan!.shifted.find((s) => s.id === 'a')).toEqual({
            id: 'a',
            start: `${DAY}T08:30:00Z`,
            end: `${DAY}T11:30:00Z`,
        });
    });

    it('returns null when the day is completely full', () => {
        const plan = suggestMovePlan([movable('a', 0, 24)], dayStart, dayEnd, HALF_HOUR);
        expect(plan).toBeNull();
    });

    it('moves existing breaks along with the surrounding work', () => {
        // Fully packed day: work 09-12, break 12-12:30, work 12:30-17. Opening a
        // slot after the morning work pushes the existing break and the afternoon
        // work later together — the plan never lands on top of the break.
        const plan = suggestMovePlan(
            [movable('a', 9, 12), movable('c', 12.5, 17)],
            dayStart,
            dayEnd,
            HALF_HOUR,
            [movable('x', 12, 12.5)]
        );
        expect(plan!.breakSlot).toEqual({ start: `${DAY}T12:00:00Z`, end: `${DAY}T12:30:00Z` });
        expect([...plan!.shifted].sort((a, b) => a.id.localeCompare(b.id))).toEqual([
            { id: 'c', start: `${DAY}T13:00:00Z`, end: `${DAY}T17:30:00Z` },
            { id: 'x', start: `${DAY}T12:30:00Z`, end: `${DAY}T13:00:00Z` },
        ]);
    });
});

describe('buildDayPlacementContext', () => {
    const PREV_DAY = '2026-07-13';
    const entry = (id: string, start: string, end: string | null, type = 'work') => ({
        id,
        start,
        end,
        type,
    });

    it('separates movable work and breaks fully inside the day', () => {
        const ctx = buildDayPlacementContext(
            [
                entry('w1', `${DAY}T09:00:00Z`, `${DAY}T12:00:00Z`),
                entry('b1', `${DAY}T12:00:00Z`, `${DAY}T12:30:00Z`, 'break'),
                entry('w2', `${DAY}T13:00:00Z`, `${DAY}T17:00:00Z`),
            ],
            dayStart,
            dayEnd
        );
        expect(ctx.work.map((e) => e.id)).toEqual(['w1', 'w2']);
        expect(ctx.breaks.map((e) => e.id)).toEqual(['b1']);
        expect(ctx.dayStart).toBe(`${DAY}T00:00:00Z`);
        // `T24:00` normalizes to the next day's midnight
        expect(ctx.dayEnd).toBe(`2026-07-15T00:00:00Z`);
    });

    it('excludes the break being re-placed', () => {
        const ctx = buildDayPlacementContext(
            [entry('b1', `${DAY}T12:00:00Z`, `${DAY}T12:30:00Z`, 'break')],
            dayStart,
            dayEnd,
            'b1'
        );
        expect(ctx.breaks).toEqual([]);
    });

    it('turns entries crossing midnight into walls that shrink the day window', () => {
        // 22:00 (prev day) - 02:00 spills in; 23:00 - 01:00 (next day) spills out.
        const ctx = buildDayPlacementContext(
            [
                entry('overnight', `${PREV_DAY}T22:00:00Z`, `${DAY}T02:00:00Z`),
                entry('w1', `${DAY}T09:00:00Z`, `${DAY}T17:00:00Z`),
                entry('late', `${DAY}T23:00:00Z`, `2026-07-15T01:00:00Z`),
            ],
            dayStart,
            dayEnd
        );
        // Boundary-crossers are not movable...
        expect(ctx.work.map((e) => e.id)).toEqual(['w1']);
        // ...but clamp the usable window so nothing can be shifted into them.
        expect(ctx.dayStart).toBe(`${DAY}T02:00:00Z`);
        expect(ctx.dayEnd).toBe(`${DAY}T23:00:00Z`);
    });

    it('ignores entries on other days', () => {
        const ctx = buildDayPlacementContext(
            [entry('other-day', `${PREV_DAY}T09:00:00Z`, `${PREV_DAY}T10:00:00Z`)],
            dayStart,
            dayEnd
        );
        expect(ctx.work).toEqual([]);
        expect(ctx.breaks).toEqual([]);
        expect(ctx.blocked).toEqual([]);
    });

    it('turns a running entry into a blocker that caps the day window', () => {
        const ctx = buildDayPlacementContext(
            [
                entry('w1', `${DAY}T06:00:00Z`, `${DAY}T08:00:00Z`),
                entry('running', `${DAY}T09:00:00Z`, null),
            ],
            dayStart,
            dayEnd
        );
        // The running entry is not movable, blocks the day from its start on,
        // and nothing can be shifted to or past it.
        expect(ctx.work.map((e) => e.id)).toEqual(['w1']);
        expect(ctx.blocked).toEqual([{ start: `${DAY}T09:00:00Z`, end: dayEnd }]);
        expect(ctx.dayEnd).toBe(`${DAY}T09:00:00Z`);
    });
});

describe('findValidBreakGapNear', () => {
    // 09-10 and 11:30-12:30 → a 90-min gap (10:00-11:30). A 1h break has a valid
    // start window of 10:00-10:30; findValidBreakGap would center it at 10:15.
    const work = [iv(9, 10), iv(11.5, 12.5)];

    it('keeps the break at its current start instead of recentering', () => {
        const gap = findValidBreakGapNear(work, HOUR, `${DAY}T10:00:00Z`);
        expect(gap).toEqual({ start: `${DAY}T10:00:00Z`, end: `${DAY}T11:00:00Z` });
    });

    it('clamps the anchor into the tolerance window when it sits too late', () => {
        // Anchored at 11:00 (beyond the window) → clamped back to 10:30.
        const gap = findValidBreakGapNear(work, HOUR, `${DAY}T11:00:00Z`);
        expect(gap).toEqual({ start: `${DAY}T10:30:00Z`, end: `${DAY}T11:30:00Z` });
    });

    it('keeps the break in place inside an oversized gap', () => {
        // 09-10 and 14-15 → 4h gap. The break stays exactly where the user left it;
        // its distance from work is a soft hint, not a reason to move it.
        const wideWork = [iv(9, 10), iv(14, 15)];
        expect(findValidBreakGapNear(wideWork, HALF_HOUR, `${DAY}T11:00:00Z`)).toEqual({
            start: `${DAY}T11:00:00Z`,
            end: `${DAY}T11:30:00Z`,
        });
    });

    it('clamps the anchor so the break stays inside the gap', () => {
        // Anchored at 13:50 in the 10:00-14:00 gap → a 30m break would spill into
        // the next work entry, so it is clamped back to 13:30-14:00.
        const wideWork = [iv(9, 10), iv(14, 15)];
        expect(findValidBreakGapNear(wideWork, HALF_HOUR, `${DAY}T13:50:00Z`)).toEqual({
            start: `${DAY}T13:30:00Z`,
            end: `${DAY}T14:00:00Z`,
        });
    });

    it("returns null when the anchor's gap can't hold the new duration", () => {
        // 09-10 and 10:30-12:30 → only a 30-min gap; a 1h break no longer fits.
        const tightWork = [iv(9, 10), iv(10.5, 12.5)];
        expect(findValidBreakGapNear(tightWork, HOUR, `${DAY}T10:00:00Z`)).toBeNull();
    });

    it('returns null when the anchor sits outside every inter-work gap', () => {
        // Anchor before the first work entry — a genuinely misplaced break, which the
        // caller then re-places via findValidBreakGap instead.
        expect(findValidBreakGapNear(work, HOUR, `${DAY}T08:00:00Z`)).toBeNull();
    });

    it('returns null when no free window in the gap can hold the break', () => {
        // Another break occupies 10:00-11:00; the leftover windows (none before,
        // 30m after) can't hold a 1h break → fall back to findValidBreakGap.
        expect(findValidBreakGapNear(work, HOUR, `${DAY}T10:00:00Z`, [iv(10, 11)])).toBeNull();
    });

    it('slides past a neighboring break inside the same gap instead of bailing', () => {
        // Gap 10:00-12:00 between work; another break sits at 10:30-11:00. Growing
        // the 10:00 break to 45m no longer fits before it, so it settles right
        // after the neighbor (11:00) — not in a different gap across the day.
        const wideWork = [iv(9, 10), iv(12, 13)];
        expect(findValidBreakGapNear(wideWork, 2700, `${DAY}T10:00:00Z`, [iv(10.5, 11)])).toEqual({
            start: `${DAY}T11:00:00Z`,
            end: `${DAY}T11:45:00Z`,
        });
    });

    it('settles in the free window closest to the anchor', () => {
        // Gap 10:00-13:00 with obstacles 10:45-11:00 and 11:15-12:30. For a 30m
        // break anchored at 10:50 the candidates are 10:15 (35m away) and 12:30
        // (100m away); the middle window is too small.
        const wideWork = [iv(9, 10), iv(13, 14)];
        expect(
            findValidBreakGapNear(wideWork, HALF_HOUR, `${DAY}T10:50:00Z`, [
                iv(10.75, 11),
                iv(11.25, 12.5),
            ])
        ).toEqual({ start: `${DAY}T10:15:00Z`, end: `${DAY}T10:45:00Z` });
    });
});
