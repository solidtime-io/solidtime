import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { useTimeEntriesStore } from '@/utils/useTimeEntries';

export function initializeStores() {
    // TanStack Query now handles projects, tasks, tags, clients, and members fetching automatically
    // Only initialize stores that aren't migrated to TanStack Query yet
    useCurrentTimeEntryStore().fetchCurrentTimeEntry();
    useTimeEntriesStore().patchTimeEntries();
}
