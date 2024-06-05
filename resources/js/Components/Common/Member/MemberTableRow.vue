<script setup lang="ts">
import type { Member } from '@/utils/api';
import { CheckCircleIcon, UserCircleIcon } from '@heroicons/vue/20/solid';
import MemberMoreOptionsDropdown from '@/Components/Common/Member/MemberMoreOptionsDropdown.vue';
import TableRow from '@/Components/TableRow.vue';
import { capitalizeFirstLetter } from '../../../utils/format';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { api } from '../../../../../openapi.json.client';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';
import { canInvitePlaceholderMembers } from '@/utils/permissions';
import { useMembersStore } from '@/utils/useMembers';
import { ref } from 'vue';
import MemberEditModal from '@/Components/Common/Member/MemberEditModal.vue';
import { formatCents } from '../../../utils/money';

const props = defineProps<{
    member: Member;
}>();

const showEditMemberModal = ref(false);

function removeMember() {
    useMembersStore().removeMember(props.member.id);
}

async function invitePlaceholder(id: string) {
    const { handleApiRequestNotifications } = useNotificationsStore();
    const organizationId = getCurrentOrganizationId();
    if (organizationId) {
        await handleApiRequestNotifications(
            () =>
                api.invitePlaceholder(
                    {},
                    {
                        params: {
                            organization: organizationId,
                            member: id,
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
            {{
                member.billable_rate ? formatCents(member.billable_rate) : '--'
            }}
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
                @edit="showEditMemberModal = true"
                @delete="removeMember"></MemberMoreOptionsDropdown>
        </div>
        <MemberEditModal
            :member="member"
            v-model:show="showEditMemberModal"></MemberEditModal>
    </TableRow>
</template>

<style scoped></style>
