import { type Dayjs } from 'dayjs';
import type { TimeEntry } from '@/packages/api/src';
import { getDayJsInstance } from '@/packages/ui/src/utils/time';

// `getDayJsInstance()` reads window-injected settings (week-start), which
// aren't available at module load. Each function calls it lazily at use
// time. The cost is a per-call locale update; cellMath doesn't use any
// week-start-aware APIs so it's a no-op functionally.

/**
 * UTC ISO of 09:00 local on `date` — the preferred placement for new
 * work when an empty day needs a default start time.
 */
export function workDayStartOn(date: string, tz: string): string {
    const dayjs = getDayJsInstance();
    return dayjs.tz(`${date} 09:00:00`, tz).utc().format();
}

export interface FreeWindow {
    start: string;
    end: string;
}

interface Interval {
    start: Dayjs;
    end: Dayjs;
}

function localDayBounds(date: string, tz: string): { dayStart: Dayjs; dayEnd: Dayjs } {
    const dayjs = getDayJsInstance();
    // `.add(1, 'day')` on a Dayjs instance advances by a fixed 24h, which is
    // wrong on DST-transition days (the local day is 23h or 25h long). Derive
    // the next calendar date in UTC (no DST) and take its local midnight, so
    // `dayEnd` is always the real next local midnight.
    const nextDate = dayjs.utc(date).add(1, 'day').format('YYYY-MM-DD');
    return {
        dayStart: dayjs.tz(`${date} 00:00:00`, tz).utc(),
        dayEnd: dayjs.tz(`${nextDate} 00:00:00`, tz).utc(),
    };
}

/**
 * Collect entries that intersect the day `[dayStart, dayEnd)`, clipped
 * to those bounds. Running entries use `nowDayjs` as their end.
 */
function collectDayObstacles(
    entries: TimeEntry[],
    dayStart: Dayjs,
    dayEnd: Dayjs,
    nowDayjs: Dayjs
): Interval[] {
    const dayjs = getDayJsInstance();
    const obstacles: Interval[] = [];
    for (const entry of entries) {
        const entryStart = dayjs.utc(entry.start);
        const entryEnd = entry.end ? dayjs.utc(entry.end) : nowDayjs;

        if (entryEnd.isSameOrBefore(dayStart)) continue;
        if (entryStart.isSameOrAfter(dayEnd)) continue;

        const clippedStart = entryStart.isBefore(dayStart) ? dayStart : entryStart;
        const clippedEnd = entryEnd.isAfter(dayEnd) ? dayEnd : entryEnd;

        obstacles.push({ start: clippedStart, end: clippedEnd });
    }
    return obstacles;
}

/**
 * First free window on the local calendar day that fits `requiredSeconds`
 * without colliding with any existing entry. Returns `null` if nothing fits
 * — never crosses midnight.
 *
 * Obstacles include same-day entries, spillovers from adjacent days, and
 * running entries (treated as `end = now`). All are clipped to the day's
 * `[00:00, 24:00)` boundaries.
 *
 * `preferredStart` (UTC ISO) is a hard floor — windows with `start` before
 * it are rejected. Use it to place "after some cursor."
 */
export function findFreeWindowOnDay(
    entries: TimeEntry[],
    date: string,
    requiredSeconds: number,
    tz: string,
    preferredStart?: string | null,
    now?: string | Dayjs
): FreeWindow | null {
    if (requiredSeconds <= 0) return null;

    const dayjs = getDayJsInstance();
    const { dayStart, dayEnd } = localDayBounds(date, tz);

    if (requiredSeconds > dayEnd.diff(dayStart, 'second')) return null;

    const nowDayjs = now ? dayjs.utc(now) : dayjs.utc();

    const obstacles = collectDayObstacles(entries, dayStart, dayEnd, nowDayjs);

    // Sort + merge so we can walk a clean [gap, obstacle, gap, ...] sequence.
    obstacles.sort((a, b) => a.start.diff(b.start));

    // merge overlaps
    const merged: Interval[] = [];
    for (const obs of obstacles) {
        const last = merged[merged.length - 1];
        if (last && obs.start.isSameOrBefore(last.end)) {
            if (obs.end.isAfter(last.end)) {
                last.end = obs.end;
            }
        } else {
            merged.push({ start: obs.start, end: obs.end });
        }
    }

    let cursor: Dayjs = dayStart;
    if (preferredStart) {
        const pref = dayjs.utc(preferredStart);
        if (pref.isAfter(cursor)) cursor = pref;
    }
    if (cursor.isSameOrAfter(dayEnd)) return null;

    for (const obs of merged) {
        if (obs.end.isSameOrBefore(cursor)) continue;

        if (obs.start.isAfter(cursor)) {
            const gapSeconds = obs.start.diff(cursor, 'second');
            if (gapSeconds >= requiredSeconds) {
                return {
                    start: cursor.format(),
                    end: cursor.add(requiredSeconds, 'second').format(),
                };
            }
        }

        if (obs.end.isAfter(cursor)) cursor = obs.end;
        if (cursor.isSameOrAfter(dayEnd)) return null;
    }

    const trailingSeconds = dayEnd.diff(cursor, 'second');
    if (trailingSeconds >= requiredSeconds) {
        return {
            start: cursor.format(),
            end: cursor.add(requiredSeconds, 'second').format(),
        };
    }

    return null;
}

/**
 * Seconds of free space starting at `cursor` until the next obstacle
 * (or end of day). Returns 0 if the cursor is inside an obstacle or past
 * midnight. Used by the extend path: "how far can I push this end forward?"
 */
export function freeGapSecondsAfter(
    entries: TimeEntry[],
    date: string,
    tz: string,
    cursor: string,
    now?: string | Dayjs
): number {
    const dayjs = getDayJsInstance();
    const { dayStart, dayEnd } = localDayBounds(date, tz);
    const cursorDjs = dayjs.utc(cursor);

    if (cursorDjs.isSameOrAfter(dayEnd)) return 0;
    if (cursorDjs.isBefore(dayStart)) return 0;

    const nowDayjs = now ? dayjs.utc(now) : dayjs.utc();

    // Drop obstacles ending at/before the cursor — they're behind us.
    const obstacles = collectDayObstacles(entries, dayStart, dayEnd, nowDayjs).filter((obs) =>
        obs.end.isAfter(cursorDjs)
    );

    obstacles.sort((a, b) => a.start.diff(b.start));

    // Cursor inside an obstacle → no gap.
    for (const obs of obstacles) {
        if (obs.start.isSameOrBefore(cursorDjs) && obs.end.isAfter(cursorDjs)) {
            return 0;
        }
    }

    // Distance to first obstacle strictly after cursor, or to end of day.
    for (const obs of obstacles) {
        if (obs.start.isAfter(cursorDjs)) {
            return Math.max(0, obs.start.diff(cursorDjs, 'second'));
        }
    }
    return Math.max(0, dayEnd.diff(cursorDjs, 'second'));
}

/**
 * Thrown when a required duration cannot fit on the target day without
 * introducing an overlap. Callers reformat the message for end users.
 */
export class NoFreeWindowError extends Error {
    public readonly code = 'no_free_window' as const;
    public readonly date: string;
    public readonly requiredSeconds: number;

    constructor(date: string, requiredSeconds: number) {
        super(
            `Cannot fit ${requiredSeconds} seconds on ${date} without overlapping existing time entries.`
        );
        this.name = 'NoFreeWindowError';
        this.date = date;
        this.requiredSeconds = requiredSeconds;
    }
}
