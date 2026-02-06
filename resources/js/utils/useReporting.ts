import { defineStore } from 'pinia';
import { type Component } from 'vue';
import { getCurrentRole, getCurrentUser } from '@/utils/useUser';
import { useProjectsQuery } from '@/utils/useProjectsQuery';
import { useMembersQuery } from '@/utils/useMembersQuery';
import { useTasksQuery } from '@/utils/useTasksQuery';
import { useClientsQuery } from '@/utils/useClientsQuery';
import { useTagsQuery } from '@/utils/useTagsQuery';
import { CheckCircleIcon, UserCircleIcon, UserGroupIcon } from '@heroicons/vue/20/solid';
import { DocumentTextIcon, FolderIcon } from '@heroicons/vue/16/solid';
import BillableIcon from '@/packages/ui/src/Icons/BillableIcon.vue';

export type GroupingOption =
    | 'project'
    | 'task'
    | 'user'
    | 'billable'
    | 'client'
    | 'description'
    | 'tag';

export const useReportingStore = defineStore('reporting', () => {
    // Cache query composables to avoid creating new subscriptions on every call
    const { projects } = useProjectsQuery();
    const { members } = useMembersQuery();
    const { tasks } = useTasksQuery();
    const { clients } = useClientsQuery();
    const { tags } = useTagsQuery();

    const emptyPlaceholder = {
        user: 'No User',
        project: 'No Project',
        task: 'No Task',
        billable: 'Non-Billable',
        client: 'No Client',
        description: 'No Description',
        tag: 'No Tag',
    } as Record<string, string>;

    function getNameForReportingRowEntry(key: string | null, type: string | null) {
        if (type === null) {
            return null;
        }
        if (key === null) {
            return emptyPlaceholder[type as keyof typeof emptyPlaceholder];
        }

        if (type === 'project') {
            return projects.value.find((project) => project.id === key)?.name;
        }
        if (type === 'user') {
            if (getCurrentRole() === 'employee') {
                return getCurrentUser().name;
            }
            return members.value.find((member) => member.user_id === key)?.name;
        }
        if (type === 'task') {
            return tasks.value.find((task) => task.id === key)?.name;
        }
        if (type === 'client') {
            return clients.value.find((client) => client.id === key)?.name;
        }
        if (type === 'tag') {
            return tags.value.find((tag) => tag.id === key)?.name;
        }
        if (type === 'billable') {
            if (key === '0') {
                return 'Non-Billable';
            } else {
                return 'Billable';
            }
        }
        return key;
    }

    const groupByOptions: {
        label: string;
        value: GroupingOption;
        icon: Component;
    }[] = [
        {
            label: 'Members',
            value: 'user',
            icon: UserGroupIcon,
        },
        {
            label: 'Projects',
            value: 'project',
            icon: FolderIcon,
        },
        {
            label: 'Tasks',
            value: 'task',
            icon: CheckCircleIcon,
        },
        {
            label: 'Clients',
            value: 'client',
            icon: UserCircleIcon,
        },
        {
            label: 'Billable',
            value: 'billable',
            icon: BillableIcon,
        },
        {
            label: 'Description',
            value: 'description',
            icon: DocumentTextIcon,
        },
        {
            label: 'Tags',
            value: 'tag',
            icon: DocumentTextIcon,
        },
    ];

    return {
        getNameForReportingRowEntry,
        groupByOptions,
        emptyPlaceholder,
    };
});
