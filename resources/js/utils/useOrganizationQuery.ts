import { useQuery } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { computed } from 'vue';

export function useOrganizationQuery(organizationId: string) {
    const query = useQuery({
        queryKey: ['organization', organizationId],
        queryFn: () =>
            api.getOrganization({
                params: {
                    organization: organizationId,
                },
            }),
        staleTime: 1000 * 30,
    });

    const organization = computed(() => query.data.value?.data);

    return {
        ...query,
        organization,
    };
}
