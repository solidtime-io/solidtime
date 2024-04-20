import { useProjectsStore } from '@/utils/useProjects';
import { useTasksStore } from '@/utils/useTasks';
import { useTagsStore } from '@/utils/useTags';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { useClientsStore } from '@/utils/useClients';
import { useMembersStore } from '@/utils/useMembers';
import { useTimeEntriesStore } from '@/utils/useTimeEntries';

export function initializeStores() {
    useProjectsStore().fetchProjects();
    useTasksStore().fetchTasks();
    useTagsStore().fetchTags();
    useCurrentTimeEntryStore().fetchCurrentTimeEntry();
    useClientsStore().fetchClients();
    useMembersStore().fetchMembers();
    useTimeEntriesStore().fetchTimeEntries();
}
