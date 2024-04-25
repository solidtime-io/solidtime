<script setup lang="ts">
import MainContainer from '@/Pages/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { UserGroupIcon } from '@heroicons/vue/20/solid';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TabBar from '@/Components/Common/TabBar/TabBar.vue';
import TabBarItem from '@/Components/Common/TabBar/TabBarItem.vue';
import { ref } from 'vue';
import MemberTable from '@/Components/Common/Member/MemberTable.vue';
import MemberInviteModal from '@/Components/Common/Member/MemberInviteModal.vue';
import type { Role } from '@/types/jetstream';
import PageTitle from '@/Components/Common/PageTitle.vue';
import InvitationTable from '@/Components/Common/Invitation/InvitationTable.vue';
import { canCreateInvitations } from '@/utils/permissions';

const inviteMember = ref(false);

defineProps<{
    availableRoles: Role[];
}>();

const activeTab = ref<'all' | 'invitations'>('all');
function isActiveTab(tab: string) {
    return activeTab.value === tab;
}
</script>

<template>
    <AppLayout title="Members" data-testid="members_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-4 sm:space-x-6">
                <PageTitle :icon="UserGroupIcon" title="Members"> </PageTitle>
                <TabBar>
                    <TabBarItem
                        :active="isActiveTab('all')"
                        @click="activeTab = 'all'"
                        >All</TabBarItem
                    >
                    <TabBarItem
                        :active="isActiveTab('invitations')"
                        @click="activeTab = 'invitations'"
                        >Invitations</TabBarItem
                    >
                </TabBar>
            </div>
            <SecondaryButton
                v-if="canCreateInvitations()"
                :icon="PlusIcon"
                @click="inviteMember = true"
                >Invite member</SecondaryButton
            >
            <MemberInviteModal
                :available-roles="availableRoles"
                v-model:show="inviteMember"
                @close="activeTab = 'invitations'"></MemberInviteModal>
        </MainContainer>
        <MemberTable v-if="activeTab === 'all'"></MemberTable>
        <InvitationTable v-if="activeTab === 'invitations'"></InvitationTable>
    </AppLayout>
</template>
