import { defineStore } from 'pinia';
import { api } from '@/packages/api/src';
import type { CreateProjectMemberBody, UpdateProjectMemberBody } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';
import { useQueryClient } from '@tanstack/vue-query';

export const useProjectMembersStore = defineStore('project-members', () => {
    const { handleApiRequestNotifications } = useNotificationsStore();
    const queryClient = useQueryClient();

    async function createProjectMember(
        projectId: string,
        projectMemberBody: CreateProjectMemberBody
    ) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            await handleApiRequestNotifications(
                () =>
                    api.createProjectMember(projectMemberBody, {
                        params: {
                            organization: organization,
                            project: projectId,
                        },
                    }),
                'Project member added successfully',
                'Failed to add project member'
            );
            queryClient.invalidateQueries({ queryKey: ['projectMembers', projectId] });
        }
    }

    async function updateProjectMember(
        projectMemberId: string,
        projectMemberBody: UpdateProjectMemberBody
    ) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            const response = await handleApiRequestNotifications(
                () =>
                    api.updateProjectMember(projectMemberBody, {
                        params: {
                            organization: organization,
                            projectMember: projectMemberId,
                        },
                    }),
                'Project member updated successfully',
                'Failed to update project member'
            );
            if (response?.data?.project_id) {
                queryClient.invalidateQueries({
                    queryKey: ['projectMembers', response.data.project_id],
                });
            }
        }
    }

    async function deleteProjectMember(projectId: string, projectMemberId: string) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            await handleApiRequestNotifications(
                () =>
                    api.deleteProjectMember(undefined, {
                        params: {
                            organization: organizationId,
                            projectMember: projectMemberId,
                        },
                    }),
                'Project member removed successfully',
                'Failed to remove project member'
            );
            queryClient.invalidateQueries({ queryKey: ['projectMembers', projectId] });
        }
    }

    return {
        createProjectMember,
        deleteProjectMember,
        updateProjectMember,
    };
});
