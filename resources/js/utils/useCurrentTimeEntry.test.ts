import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import { describe, expect, it } from 'vitest';
import type { TimeEntry } from '@/packages/api/src';
import { getLastWorkTimeEntry } from './useCurrentTimeEntry';

dayjs.extend(utc);

function timeEntry(id: string, start: string, type: 'work' | 'break'): TimeEntry {
    return {
        id,
        start,
        end: null,
        duration: null,
        description: '',
        project_id: null,
        task_id: null,
        organization_id: 'organization-1',
        user_id: 'user-1',
        tags: [],
        billable: false,
        type,
    } as TimeEntry;
}

describe('getLastWorkTimeEntry', () => {
    it('returns the newest work entry that is not in the future', () => {
        const entries = [
            timeEntry('future-work', '2026-07-14T14:00:00Z', 'work'),
            timeEntry('break', '2026-07-14T12:00:00Z', 'break'),
            timeEntry('last-work', '2026-07-14T11:00:00Z', 'work'),
            timeEntry('older-work', '2026-07-14T10:00:00Z', 'work'),
        ];

        expect(getLastWorkTimeEntry(entries, dayjs.utc('2026-07-14T13:00:00Z'))?.id).toBe(
            'last-work'
        );
    });
});
