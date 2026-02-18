import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import type { Task } from '@/packages/api/src';
import { computed } from 'vue';
import { fetchAllPages } from '@/utils/fetchAllPages';

export async function fetchAllTasks(organizationId: string): Promise<Task[]> {
    return fetchAllPages((page) =>
        api.getTasks({
            params: { organization: organizationId },
            queries: { done: 'all', page },
        })
    );
}

export function useTasksQuery() {
    const queryClient = useQueryClient();

    const query = useQuery({
        queryKey: computed(() => ['tasks', getCurrentOrganizationId()]),
        queryFn: async () => {
            const organizationId = getCurrentOrganizationId();
            if (!organizationId) throw new Error('No organization');
            const data = await fetchAllTasks(organizationId);
            return { data };
        },
        enabled: () => !!getCurrentOrganizationId(),
        staleTime: 1000 * 30, // 30 seconds
    });

    const tasks = computed<Task[]>(() => query.data.value?.data ?? []);

    const invalidateTasks = () => {
        queryClient.invalidateQueries({ queryKey: ['tasks'] });
    };

    return {
        ...query,
        tasks,
        invalidateTasks,
    };
}
