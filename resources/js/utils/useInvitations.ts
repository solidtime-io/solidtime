import { defineStore } from 'pinia';
import { api } from '@/packages/api/src';
import { computed, ref } from 'vue';
import type { CreateInvitationBody, Invitation } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';
import { fetchAllPages } from '@/utils/fetchAllPages';

export async function fetchAllInvitations(organizationId: string): Promise<Invitation[]> {
    return fetchAllPages((page) =>
        api.getInvitations({
            params: { organization: organizationId },
            queries: { page },
        })
    );
}

export const useInvitationsStore = defineStore('invitations', () => {
    const invitationsData = ref<Invitation[]>([]);
    const { handleApiRequestNotifications } = useNotificationsStore();

    async function fetchInvitations() {
        const organization = getCurrentOrganizationId();
        if (organization) {
            const data = await handleApiRequestNotifications(
                () => fetchAllInvitations(organization),
                undefined,
                'Failed to fetch invitations'
            );
            if (data) {
                invitationsData.value = data;
            }
        }
    }

    async function createInvitation(inviteBody: CreateInvitationBody): Promise<undefined> {
        const organization = getCurrentOrganizationId();
        if (organization) {
            await handleApiRequestNotifications(
                () =>
                    api.invite(inviteBody, {
                        params: {
                            organization: organization,
                        },
                    }),
                'User successfully invited',
                'Failed to invite user'
            );
            await fetchInvitations();
        }
    }

    const invitations = computed<Invitation[]>(() => {
        return invitationsData.value;
    });

    return { invitations, fetchInvitations, createInvitation };
});
