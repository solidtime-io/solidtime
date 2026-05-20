import { describe, it, expect } from 'vitest';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import timezone from 'dayjs/plugin/timezone';
import { findFreeWindowOnDay, freeGapSecondsAfter, NoFreeWindowError } from './cellMath';
import type { TimeEntry } from '@/packages/api/src';

dayjs.extend(utc);
dayjs.extend(timezone);

// All times in the tests are in UTC for clarity. The "day" we search is
// 2026-04-10 in UTC (so we use tz='UTC' to avoid local-machine surprises).
const TZ = 'UTC';
const DATE = '2026-04-10';

/** Build a fake TimeEntry from UTC ISO timestamps. */
function entry(start: string, end: string | null, id = `e-${start}-${end}`): TimeEntry {
    const startMs = dayjs.utc(start).valueOf();
    const endMs = end ? dayjs.utc(end).valueOf() : startMs;
    return {
        id,
        start,
        end,
        duration: end ? Math.floor((endMs - startMs) / 1000) : null,
        description: '',
        member_id: 'm-1',
        project_id: null,
        task_id: null,
        billable: false,
        tags: [],
        // The grid only reads the fields above; the rest are placeholders
        // to satisfy the TimeEntry type without pulling in real fixtures.
        user_id: 'u-1',
        organization_id: 'o-1',
    } as unknown as TimeEntry;
}

const HOUR = 3600;

