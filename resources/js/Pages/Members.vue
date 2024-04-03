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

const inviteMember = ref(false);

defineProps<{
    availableRoles: Role[];
}>();
</script>

<template>
    <AppLayout title="Members" data-testid="members_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-6">
                <h3
                    class="text-white font-bold text-base flex items-center space-x-2.5">
                    <UserGroupIcon
                        class="w-6 text-icon-default"></UserGroupIcon>
                    <span> Members </span>
                </h3>
                <TabBar>
                    <TabBarItem active>All</TabBarItem>
                    <TabBarItem>Active</TabBarItem>
                    <TabBarItem>Inactive</TabBarItem>
                </TabBar>
            </div>
            <SecondaryButton :icon="PlusIcon" @click="inviteMember = true"
                >Invite member</SecondaryButton
            >
            <MemberInviteModal
                :available-roles="availableRoles"
                v-model:show="inviteMember"></MemberInviteModal>
        </MainContainer>
        <MemberTable></MemberTable>
    </AppLayout>
</template>
