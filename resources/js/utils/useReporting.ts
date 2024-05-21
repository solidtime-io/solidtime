import { defineStore, storeToRefs } from 'pinia';
import { api } from '../../../openapi.json.client';
import { computed, ref } from 'vue';
import type {
    AggregatedTimeEntries,
    AggregatedTimeEntriesQueryParams,
    ReportingResponse,
} from '@/utils/api';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';
import { useProjectsStore } from '@/utils/useProjects';
import { useMembersStore } from '@/utils/useMembers';
import { useTasksStore } from '@/utils/useTasks';

export const useReportingStore = defineStore('reporting', () => {
    const reportingGraphResponse = ref<ReportingResponse | null>(null);
    const reportingTableResponse = ref<ReportingResponse | null>(null);

    const { handleApiRequestNotifications } = useNotificationsStore();

    async function fetchGraphReporting(
        params: AggregatedTimeEntriesQueryParams
    ) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            reportingGraphResponse.value = await handleApiRequestNotifications(
                api.getAggregatedTimeEntries({
                    params: {
                        organization: organization,
                    },
                    queries: params,
                }),
                undefined,
                'Failed to fetch reporting data'
            );
        }
    }

    async function fetchTableReporting(
        params: AggregatedTimeEntriesQueryParams
    ) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            reportingTableResponse.value = await handleApiRequestNotifications(
                api.getAggregatedTimeEntries({
                    params: {
                        organization: organization,
                    },
                    queries: params,
                }),
                undefined,
                'Failed to fetch reporting data'
            );
        }
    }

    const aggregatedGraphTimeEntries = computed<AggregatedTimeEntries>(() => {
        return reportingGraphResponse.value?.data as AggregatedTimeEntries;
    });

    const aggregatedTableTimeEntries = computed<AggregatedTimeEntries>(() => {
        return reportingTableResponse.value?.data as AggregatedTimeEntries;
    });

    function getNameForReportingRowEntry(
        key: string | null,
        type: string | null
    ) {
        if (type === null) {
            return null;
        }
        if (key === null) {
            const emptyPlaceholder = {
                user: 'No User',
                project: 'No Project',
                task: 'No Task',
                billable: 'Non-Billable',
            };

            return emptyPlaceholder[type as keyof typeof emptyPlaceholder];
        }

        if (type === 'project') {
            const projectsStore = useProjectsStore();
            const { projects } = storeToRefs(projectsStore);
            return projects.value.find((project) => project.id === key)?.name;
        }
        if (type === 'user') {
            const memberStore = useMembersStore();
            const { members } = storeToRefs(memberStore);
            return members.value.find((member) => member.user_id === key)?.name;
        }
        if (type === 'task') {
            const taskStore = useTasksStore();
            const { tasks } = storeToRefs(taskStore);
            return tasks.value.find((task) => task.id === key)?.name;
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

    return {
        aggregatedGraphTimeEntries,
        fetchGraphReporting,
        fetchTableReporting,
        aggregatedTableTimeEntries,
        getNameForReportingRowEntry,
    };
});
