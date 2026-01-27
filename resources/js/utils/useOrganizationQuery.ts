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
    });

    const organization = computed(() => query.data.value?.data);

    return {
        ...query,
        organization,
    };
}
