import { defineStore } from 'pinia';
import { api } from '../../../openapi.json.client';
import { computed, ref } from 'vue';
import type { CreateProjectBody, Project, ProjectResponse } from '@/utils/api';
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

    async function createProject(projectBody: CreateProjectBody) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            await api.createProject(projectBody, {
                params: {
                    organization: organization,
                },
            });
            await fetchProjects();
        }
    }

    async function deleteProject(projectId: string) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            await api.deleteProject(
                {},
                {
                    params: {
                        organization: organizationId,
                        project: projectId,
                    },
                }
            );
            await fetchProjects();
        }
    }

    const projects = computed<Project[]>(
        () => projectResponse.value?.data || []
    );

    return { projects, fetchProjects, createProject, deleteProject };
});
