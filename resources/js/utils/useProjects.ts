import { defineStore } from 'pinia';
import { api } from '../../../openapi.json.client';
import { computed, ref } from 'vue';
import type { ZodiosResponseByAlias } from '@zodios/core';
import type { SolidTimeApi } from '@/utils/api';

type ProjectResponse = ZodiosResponseByAlias<SolidTimeApi, 'getProjects'>;
export type Project = ProjectResponse['data'][0];

export const useProjectsStore = defineStore('projects', () => {
    const projectResponse = ref<ProjectResponse | null>(null);

    async function fetchProjects(organizationId: string) {
        projectResponse.value = await api.getProjects({
            params: {
                organization: organizationId,
            },
        });
    }

    const projects = computed(() => projectResponse.value?.data || []);

    return { projects, fetchProjects };
});
