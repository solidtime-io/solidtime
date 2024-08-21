import type { TimeEntry } from '@/packages/api/src';

export type TimeEntriesGroupedByType = TimeEntry & { timeEntries: TimeEntry[] };
