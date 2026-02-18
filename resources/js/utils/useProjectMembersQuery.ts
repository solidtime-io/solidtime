import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import type { ProjectMember } from '@/packages/api/src';
import { computed, type Ref } from 'vue';

export function useProjectMembersQuery(projectId: Ref<string | null> | string) {
    const queryClient = useQueryClient();

    const projectIdValue = computed(() => {
        return typeof projectId === 'string' ? projectId : projectId.value;
    });

    const query = useQuery({
        queryKey: computed(() => [
            'projectMembers',
            getCurrentOrganizationId(),
            projectIdValue.value,
        ]),
        queryFn: async () => {
            const organizationId = getCurrentOrganizationId();
            const pid = projectIdValue.value;
            if (!organizationId || !pid) throw new Error('No organization or project');
            return api.getProjectMembers({
                params: { organization: organizationId, project: pid },
            });
        },
        enabled: () => !!getCurrentOrganizationId() && !!projectIdValue.value,
        staleTime: 1000 * 30, // 30 seconds
    });

    const projectMembers = computed<ProjectMember[]>(() => query.data.value?.data ?? []);

    const invalidateProjectMembers = () => {
        queryClient.invalidateQueries({
            queryKey: ['projectMembers', getCurrentOrganizationId(), projectIdValue.value],
        });
    };

    return {
        ...query,
        projectMembers,
        invalidateProjectMembers,
    };
}
