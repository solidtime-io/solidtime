import { defineStore } from 'pinia';
import { api } from '../../../openapi.json.client';
import { computed, ref } from 'vue';
import type { Project, ProjectResponse } from '@/utils/api';
import { getCurrentOrganizationId } from '@/utils/useUser';

export const useProjectsStore = defineStore('projects', () => {
    const projectResponse = ref<ProjectResponse | null>(null);

    async function fetchProjects() {
        const organization = getCurrentOrganizationId();
        if (organization) {
            projectResponse.value = await api.getProjects({
                params: {
                    organization: organization,
                },
            });
        }
    }

    const projects = computed<Project[]>(
        () => projectResponse.value?.data || []
    );

    return { projects, fetchProjects };
});
