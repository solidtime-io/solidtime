import { describe, expect, it } from 'vitest';
import type { TimeEntry } from '@/packages/api/src';
import {
    findMisplacedBreak,
    type BreakPlacementHint,
} from '@/packages/ui/src/utils/breakPlacement';

// Decision logic behind the aggregate (collapsed grouped-break) row's placement
// warning: the row shows the hint — and navigates the calendar — based on the
// first misplaced break in the group.
function breakEntry(id: string): TimeEntry {
    return { id, type: 'break', start: '2026-07-14T10:00:00Z' } as TimeEntry;
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
        const entries = [breakEntry('break-a'), breakEntry('break-b')];
        const result = findMisplacedBreak(entries, {
            'break-a': hint(false),
            'break-b': hint(true),
        });
        expect(result?.id).toBe('break-b');
    });

    it('returns null when no break in the group is misplaced', () => {
        const entries = [breakEntry('break-a'), breakEntry('break-b')];
        const result = findMisplacedBreak(entries, {
            'break-a': hint(false),
            'break-b': hint(false),
        });
        expect(result).toBeNull();
    });

    it('returns null when the group has no placement hints', () => {
        const entries = [breakEntry('break-a'), breakEntry('break-b')];
        expect(findMisplacedBreak(entries, {})).toBeNull();
    });

    it('ignores hints for entries that are not in the group', () => {
        const entries = [breakEntry('break-a')];
        const result = findMisplacedBreak(entries, {
            'break-a': hint(false),
            'break-elsewhere': hint(true),
        });
        expect(result).toBeNull();
    });
});
