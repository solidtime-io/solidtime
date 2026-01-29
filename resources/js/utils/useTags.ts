import { defineStore } from 'pinia';
import type { Tag } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { api } from '@/packages/api/src';
import { useNotificationsStore } from '@/utils/notification';
import { useQueryClient } from '@tanstack/vue-query';

export const useTagsStore = defineStore('tags', () => {
    const { handleApiRequestNotifications } = useNotificationsStore();
    const queryClient = useQueryClient();

    async function deleteTag(tagId: string) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            await handleApiRequestNotifications(
                () =>
                    api.deleteTag(undefined, {
                        params: {
                            organization: organizationId,
                            tag: tagId,
                        },
                    }),
                'Tag deleted successfully',
                'Failed to delete tag'
            );
            queryClient.invalidateQueries({ queryKey: ['tags'] });
        }
    }

    async function createTag(name: string): Promise<Tag | undefined> {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            const response = await handleApiRequestNotifications(
                () =>
                    api.createTag(
                        {
                            name: name,
                        },
                        {
                            params: {
                                organization: organizationId,
                            },
                        }
                    ),
                'Tag created successfully',
                'Failed to create tag'
            );
            if (response?.data) {
                queryClient.invalidateQueries({ queryKey: ['tags'] });
                return response.data;
            }
        } else {
            throw new Error('Failed to create tag because organization ID is missing.');
        }
    }

    return { createTag, deleteTag };
});
