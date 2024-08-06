import { defineStore } from 'pinia';
import { api } from '@/utils/api';
import { computed, ref } from 'vue';
import type {
    Member,
    MemberIndexResponse,
    UpdateMemberBody,
} from '@/utils/api';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';

export type MemberBillableKey = 'default-rate' | 'custom-rate';

export const useMembersStore = defineStore('members', () => {
    const membersResponse = ref<MemberIndexResponse | null>(null);
    const { handleApiRequestNotifications } = useNotificationsStore();

    async function fetchMembers() {
        const organization = getCurrentOrganizationId();
        if (organization) {
            membersResponse.value = await handleApiRequestNotifications(
                () =>
                    api.getMembers({
                        params: {
                            organization: organization,
                        },
                    }),
                undefined,
                'Failed to fetch members'
            );
        }
    }

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
            await fetchMembers();
        }
    }

    async function updateMember(
        memberId: string,
        memberBody: UpdateMemberBody
    ) {
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
            await fetchMembers();
        }
    }

    const members = computed<Member[]>(() => {
        return membersResponse.value?.data || [];
    });

    return { members, fetchMembers, removeMember, updateMember };
});
