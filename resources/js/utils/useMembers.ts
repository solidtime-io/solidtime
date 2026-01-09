import { defineStore } from 'pinia';
import { api } from '@/packages/api/src';
import type { UpdateMemberBody } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';
import { useQueryClient } from '@tanstack/vue-query';

export type MemberBillableKey = 'default-rate' | 'custom-rate';

export const useMembersStore = defineStore('members', () => {
    const { handleApiRequestNotifications } = useNotificationsStore();
    const queryClient = useQueryClient();

    async function removeMember(membershipId: string) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            await handleApiRequestNotifications(
                () =>
                    api.removeMember(undefined, {
                        params: {
                            organization: organization,
                            member: membershipId,
                        },
                    }),
                'Member deleted successfully',
                'Failed to delete member'
            );
            queryClient.invalidateQueries({ queryKey: ['members'] });
        }
    }

    async function updateMember(memberId: string, memberBody: UpdateMemberBody) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            await handleApiRequestNotifications(
                () =>
                    api.updateMember(memberBody, {
                        params: {
                            organization: organization,
                            member: memberId,
                        },
                    }),
                'Member updated successfully',
                'Failed to update member'
            );
            queryClient.invalidateQueries({ queryKey: ['members'] });
        }
    }

    return { removeMember, updateMember };
});
