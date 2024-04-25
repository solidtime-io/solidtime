<script setup lang="ts">
import type { Member } from '@/utils/api';
import { CheckCircleIcon, UserCircleIcon } from '@heroicons/vue/20/solid';
import { useClientsStore } from '@/utils/useClients';
import MemberMoreOptionsDropdown from '@/Components/Common/Member/MemberMoreOptionsDropdown.vue';
import TableRow from '@/Components/TableRow.vue';
import { capitalizeFirstLetter } from '../../../utils/format';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { api } from '../../../../../openapi.json.client';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';
import { canInvitePlaceholderMembers } from '@/utils/permissions';

const props = defineProps<{
    member: Member;
}>();

function removeMember() {
    useClientsStore().deleteClient(props.member.id);
}

async function invitePlaceholder(id: string) {
    const { handleApiRequestNotifications } = useNotificationsStore();
    const organizationId = getCurrentOrganizationId();
    if (organizationId) {
        await handleApiRequestNotifications(
            api.invitePlaceholder(
                {},
                {
                    params: {
                        organization: organizationId,
                        membership: id,
                    },
                }
            ),
            'Member invited successfully',
            'Error inviting member'
        );
    }
}
</script>

<template>
    <TableRow>
        <div
            class="whitespace-nowrap flex items-center space-x-5 3xl:pl-12 py-4 pr-3 text-sm font-medium text-white pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
            <span>
                {{ member.name }}
            </span>
        </div>
        <div class="whitespace-nowrap px-3 py-4 text-sm text-muted">
            {{ member.email }}
        </div>
        <div class="whitespace-nowrap px-3 py-4 text-sm text-muted">
            {{ capitalizeFirstLetter(member.role) }}
        </div>
        <div class="whitespace-nowrap px-3 py-4 text-sm text-muted">
            {{ member.billable_rate ?? '--' }}
        </div>
        <div
            class="whitespace-nowrap px-3 py-4 text-sm text-muted flex space-x-1 items-center font-medium">
            <CheckCircleIcon
                v-if="member.is_placeholder === false"
                class="w-5"></CheckCircleIcon>
            <span v-if="member.is_placeholder === false">Active</span>
            <UserCircleIcon
                v-if="member.is_placeholder === true"
                class="w-5"></UserCircleIcon>
            <span v-if="member.is_placeholder === true">Inactive</span>
        </div>
        <div
            class="relative whitespace-nowrap flex items-center pl-3 text-right text-sm font-medium sm:pr-0 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
            <SecondaryButton
                v-if="
                    member.is_placeholder === true &&
                    canInvitePlaceholderMembers()
                "
                @click="invitePlaceholder(member.id)"
                size="small"
                >Invite</SecondaryButton
            >
            <MemberMoreOptionsDropdown
                :member="member"
                @delete="removeMember"></MemberMoreOptionsDropdown>
        </div>
    </TableRow>
</template>

<style scoped></style>
