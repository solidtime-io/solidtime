<script setup lang="ts">
import MainContainer from '@/Pages/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PlusIcon } from '@heroicons/vue/16/solid';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TabBar from '@/Components/Common/TabBar/TabBar.vue';
import TabBarItem from '@/Components/Common/TabBar/TabBarItem.vue';
import { UserCircleIcon } from '@heroicons/vue/20/solid';
import { onMounted, ref } from 'vue';
import { useClientsStore } from '@/utils/useClients';
import ClientTable from '@/Components/Common/Client/ClientTable.vue';
import ClientCreateModal from '@/Components/Common/Client/ClientCreateModal.vue';

onMounted(() => {
    useClientsStore().fetchClients();
});

const createClient = ref(false);
</script>

<template>
    <AppLayout title="Clients" data-testid="clients_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-6">
                <h3
                    class="text-white font-bold text-base flex items-center space-x-2.5">
                    <UserCircleIcon
                        class="w-6 text-icon-default"></UserCircleIcon>
                    <span> Clients </span>
                </h3>
                <TabBar>
                    <TabBarItem>All</TabBarItem>
                    <TabBarItem active>Active</TabBarItem>
                    <TabBarItem>Archived</TabBarItem>
                </TabBar>
            </div>
            <SecondaryButton :icon="PlusIcon" @click="createClient = true"
                >Create Client</SecondaryButton
            >
            <ClientCreateModal v-model:show="createClient"></ClientCreateModal>
        </MainContainer>
        <ClientTable></ClientTable>
    </AppLayout>
</template>
