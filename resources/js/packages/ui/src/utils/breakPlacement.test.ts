import { describe, expect, it } from 'vitest';
import type { TimeEntry } from '@/packages/api/src';
import {
    findMisplacedBreak,
    getBreakPlacementHint,
    type BreakPlacementHint,
} from '@/packages/ui/src/utils/breakPlacement';

function entry(
    id: string,
    start: string,
    end: string | null,
    type: 'work' | 'break' = 'work'
): TimeEntry {
    return {
        id,
        type,
        start,
        end,
        duration: end ? (Date.parse(end) - Date.parse(start)) / 1000 : null,
        organization_id: 'organization-1',
        user_id: 'user-1',
        member_id: 'member-1',
        project_id: type === 'break' ? null : 'project-1',
        task_id: null,
        billable: false,
        description: null,
        tags: [],
    };
}

function hint(misplaced: boolean): BreakPlacementHint {
    return {
        misplaced,
        previousWorkEnd: null,
        nextWorkStart: null,
        gapBeforeSeconds: null,
        gapAfterSeconds: null,
    };
}

describe('findMisplacedBreak', () => {
    it('returns the first misplaced break in a group', () => {
        const entries = [
            entry('break-a', '2026-07-14T10:00:00Z', '2026-07-14T10:30:00Z', 'break'),
            entry('break-b', '2026-07-14T12:00:00Z', '2026-07-14T12:30:00Z', 'break'),
        ];
        const result = findMisplacedBreak(entries, {
            'break-a': hint(false),
            'break-b': hint(true),
        });
        expect(result?.id).toBe('break-b');
    });
});

describe('getBreakPlacementHint', () => {
    const breakEntry = entry('break', '2026-07-14T12:00:00Z', '2026-07-14T12:30:00Z', 'break');

    it('accepts work touching both sides of the break', () => {
        const result = getBreakPlacementHint(breakEntry, [
            entry('morning', '2026-07-14T09:00:00Z', '2026-07-14T12:00:00Z'),
            entry('afternoon', '2026-07-14T12:30:00Z', '2026-07-14T17:00:00Z'),
        ]);

        expect(result).toEqual(
            expect.objectContaining({
                misplaced: false,
                gapBeforeSeconds: 0,
                gapAfterSeconds: 0,
            })
        );
    });

    it('accepts gaps exactly at the placement tolerance', () => {
        const result = getBreakPlacementHint(breakEntry, [
            entry('morning', '2026-07-14T09:00:00Z', '2026-07-14T11:30:00Z'),
            entry('afternoon', '2026-07-14T13:00:00Z', '2026-07-14T17:00:00Z'),
        ]);

        expect(result).toEqual(
            expect.objectContaining({
                misplaced: false,
                gapBeforeSeconds: 30 * 60,
                gapAfterSeconds: 30 * 60,
            })
        );
    });

    it('flags a completed break when work is missing on either side', () => {
        const noPreviousWork = getBreakPlacementHint(breakEntry, [
            entry('afternoon', '2026-07-14T12:30:00Z', '2026-07-14T17:00:00Z'),
        ]);
        const noNextWork = getBreakPlacementHint(breakEntry, [
            entry('morning', '2026-07-14T09:00:00Z', '2026-07-14T12:00:00Z'),
        ]);

        expect(noPreviousWork).toEqual(
            expect.objectContaining({ misplaced: true, gapBeforeSeconds: null })
        );
        expect(noNextWork).toEqual(
            expect.objectContaining({ misplaced: true, gapAfterSeconds: null })
        );
    });

    it('does not require work after a running break', () => {
        const runningBreak = entry('break', '2026-07-14T12:00:00Z', null, 'break');
        const result = getBreakPlacementHint(runningBreak, [
            entry('morning', '2026-07-14T09:00:00Z', '2026-07-14T12:00:00Z'),
        ]);

        expect(result).toEqual(
            expect.objectContaining({
                misplaced: false,
                gapBeforeSeconds: 0,
                gapAfterSeconds: null,
            })
        );
    });

    it('treats work overlapping the break as touching both sides', () => {
        const result = getBreakPlacementHint(breakEntry, [
            entry('overlapping', '2026-07-14T11:45:00Z', '2026-07-14T12:15:00Z'),
        ]);

        expect(result).toEqual(
            expect.objectContaining({
                misplaced: false,
                gapBeforeSeconds: 0,
                gapAfterSeconds: 0,
            })
        );
    });

    it('returns null for work entries', () => {
        expect(
            getBreakPlacementHint(entry('work', '2026-07-14T09:00:00Z', '2026-07-14T10:00:00Z'), [])
        ).toBeNull();
    });
});
