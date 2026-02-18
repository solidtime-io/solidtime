<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { UserGroupIcon } from '@heroicons/vue/20/solid';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import TabBar from '@/Components/Common/TabBar/TabBar.vue';
import TabBarItem from '@/Components/Common/TabBar/TabBarItem.vue';
import { ref } from 'vue';
import MemberTable from '@/Components/Common/Member/MemberTable.vue';
import MemberInviteModal from '@/Components/Common/Member/MemberInviteModal.vue';
import type { Role } from '@/types/jetstream';
import PageTitle from '@/Components/Common/PageTitle.vue';
import InvitationTable from '@/Components/Common/Invitation/InvitationTable.vue';
import { canCreateInvitations } from '@/utils/permissions';
import { useStorage } from '@vueuse/core';
import type { SortColumn, SortDirection } from '@/Components/Common/Member/MemberTable.vue';

const inviteMember = ref(false);

defineProps<{
    availableRoles: Role[];
}>();

const activeTab = ref<'all' | 'invitations'>('all');

interface MemberTableState {
    sortColumn: SortColumn;
    sortDirection: SortDirection;
}

const tableState = useStorage<MemberTableState>(
    'member-table-state',
    {
        sortColumn: 'name',
        sortDirection: 'asc',
    },
    undefined,
    { mergeDefaults: true }
);

function handleSort(column: SortColumn, direction: SortDirection) {
    tableState.value.sortColumn = column;
    tableState.value.sortDirection = direction;
}
</script>

<template>
    <AppLayout title="Members" data-testid="members_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-4 sm:space-x-6">
                <PageTitle :icon="UserGroupIcon" title="Members"> </PageTitle>
                <TabBar v-model="activeTab">
                    <TabBarItem value="all">All</TabBarItem>
                    <TabBarItem value="invitations">Invitations</TabBarItem>
                </TabBar>
            </div>
            <SecondaryButton
                v-if="canCreateInvitations()"
                :icon="PlusIcon"
                @click="inviteMember = true"
                >Invite member</SecondaryButton
            >
            <MemberInviteModal
                v-model:show="inviteMember"
                :available-roles="availableRoles"
                @close="activeTab = 'invitations'"></MemberInviteModal>
        </MainContainer>
        <MemberTable
            v-if="activeTab === 'all'"
            :sort-column="tableState.sortColumn"
            :sort-direction="tableState.sortDirection"
            @sort="handleSort"></MemberTable>
        <InvitationTable v-if="activeTab === 'invitations'"></InvitationTable>
    </AppLayout>
</template>
