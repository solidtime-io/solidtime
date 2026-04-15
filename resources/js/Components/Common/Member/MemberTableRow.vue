<script setup lang="ts">
import type { Member, Organization } from '@/packages/api/src';
import { api } from '@/packages/api/src';
import { CheckCircleIcon, UserCircleIcon } from '@heroicons/vue/24/outline';
import {
    PencilSquareIcon,
    TrashIcon,
    ArrowDownOnSquareStackIcon,
    UserCircleIcon as UserCircleIconSolid,
} from '@heroicons/vue/20/solid';
import MemberMoreOptionsDropdown from '@/Components/Common/Member/MemberMoreOptionsDropdown.vue';
import TableRow from '@/Components/TableRow.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';
import {
    canInvitePlaceholderMembers,
    canUpdateMembers,
    canDeleteMembers,
    canMergeMembers,
    canMakeMembersPlaceholders,
} from '@/utils/permissions';
import { computed, type ComputedRef, inject, ref } from 'vue';
import MemberEditModal from '@/Components/Common/Member/MemberEditModal.vue';
import MemberMergeModal from '@/Components/Common/Member/MemberMergeModal.vue';
import MemberMakePlaceholderModal from '@/Components/Common/Member/MemberMakePlaceholderModal.vue';
import MemberDeleteModal from '@/Components/Common/Member/MemberDeleteModal.vue';
import { capitalizeFirstLetter } from '../../../utils/format';
import { formatCents } from '../../../packages/ui/src/utils/money';
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuTrigger,
} from '@/packages/ui/src';

const props = defineProps<{
    member: Member;
}>();

const organization = inject<ComputedRef<Organization>>('organization');

const showEditMemberModal = ref(false);
const showMergeMemberModal = ref(false);
const showMakeMemberPlaceholderModal = ref(false);
const showDeleteMemberModal = ref(false);

function removeMember() {
    showDeleteMemberModal.value = true;
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
    <ContextMenu>
        <ContextMenuTrigger as-child>
            <TableRow>
                <div
                    class="whitespace-nowrap flex items-center space-x-5 py-4 pr-3 text-sm font-medium text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
                    <span>
                        {{ member.name }}
                    </span>
                </div>
                <div class="whitespace-nowrap px-3 py-4 text-sm text-text-primary">
                    {{ member.email }}
                </div>
                <div class="whitespace-nowrap px-3 py-4 text-sm text-text-primary">
                    {{ capitalizeFirstLetter(member.role) }}
                </div>
                <div class="whitespace-nowrap px-3 py-4 text-sm text-text-primary">
                    <span v-if="member.billable_rate">
                        {{
                            formatCents(
                                member.billable_rate,
                                organization?.currency,
                                organization?.currency_format,
                                organization?.currency_symbol,
                                organization?.number_format
                            )
                        }}
                    </span>
                    <span v-else class="text-text-tertiary"> -- </span>
                </div>
                <div
                    class="whitespace-nowrap px-3 py-4 text-sm text-text-primary flex space-x-1.5 items-center">
                    <template v-if="member.is_placeholder === false">
                        <CheckCircleIcon class="w-4 text-icon-default"></CheckCircleIcon>
                        <span>Active</span>
                    </template>
                    <template v-else>
                        <UserCircleIcon class="w-4 text-icon-default"></UserCircleIcon>
                        <span>Inactive</span>
                    </template>
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
                <MemberEditModal
                    v-model:show="showEditMemberModal"
                    :member="member"></MemberEditModal>
                <MemberMergeModal
                    v-model:show="showMergeMemberModal"
                    :member="member"></MemberMergeModal>
                <MemberMakePlaceholderModal
                    v-model:show="showMakeMemberPlaceholderModal"
                    :member="member"></MemberMakePlaceholderModal>
                <MemberDeleteModal
                    v-model:show="showDeleteMemberModal"
                    :member="member"></MemberDeleteModal>
            </TableRow>
        </ContextMenuTrigger>
        <ContextMenuContent class="min-w-[160px]">
            <ContextMenuItem
                v-if="canUpdateMembers()"
                class="space-x-3"
                @select="showEditMemberModal = true">
                <PencilSquareIcon class="w-4 h-4 text-icon-default" />
                <span>Edit</span>
            </ContextMenuItem>
            <ContextMenuItem
                v-if="member.role === 'placeholder' && canMergeMembers()"
                class="space-x-3"
                @select="showMergeMemberModal = true">
                <ArrowDownOnSquareStackIcon class="w-4 h-4 text-icon-default" />
                <span>Merge</span>
            </ContextMenuItem>
            <ContextMenuItem
                v-if="member.role !== 'placeholder' && canMakeMembersPlaceholders()"
                class="space-x-3"
                @select="showMakeMemberPlaceholderModal = true">
                <UserCircleIconSolid class="w-4 h-4 text-icon-default" />
                <span>Deactivate</span>
            </ContextMenuItem>
            <ContextMenuSeparator v-if="canDeleteMembers()" />
            <ContextMenuItem
                v-if="canDeleteMembers()"
                class="space-x-3 text-destructive"
                @select="showDeleteMemberModal = true">
                <TrashIcon class="w-4 h-4 text-icon-default" />
                <span>Delete</span>
            </ContextMenuItem>
        </ContextMenuContent>
    </ContextMenu>
</template>

<style scoped></style>
