import { defineStore } from 'pinia';
import { ref } from 'vue';
import type { ZodiosResponseByAlias } from '@zodios/core';
import type { SolidTimeApi } from '@/utils/api';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { api } from '../../../openapi.json.client';

type TagIndexResponse = ZodiosResponseByAlias<SolidTimeApi, 'getTags'>;
export type Tag = TagIndexResponse['data'][0];

export const useTagsStore = defineStore('tags', () => {
    const tags = ref<Tag[]>([]);

    async function fetchTags() {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            const response = await api.getTags({
                params: {
                    organization: organizationId,
                },
            });
            tags.value = response.data;
        } else {
            throw new Error(
                'Failed to fetch current tags because organization ID is missing.'
            );
        }
    }

    async function createTag(name: string) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            const response = await api.createTag(
                {
                    name: name,
                },
                {
                    params: {
                        organization: organizationId,
                    },
                }
            );
            tags.value.unshift(response.data);
            return response.data;
        } else {
            throw new Error(
                'Failed to create tag because organization ID is missing.'
            );
        }
    }

    return { tags, fetchTags, createTag };
});
