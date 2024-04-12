import { defineStore } from 'pinia';
import { api } from '../../../openapi.json.client';
import { computed, ref } from 'vue';
import type { Member, MemberIndexResponse } from '@/utils/api';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';

export const useMembersStore = defineStore('members', () => {
    const membersResponse = ref<MemberIndexResponse | null>(null);
    const { handleApiRequestNotifications } = useNotificationsStore();

    async function fetchMembers() {
        const organization = getCurrentOrganizationId();
        if (organization) {
            membersResponse.value = await handleApiRequestNotifications(
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

    const members = computed<Member[]>(() => {
        return membersResponse.value?.data || [];
    });

    return { members, fetchMembers };
});
