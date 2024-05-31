import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { Tag } from '@/utils/api';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { api } from '../../../openapi.json.client';
import { useNotificationsStore } from '@/utils/notification';

export const useTagsStore = defineStore('tags', () => {
    const tags = ref<Tag[]>([]);
    const { handleApiRequestNotifications } = useNotificationsStore();
    async function fetchTags() {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            const response = await handleApiRequestNotifications(
                () =>
                    api.getTags({
                        params: {
                            organization: organizationId,
                        },
                    }),
                undefined,
                'Failed to fetch tags'
            );
            if (response?.data) {
                tags.value = response.data;
            }
        } else {
            throw new Error(
                'Failed to fetch current tags because organization ID is missing.'
            );
        }
    }

    async function deleteTag(tagId: string) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            await handleApiRequestNotifications(
                () =>
                    api.deleteTag(
                        {},
                        {
                            params: {
                                organization: organizationId,
                                tag: tagId,
                            },
                        }
                    ),
                'Tag deleted successfully',
                'Failed to delete tag'
            );
            await fetchTags();
        }
    }

    async function createTag(name: string) {
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
                tags.value.unshift(response.data);
                return response.data;
            }
        } else {
            throw new Error(
                'Failed to create tag because organization ID is missing.'
            );
        }
    }

    return { tags, fetchTags, createTag, deleteTag };
});
