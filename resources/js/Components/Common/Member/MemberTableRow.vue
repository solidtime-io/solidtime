<script setup lang="ts">
import type { Member, Organization } from '@/packages/api/src';
import { api } from '@/packages/api/src';
import { CheckCircleIcon, UserCircleIcon } from '@heroicons/vue/20/solid';
import MemberMoreOptionsDropdown from '@/Components/Common/Member/MemberMoreOptionsDropdown.vue';
import TableRow from '@/Components/TableRow.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';
import { canInvitePlaceholderMembers } from '@/utils/permissions';
import { computed, type ComputedRef, inject, ref } from 'vue';
import MemberEditModal from '@/Components/Common/Member/MemberEditModal.vue';
import MemberMergeModal from '@/Components/Common/Member/MemberMergeModal.vue';
import MemberMakePlaceholderModal from '@/Components/Common/Member/MemberMakePlaceholderModal.vue';
import MemberDeleteModal from '@/Components/Common/Member/MemberDeleteModal.vue';
import { capitalizeFirstLetter } from '../../../utils/format';
import { formatCents } from '../../../packages/ui/src/utils/money';
import { useMembersStore } from '@/utils/useMembers';

const props = defineProps<{
    member: Member;
}>();

const organization = inject<ComputedRef<Organization>>('organization');
const memberStore = useMembersStore();

const showEditMemberModal = ref(false);
const showMergeMemberModal = ref(false);
const showMakeMemberPlaceholderModal = ref(false);
const showDeleteMemberModal = ref(false);

function removeMember() {
    showDeleteMemberModal.value = true;
    memberStore.fetchMembers();
}

async function invitePlaceholder(id: string) {
    const { handleApiRequestNotifications } = useNotificationsStore();
    const organizationId = getCurrentOrganizationId();
    if (organizationId) {
        await handleApiRequestNotifications(
            () =>
                api.invitePlaceholder(undefined, {
                    params: {
                        organization: organizationId,
                        member: id,
                    },
                }),
            'Member invited successfully',
            'Error inviting member'
        );
    }
}

const userHasValidMailAddress = computed(() => {
    return !props.member.email.endsWith('@solidtime-import.test');
});
</script>

<template>
    <TableRow>
        <div
            class="whitespace-nowrap flex items-center space-x-5 py-4 pr-3 text-sm font-medium text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
            <span>
                {{ member.name }}
            </span>
        </div>
        <div class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary">
            {{ member.email }}
        </div>
        <div class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary">
            {{ capitalizeFirstLetter(member.role) }}
        </div>
        <div class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary">
            {{
                member.billable_rate
                    ? formatCents(
                          member.billable_rate,
                          organization?.currency,
                          organization?.currency_format,
                          organization?.currency_symbol,
                          organization?.number_format
                      )
                    : '--'
            }}
        </div>
        <div
            class="whitespace-nowrap px-3 py-4 text-sm text-text-secondary flex space-x-1 items-center font-medium">
            <CheckCircleIcon v-if="member.is_placeholder === false" class="w-5"></CheckCircleIcon>
            <span v-if="member.is_placeholder === false">Active</span>
            <UserCircleIcon v-if="member.is_placeholder === true" class="w-5"></UserCircleIcon>
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
                >Invite
            </SecondaryButton>
            <MemberMoreOptionsDropdown
                :member="member"
                @edit="showEditMemberModal = true"
                @delete="removeMember"
                @merge="showMergeMemberModal = true"
                @make-placeholder="
                    showMakeMemberPlaceholderModal = true
                "></MemberMoreOptionsDropdown>
        </div>
        <MemberEditModal v-model:show="showEditMemberModal" :member="member"></MemberEditModal>
        <MemberMergeModal v-model:show="showMergeMemberModal" :member="member"></MemberMergeModal>
        <MemberMakePlaceholderModal
            v-model:show="showMakeMemberPlaceholderModal"
            :member="member"></MemberMakePlaceholderModal>
        <MemberDeleteModal
            v-model:show="showDeleteMemberModal"
            :member="member"></MemberDeleteModal>
    </TableRow>
</template>

<style scoped></style>
