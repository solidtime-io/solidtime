import { defineStore } from 'pinia';
import { api } from '../../../openapi.json.client';
import { computed, ref } from 'vue';
import type { Member, MemberIndexResponse } from '@/utils/api';
import { getCurrentOrganizationId } from '@/utils/useUser';

export const useMembersStore = defineStore('members', () => {
    const membersResponse = ref<MemberIndexResponse | null>(null);

    async function fetchMembers() {
        const organization = getCurrentOrganizationId();
        if (organization) {
            membersResponse.value = await api.getMembers({
                params: {
                    organization: organization,
                },
            });
        }
    }

    const members = computed<Member[]>(() => {
        return membersResponse.value?.data || [];
    });

    return { members, fetchMembers };
});
