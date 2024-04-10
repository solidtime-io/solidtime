import { defineStore } from 'pinia';
import { api } from '../../../openapi.json.client';
import { computed, ref } from 'vue';
import type {
    CreateProjectMemberBody,
    ProjectMember,
    ProjectMemberResponse,
} from '@/utils/api';
import { getCurrentOrganizationId } from '@/utils/useUser';

export const useProjectMembersStore = defineStore('project-members', () => {
    const projectMemberResponse = ref<ProjectMemberResponse | null>(null);

    async function fetchProjectMembers(projectId: string) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            projectMemberResponse.value = await api.getProjectMembers({
                params: {
                    organization: organization,
                    project: projectId,
                },
            });
        }
    }

    async function createProjectMember(
        projectId: string,
        projectMemberBody: CreateProjectMemberBody
    ) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            await api.createProjectMember(projectMemberBody, {
                params: {
                    organization: organization,
                    project: projectId,
                },
            });
            await fetchProjectMembers(projectId);
        }
    }

    async function deleteProjectMember(
        projectId: string,
        projectMemberId: string
    ) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            await api.deleteProjectMember(
                {},
                {
                    params: {
                        organization: organizationId,
                        projectMember: projectMemberId,
                    },
                }
            );
            await fetchProjectMembers(projectId);
        }
    }

    const projectMembers = computed<ProjectMember[]>(
        () => projectMemberResponse.value?.data || []
    );

    return {
        projectMembers,
        fetchProjectMembers,
        createProjectMember,
        deleteProjectMember,
    };
});
