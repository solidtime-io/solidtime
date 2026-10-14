import { useQuery, useQueryClient, type QueryClient } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import type { Goal } from '@/packages/api/src';
import { computed } from 'vue';
import { fetchAllPages } from '@/utils/fetchAllPages';
import { canCreateMoreGoals, goalLimit } from '@/utils/billing';

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

export function goalsQueryOptions(archived: GoalArchivedFilter = 'false') {
    return {
        queryKey: ['goals', getCurrentOrganizationId(), archived],
        queryFn: async () => {
            const organizationId = getCurrentOrganizationId();
            if (!organizationId) throw new Error('No organization');
            const data = await fetchAllGoals(organizationId, archived);
            return { data };
        },
        staleTime: 1000 * 30, // 30 seconds
    };
}

/**
 * Checks the goal limit outside of the goals page, where the goals are not loaded yet.
 * Archived goals count towards the limit, so all goals are counted.
 */
export async function fetchCanCreateMoreGoals(queryClient: QueryClient): Promise<boolean> {
    if (goalLimit() === null) {
        return true;
    }
    const { data } = await queryClient.fetchQuery(goalsQueryOptions('all'));
    return canCreateMoreGoals(data.length);
}

export function useGoalsQuery(archived: GoalArchivedFilter = 'false') {
    const queryClient = useQueryClient();

    const query = useQuery({
        ...goalsQueryOptions(archived),
        queryKey: computed(() => goalsQueryOptions(archived).queryKey),
        enabled: () => !!getCurrentOrganizationId(),
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