describe('findFreeWindowOnDay', () => {
    // ── Empty / trivial cases ──────────────────────────────────────

    it('returns the start of the day for a totally empty day', () => {
        const result = findFreeWindowOnDay([], DATE, HOUR, TZ);
        expect(result).toEqual({
            start: '2026-04-10T00:00:00Z',
            end: '2026-04-10T01:00:00Z',
        });
    });

    it('returns null for zero or negative required seconds', () => {
        expect(findFreeWindowOnDay([], DATE, 0, TZ)).toBeNull();
        expect(findFreeWindowOnDay([], DATE, -1, TZ)).toBeNull();
    });

    it('refuses any duration > 24h on principle (single-day constraint)', () => {
        const result = findFreeWindowOnDay([], DATE, 25 * HOUR, TZ);
        expect(result).toBeNull();
    });

    // ── Single obstacle, basic gap finding ────────────────────────

    it('finds the gap before a single obstacle if it fits', () => {
        const obs = [entry('2026-04-10T10:00:00Z', '2026-04-10T11:00:00Z')];
        const result = findFreeWindowOnDay(obs, DATE, HOUR, TZ);
        expect(result?.start).toBe('2026-04-10T00:00:00Z');
        expect(result?.end).toBe('2026-04-10T01:00:00Z');
    });

    it('finds the gap after a single obstacle when preferredStart skips earlier gaps', () => {
        const obs = [entry('2026-04-10T10:00:00Z', '2026-04-10T11:00:00Z')];
        const result = findFreeWindowOnDay(obs, DATE, HOUR, TZ, '2026-04-10T11:00:00Z');
        expect(result?.start).toBe('2026-04-10T11:00:00Z');
        expect(result?.end).toBe('2026-04-10T12:00:00Z');
    });

    // ── Multi-obstacle gap walking ────────────────────────────────

    it('finds a gap between two obstacles', () => {
        const obs = [
            entry('2026-04-10T08:00:00Z', '2026-04-10T10:00:00Z'),
            entry('2026-04-10T12:00:00Z', '2026-04-10T14:00:00Z'),
        ];
        // Search for 2h, expecting the [00:00, 08:00) gap (8h available)
        const result = findFreeWindowOnDay(obs, DATE, 2 * HOUR, TZ);
        expect(result?.start).toBe('2026-04-10T00:00:00Z');
    });

    it('walks past gaps that are too small', () => {
        const obs = [
            entry('2026-04-10T00:30:00Z', '2026-04-10T10:00:00Z'),
            entry('2026-04-10T11:00:00Z', '2026-04-10T12:00:00Z'),
        ];
        // First gap is 30min, second gap is 1h, third is 12h.
        // Asking for 90min → first two gaps are too small, third fits.
        const result = findFreeWindowOnDay(obs, DATE, 90 * 60, TZ);
        expect(result?.start).toBe('2026-04-10T12:00:00Z');
    });

    it('uses preferredStart even when it lands inside a gap', () => {
        const obs = [
            entry('2026-04-10T00:00:00Z', '2026-04-10T08:00:00Z'),
            entry('2026-04-10T12:00:00Z', '2026-04-10T14:00:00Z'),
        ];
        // preferredStart 09:00 → gap is [09:00, 12:00) = 3h
        const result = findFreeWindowOnDay(obs, DATE, 2 * HOUR, TZ, '2026-04-10T09:00:00Z');
        expect(result?.start).toBe('2026-04-10T09:00:00Z');
        expect(result?.end).toBe('2026-04-10T11:00:00Z');
    });

    it('skips ahead when preferredStart lands inside an obstacle', () => {
        const obs = [entry('2026-04-10T08:00:00Z', '2026-04-10T12:00:00Z')];
        // preferredStart 10:00 lands inside [08:00, 12:00). We must
        // advance to the next free position (12:00).
        const result = findFreeWindowOnDay(obs, DATE, HOUR, TZ, '2026-04-10T10:00:00Z');
        expect(result?.start).toBe('2026-04-10T12:00:00Z');
    });

    // ── Spillover from previous day ───────────────────────────────

    it('treats an entry that started yesterday but ends today as an obstacle', () => {
        // Yesterday 23:00 → today 02:00 → blocks the first 2h of today.
        const obs = [entry('2026-04-09T23:00:00Z', '2026-04-10T02:00:00Z')];
        const result = findFreeWindowOnDay(obs, DATE, HOUR, TZ);
        expect(result?.start).toBe('2026-04-10T02:00:00Z');
    });

    it('ignores an entry that ended exactly at the start of the day', () => {
        // Yesterday 22:00 → today 00:00 (exclusive) → does NOT block today.
        const obs = [entry('2026-04-09T22:00:00Z', '2026-04-10T00:00:00Z')];
        const result = findFreeWindowOnDay(obs, DATE, HOUR, TZ);
        expect(result?.start).toBe('2026-04-10T00:00:00Z');
    });

    // ── Running entries ────────────────────────────────────────────

    it('treats a running entry as blocking up to "now"', () => {
        const obs = [entry('2026-04-10T08:00:00Z', null)];
        const now = '2026-04-10T10:30:00Z';
        // The running entry blocks 08:00–10:30 → first free window is
        // either before 08:00 (8h available, fits a 1h request).
        const result = findFreeWindowOnDay(obs, DATE, HOUR, TZ, null, now);
        expect(result?.start).toBe('2026-04-10T00:00:00Z');
    });

    it('places after a running entry when preferredStart pushes past it', () => {
        const obs = [entry('2026-04-10T08:00:00Z', null)];
        const now = '2026-04-10T10:30:00Z';
        const result = findFreeWindowOnDay(obs, DATE, HOUR, TZ, '2026-04-10T09:00:00Z', now);
        // preferredStart 09:00 lands inside the running entry's blocked
        // range [08:00, 10:30) → must skip to 10:30.
        expect(result?.start).toBe('2026-04-10T10:30:00Z');
    });

    // ── Midnight refusal ──────────────────────────────────────────

    it('refuses to return a window that would cross midnight', () => {
        const obs = [entry('2026-04-10T00:00:00Z', '2026-04-10T22:30:00Z')];
        // Only 90min remain in the day. Asking for 2h → null.
        const result = findFreeWindowOnDay(obs, DATE, 2 * HOUR, TZ);
        expect(result).toBeNull();
    });

    it('accepts a window that ends exactly at midnight', () => {
        const obs = [entry('2026-04-10T00:00:00Z', '2026-04-10T22:00:00Z')];
        // Exactly 2h remain → 22:00–00:00.
        const result = findFreeWindowOnDay(obs, DATE, 2 * HOUR, TZ);
        expect(result?.start).toBe('2026-04-10T22:00:00Z');
        expect(result?.end).toBe('2026-04-11T00:00:00Z');
    });

    // ── Pre-existing overlapping obstacles ────────────────────────

    it('merges overlapping obstacles before computing gaps', () => {
        const obs = [
            entry('2026-04-10T09:00:00Z', '2026-04-10T11:00:00Z'),
            entry('2026-04-10T10:00:00Z', '2026-04-10T13:00:00Z'),
            entry('2026-04-10T15:00:00Z', '2026-04-10T16:00:00Z'),
        ];
        // Effective obstacles: [09:00, 13:00) and [15:00, 16:00)
        // First gap is [00:00, 09:00) = 9h. Asking for 2h → 00:00.
        const result = findFreeWindowOnDay(obs, DATE, 2 * HOUR, TZ);
        expect(result?.start).toBe('2026-04-10T00:00:00Z');
    });

    // ── Day full ──────────────────────────────────────────────────

    it('returns null when no gap is large enough', () => {
        const obs = [
            entry('2026-04-10T00:00:00Z', '2026-04-10T11:00:00Z'),
            entry('2026-04-10T11:30:00Z', '2026-04-10T22:00:00Z'),
            entry('2026-04-10T22:30:00Z', '2026-04-10T23:30:00Z'),
        ];
        // Gaps: 30min, 30min, 30min. Asking for 1h → null.
        const result = findFreeWindowOnDay(obs, DATE, HOUR, TZ);
        expect(result).toBeNull();
    });

    // ── Timezone awareness ────────────────────────────────────────

    it('respects the user timezone for day boundaries', () => {
        // In Pacific/Auckland (+13 in April 2026), 2026-04-10 starts at
        // 2026-04-09T11:00:00Z (NZDT was UTC+13 until April 5 2026, then
        // NZST UTC+12 — let's pick a date in NZST so the offset is +12).
        // 2026-04-10 NZST = 2026-04-09T12:00:00Z to 2026-04-10T12:00:00Z
        const tz = 'Pacific/Auckland';
        const obs = [
            // This entry is at 2026-04-10T08:00:00Z = 2026-04-10T20:00 NZ
            // → falls in the NZ day for 2026-04-10. So it blocks from
            // 20:00 to 21:00 NZ time.
            entry('2026-04-10T08:00:00Z', '2026-04-10T09:00:00Z'),
        ];
        const result = findFreeWindowOnDay(obs, '2026-04-10', HOUR, tz);
        // Day starts at 2026-04-09T12:00:00Z in NZST. First gap is the
        // 20h before the obstacle starts.
        expect(result?.start).toBe('2026-04-09T12:00:00Z');
    });

    it('does not place work past local midnight on spring-forward days', () => {
        const tz = 'Europe/Vienna';
        const obs = [entry('2026-03-28T23:00:00Z', '2026-03-29T22:00:00Z')];

        const result = findFreeWindowOnDay(obs, '2026-03-29', HOUR, tz);

        expect(result).toBeNull();
    });

    it('can use the final local hour on fall-back days', () => {
        const tz = 'Europe/Vienna';

        const result = findFreeWindowOnDay([], '2026-10-25', HOUR, tz, '2026-10-25T22:00:00Z');

        expect(result).toEqual({
            start: '2026-10-25T22:00:00Z',
            end: '2026-10-25T23:00:00Z',
        });
    });
});

