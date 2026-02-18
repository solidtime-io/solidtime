import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import type { Client } from '@/packages/api/src';
import { computed } from 'vue';
import { fetchAllPages } from '@/utils/fetchAllPages';

export async function fetchAllClients(organizationId: string): Promise<Client[]> {
    return fetchAllPages((page) =>
        api.getClients({
            params: { organization: organizationId },
            queries: { archived: 'all', page },
        })
    );
}

export function useClientsQuery() {
    const queryClient = useQueryClient();

    const query = useQuery({
        queryKey: computed(() => ['clients', getCurrentOrganizationId()]),
        queryFn: async () => {
            const organizationId = getCurrentOrganizationId();
            if (!organizationId) throw new Error('No organization');
            const data = await fetchAllClients(organizationId);
            return { data };
        },
        enabled: () => !!getCurrentOrganizationId(),
        staleTime: 1000 * 30, // 30 seconds
    });

    const clients = computed<Client[]>(() => query.data.value?.data ?? []);

    const invalidateClients = () => {
        queryClient.invalidateQueries({ queryKey: ['clients'] });
    };

    return {
        ...query,
        clients,
        invalidateClients,
    };
}
