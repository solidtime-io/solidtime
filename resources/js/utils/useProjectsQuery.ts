import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import type { Project } from '@/packages/api/src';
import { computed } from 'vue';

export function useProjectsQuery() {
    const queryClient = useQueryClient();

    const query = useQuery({
        queryKey: computed(() => ['projects', getCurrentOrganizationId()]),
        queryFn: async () => {
            const organizationId = getCurrentOrganizationId();
            if (!organizationId) throw new Error('No organization');
            return api.getProjects({
                params: { organization: organizationId },
                queries: { archived: 'all' },
            });
        },
        enabled: () => !!getCurrentOrganizationId(),
        staleTime: 1000 * 30, // 30 seconds
    });

    const projects = computed<Project[]>(() => query.data.value?.data ?? []);

    const invalidateProjects = () => {
        queryClient.invalidateQueries({ queryKey: ['projects'] });
    };

    return {
        ...query,
        projects,
        invalidateProjects,
    };
}