describe('freeGapSecondsAfter', () => {
    it('returns the rest of the day for an empty day', () => {
        const result = freeGapSecondsAfter([], DATE, TZ, '2026-04-10T09:00:00Z');
        // 09:00 → 24:00 = 15 hours
        expect(result).toBe(15 * HOUR);
    });

    it('returns 0 when cursor is at end-of-day', () => {
        const result = freeGapSecondsAfter([], DATE, TZ, '2026-04-11T00:00:00Z');
        expect(result).toBe(0);
    });

    it('returns 0 when cursor is after end-of-day', () => {
        const result = freeGapSecondsAfter([], DATE, TZ, '2026-04-11T05:00:00Z');
        expect(result).toBe(0);
    });

    it('returns 0 when cursor is before the day starts', () => {
        const result = freeGapSecondsAfter([], DATE, TZ, '2026-04-09T23:00:00Z');
        expect(result).toBe(0);
    });

    it('returns the gap to the next obstacle', () => {
        const obs = [entry('2026-04-10T11:00:00Z', '2026-04-10T12:00:00Z')];
        // cursor 09:00 → next obstacle 11:00 → 2h gap
        const result = freeGapSecondsAfter(obs, DATE, TZ, '2026-04-10T09:00:00Z');
        expect(result).toBe(2 * HOUR);
    });

    it('returns 0 when cursor sits inside an obstacle', () => {
        const obs = [entry('2026-04-10T08:00:00Z', '2026-04-10T12:00:00Z')];
        const result = freeGapSecondsAfter(obs, DATE, TZ, '2026-04-10T10:00:00Z');
        expect(result).toBe(0);
    });

    it('returns the rest of the day when no obstacle is ahead', () => {
        const obs = [entry('2026-04-10T08:00:00Z', '2026-04-10T09:00:00Z')];
        const result = freeGapSecondsAfter(obs, DATE, TZ, '2026-04-10T09:00:00Z');
        // 09:00 → 24:00 = 15h
        expect(result).toBe(15 * HOUR);
    });

    it('skips obstacles strictly before the cursor', () => {
        const obs = [
            entry('2026-04-10T08:00:00Z', '2026-04-10T09:00:00Z'),
            entry('2026-04-10T15:00:00Z', '2026-04-10T16:00:00Z'),
        ];
        // cursor 09:00 → first obstacle (08-09) is behind, next is 15:00 → 6h
        const result = freeGapSecondsAfter(obs, DATE, TZ, '2026-04-10T09:00:00Z');
        expect(result).toBe(6 * HOUR);
    });

    it('treats a running entry as blocking up to "now"', () => {
        const obs = [entry('2026-04-10T08:00:00Z', null)];
        const now = '2026-04-10T10:00:00Z';
        // cursor 11:00 is after the running entry's effective end (10:00),
        // so the gap is the rest of the day = 13h
        const result = freeGapSecondsAfter(obs, DATE, TZ, '2026-04-10T11:00:00Z', now);
        expect(result).toBe(13 * HOUR);
    });

    it('returns 0 when cursor is inside a running entry (cursor < now)', () => {
        const obs = [entry('2026-04-10T08:00:00Z', null)];
        const now = '2026-04-10T12:00:00Z';
        // cursor 10:00 falls inside [08:00, 12:00) → blocked
        const result = freeGapSecondsAfter(obs, DATE, TZ, '2026-04-10T10:00:00Z', now);
        expect(result).toBe(0);
    });

    it('clips the gap to midnight, never beyond', () => {
        const obs = [entry('2026-04-10T08:00:00Z', '2026-04-10T09:00:00Z')];
        // cursor 23:00 → 1h until midnight
        const result = freeGapSecondsAfter(obs, DATE, TZ, '2026-04-10T23:00:00Z');
        expect(result).toBe(HOUR);
    });

    it('clips spring-forward days at the next local midnight', () => {
        const result = freeGapSecondsAfter(
            [],
            '2026-03-29',
            'Europe/Vienna',
            '2026-03-29T22:00:00Z'
        );

        expect(result).toBe(0);
    });

    it('includes the final local hour on fall-back days', () => {
        const result = freeGapSecondsAfter(
            [],
            '2026-10-25',
            'Europe/Vienna',
            '2026-10-25T22:00:00Z'
        );

        expect(result).toBe(HOUR);
    });
});

describe('NoFreeWindowError', () => {
    it('carries the date and required seconds', () => {
        const err = new NoFreeWindowError('2026-04-10', 7200);
        expect(err.code).toBe('no_free_window');
        expect(err.date).toBe('2026-04-10');
        expect(err.requiredSeconds).toBe(7200);
        expect(err.message).toContain('2026-04-10');
        expect(err instanceof Error).toBe(true);
    });
});
