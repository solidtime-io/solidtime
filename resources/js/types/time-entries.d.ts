import type { TimeEntry } from '@/utils/api';

export type TimeEntriesGroupedByType = TimeEntry & { timeEntries: TimeEntry[] };
