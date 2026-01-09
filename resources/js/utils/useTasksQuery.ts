import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import type { Task } from '@/packages/api/src';
import { computed } from 'vue';

export function useTasksQuery() {
    const queryClient = useQueryClient();

    const query = useQuery({
        queryKey: ['tasks'],
        queryFn: async () => {
            const organizationId = getCurrentOrganizationId();
            if (!organizationId) throw new Error('No organization');
            return api.getTasks({
                params: { organization: organizationId },
                queries: { done: 'all' },
            });
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
