import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import type { Goal } from '@/packages/api/src';
import { computed } from 'vue';
import { fetchAllPages } from '@/utils/fetchAllPages';

export type GoalArchivedFilter = 'true' | 'false' | 'all';

export async function fetchAllGoals(
    organizationId: string,
    archived: GoalArchivedFilter = 'false'
): Promise<Goal[]> {
    return fetchAllPages((page) =>
        api.getGoals({
            params: { organization: organizationId },
            queries: { page, archived },
        })
    );
}

export function useGoalsQuery(archived: GoalArchivedFilter = 'false') {
    const queryClient = useQueryClient();

    const query = useQuery({
        queryKey: computed(() => ['goals', getCurrentOrganizationId(), archived]),
        queryFn: async () => {
            const organizationId = getCurrentOrganizationId();
            if (!organizationId) throw new Error('No organization');
            const data = await fetchAllGoals(organizationId, archived);
            return { data };
        },
        enabled: () => !!getCurrentOrganizationId(),
        staleTime: 1000 * 30, // 30 seconds
        // Progress is computed server side, keep it fresh while the page is open
        refetchInterval: 1000 * 60,
    });

    const goals = computed<Goal[]>(() => query.data.value?.data ?? []);

    const invalidateGoals = () => {
        queryClient.invalidateQueries({ queryKey: ['goals'] });
    };

    return {
        ...query,
        goals,
        invalidateGoals,
    };
}
