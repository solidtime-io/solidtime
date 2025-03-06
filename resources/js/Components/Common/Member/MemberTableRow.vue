<script setup lang="ts">
import type { Member } from '@/packages/api/src';
import { CheckCircleIcon, UserCircleIcon } from '@heroicons/vue/20/solid';
import MemberMoreOptionsDropdown from '@/Components/Common/Member/MemberMoreOptionsDropdown.vue';
import TableRow from '@/Components/TableRow.vue';
import { capitalizeFirstLetter } from '../../../utils/format';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { api } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';
import { canInvitePlaceholderMembers } from '@/utils/permissions';
import { useMembersStore } from '@/utils/useMembers';
import {computed, ref} from 'vue';
import MemberEditModal from '@/Components/Common/Member/MemberEditModal.vue';
import { getOrganizationCurrencyString } from '@/utils/money';
import { formatCents } from '@/packages/ui/src/utils/money';
import MemberMergeModal from "@/Components/Common/Member/MemberMergeModal.vue";

const props = defineProps<{
    member: Member;
}>();

const showEditMemberModal = ref(false);
const showMergeMemberModal = ref(false);

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
                    undefined,
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

const userHasValidMailAddress = computed(() => {
    return !props.member.email.endsWith('@solidtime-import.test');
})

</script>

<template>
    <TableRow>
        <div
            class="whitespace-nowrap flex items-center space-x-5 py-4 pr-3 text-sm font-medium text-white pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
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
                member.billable_rate
                    ? formatCents(
                          member.billable_rate,
                          getOrganizationCurrencyString()
                      )
                    : '--'
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
                    canInvitePlaceholderMembers() &&
                    userHasValidMailAddress
                "
                size="small"
                @click="invitePlaceholder(member.id)"
                >Invite</SecondaryButton
            >
            <MemberMoreOptionsDropdown
                :member="member"
                @edit="showEditMemberModal = true"
                @delete="removeMember"
                @merge="showMergeMemberModal = true"
            ></MemberMoreOptionsDropdown>
        </div>
        <MemberEditModal
            v-model:show="showEditMemberModal"
            :member="member"></MemberEditModal>
        <MemberMergeModal v-model:show="showMergeMemberModal" :member="member"></MemberMergeModal>
    </TableRow>
</template>

<style scoped></style>
