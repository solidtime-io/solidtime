import { defineStore } from 'pinia';
import { api } from '@/packages/api/src';
import type { CreateProjectBody, UpdateProjectBody } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';
import { useQueryClient } from '@tanstack/vue-query';

export const useProjectsStore = defineStore('projects', () => {
    const { handleApiRequestNotifications } = useNotificationsStore();
    const queryClient = useQueryClient();

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

            queryClient.invalidateQueries({ queryKey: ['projects'] });
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
            queryClient.invalidateQueries({ queryKey: ['projects'] });
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
            queryClient.invalidateQueries({ queryKey: ['projects'] });
        }
    }

    return {
        createProject,
        deleteProject,
        updateProject,
    };
});
