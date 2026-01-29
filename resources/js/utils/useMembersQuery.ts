import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import type { Member } from '@/packages/api/src';
import { computed } from 'vue';

export function useMembersQuery() {
    const queryClient = useQueryClient();

    const query = useQuery({
        queryKey: ['members'],
        queryFn: async () => {
            const organizationId = getCurrentOrganizationId();
            if (!organizationId) throw new Error('No organization');
            return api.getMembers({
                params: { organization: organizationId },
            });
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
