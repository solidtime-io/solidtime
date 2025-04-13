<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PlusIcon } from '@heroicons/vue/16/solid';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { UserCircleIcon } from '@heroicons/vue/20/solid';
import { computed, onMounted, ref } from 'vue';
import { useClientsStore } from '@/utils/useClients';
import ClientTable from '@/Components/Common/Client/ClientTable.vue';
import ClientCreateModal from '@/Components/Common/Client/ClientCreateModal.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import { canCreateClients } from '@/utils/permissions';
import TabBarItem from '@/Components/Common/TabBar/TabBarItem.vue';
import TabBar from '@/Components/Common/TabBar/TabBar.vue';
import { storeToRefs } from 'pinia';

onMounted(() => {
    useClientsStore().fetchClients();
});

const activeTab = ref<'active' | 'archived'>('active');

const createClient = ref(false);

const { clients } = storeToRefs(useClientsStore());

const shownClients = computed(() => {
    return clients.value.filter((client) => {
        if (activeTab.value === 'active') {
            return !client.is_archived;
        }
        return client.is_archived;
    });
});
</script>

<template>
    <AppLayout title="Clients" data-testid="clients_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-3 sm:space-x-6">
                <PageTitle :icon="UserCircleIcon" title="Clients"> </PageTitle>
                <TabBar
                v-model="activeTab"
                >
                    <TabBarItem
                        value="active"
                        >Active</TabBarItem
                    >
                    <TabBarItem
                        value="archived"
                        >
                        Archived
                    </TabBarItem>
                </TabBar>
            </div>
            <SecondaryButton
                v-if="canCreateClients()"
                :icon="PlusIcon"
                @click="createClient = true"
                >Create Client</SecondaryButton
            >
            <ClientCreateModal v-model:show="createClient"></ClientCreateModal>
        </MainContainer>
        <ClientTable :clients="shownClients"></ClientTable>
    </AppLayout>
</template>
