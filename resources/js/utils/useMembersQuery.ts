import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import type { Member } from '@/packages/api/src';
import { computed } from 'vue';
import { fetchAllPages } from '@/utils/fetchAllPages';

export async function fetchAllMembers(organizationId: string): Promise<Member[]> {
    return fetchAllPages((page) =>
        api.getMembers({
            params: { organization: organizationId },
            queries: { page },
        })
    );
}

export function useMembersQuery() {
    const queryClient = useQueryClient();

    const query = useQuery({
        queryKey: computed(() => ['members', getCurrentOrganizationId()]),
        queryFn: async () => {
            const organizationId = getCurrentOrganizationId();
            if (!organizationId) throw new Error('No organization');
            const data = await fetchAllMembers(organizationId);
            return { data };
        },
        enabled: () => !!getCurrentOrganizationId(),
        staleTime: 1000 * 30, // 30 seconds
    });

    const members = computed<Member[]>(() => query.data.value?.data ?? []);

    const invalidateMembers = () => {
        queryClient.invalidateQueries({ queryKey: ['members'] });
    };

    return {
        ...query,
        members,
        invalidateMembers,
    };
}
