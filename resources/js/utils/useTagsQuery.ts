import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import type { Tag } from '@/packages/api/src';
import { computed } from 'vue';
import { fetchAllPages } from '@/utils/fetchAllPages';

export async function fetchAllTags(organizationId: string): Promise<Tag[]> {
    return fetchAllPages((page) =>
        api.getTags({
            params: { organization: organizationId },
            queries: { page },
        })
    );
}

export function useTagsQuery() {
    const queryClient = useQueryClient();

    const query = useQuery({
        queryKey: computed(() => ['tags', getCurrentOrganizationId()]),
        queryFn: async () => {
            const organizationId = getCurrentOrganizationId();
            if (!organizationId) throw new Error('No organization');
            const data = await fetchAllTags(organizationId);
            return { data };
        },
        enabled: () => !!getCurrentOrganizationId(),
        staleTime: 1000 * 30, // 30 seconds
    });

    const tags = computed<Tag[]>(() => query.data.value?.data ?? []);

    const invalidateTags = () => {
        queryClient.invalidateQueries({ queryKey: ['tags'] });
    };

    return {
        ...query,
        tags,
        invalidateTags,
    };
}
