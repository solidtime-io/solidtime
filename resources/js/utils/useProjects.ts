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

export const useProjectsStore = defineStore('projects', () => {
    const projectResponse = ref<ProjectResponse | null>(null);
    const { handleApiRequestNotifications } = useNotificationsStore();
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
