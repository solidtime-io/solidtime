import { defineStore } from 'pinia';
import { api } from '@/packages/api/src';
import { computed, ref } from 'vue';
import type {
    CreateProjectBody,
    Project,
    ProjectResponse,
    UpdateProjectBody,
} from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';
import { useQueryClient } from '@tanstack/vue-query';

export const useProjectsStore = defineStore('projects', () => {
    const projectResponse = ref<ProjectResponse | null>(null);
    const { handleApiRequestNotifications } = useNotificationsStore();
    const queryClient = useQueryClient();

    function invalidateProjectsQuery() {
        queryClient.invalidateQueries({ queryKey: ['projects'] });
    }
    async function fetchProjects() {
        const organization = getCurrentOrganizationId();
        if (organization) {
            projectResponse.value = await handleApiRequestNotifications(
                () =>
                    api.getProjects({
                        params: {
                            organization: organization,
                        },
                        queries: {
                            archived: 'all',
                        },
                    }),
                undefined,
                'Failed to fetch projects'
            );
        }
    }

    async function createProject(projectBody: CreateProjectBody) {
        const organization = getCurrentOrganizationId();

        if (organization) {
            const response = await handleApiRequestNotifications(
                () =>
                    api.createProject(projectBody, {
                        params: {
                            organization: organization,
                        },
                    }),
                'Project created successfully',
                'Failed to create project'
            );

            await fetchProjects();
            invalidateProjectsQuery();
            return response['data'];
        }
    }

    async function deleteProject(projectId: string) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            await handleApiRequestNotifications(
                () =>
                    api.deleteProject(undefined, {
                        params: {
                            organization: organizationId,
                            project: projectId,
                        },
                    }),
                'Project deleted successfully',
                'Failed to delete project'
            );
            await fetchProjects();
            invalidateProjectsQuery();
        }
    }

    async function updateProject(projectId: string, updateProjectBody: UpdateProjectBody) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            await handleApiRequestNotifications(
                () =>
                    api.updateProject(updateProjectBody, {
                        params: {
                            organization: organizationId,
                            project: projectId,
                        },
                    }),
                'Project updated successfully',
                'Failed to update project'
            );
            await fetchProjects();
            invalidateProjectsQuery();
        }
    }

    const projects = computed<Project[]>(() => projectResponse.value?.data || []);

    return {
        projects,
        fetchProjects,
        createProject,
        deleteProject,
        updateProject,
    };
});
