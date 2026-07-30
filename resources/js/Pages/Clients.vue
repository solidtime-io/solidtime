<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PlusIcon } from '@heroicons/vue/16/solid';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { UserCircleIcon } from '@heroicons/vue/20/solid';
import { computed, ref } from 'vue';
import { useClientsQuery } from '@/utils/useClientsQuery';
import ClientTable from '@/Components/Common/Client/ClientTable.vue';
import ClientCreateModal from '@/Components/Common/Client/ClientCreateModal.vue';
import ClientDetailPane from '@/Components/Common/Client/ClientDetailPane.vue';
import type { Client } from '@/packages/api/src';
import PageTitle from '@/Components/Common/PageTitle.vue';
import { canCreateClients } from '@/utils/permissions';
import { TabBar, TabBarItem } from '@/packages/ui/src';
import { useStorage } from '@vueuse/core';
import type { SortColumn, SortDirection } from '@/Components/Common/Client/ClientTable.vue';

const { clients } = useClientsQuery();

const activeTab = ref<'active' | 'archived'>('active');

const createClient = ref(false);

interface ClientTableState {
    sortColumn: SortColumn;
    sortDirection: SortDirection;
}

const tableState = useStorage<ClientTableState>(
    'client-table-state',
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

const shownClients = computed(() => {
    return clients.value.filter((client) => {
        if (activeTab.value === 'active') {
            return !client.is_archived;
        }
        return client.is_archived;
    });
});

const selectedClientId = ref<string | null>(null);

// Resolve against the full client list so the pane stays open (with fresh data)
// when an archive/unarchive moves the client to the other tab.
const selectedClient = computed(() => {
    return clients.value.find((client) => client.id === selectedClientId.value);
});

function handleSelect(client: Client) {
    selectedClientId.value = selectedClientId.value === client.id ? null : client.id;
}
</script>

<template>
    <AppLayout title="Clients" data-testid="clients_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-3 sm:space-x-6">
                <PageTitle :icon="UserCircleIcon" title="Clients"> </PageTitle>
                <TabBar v-model="activeTab">
                    <TabBarItem value="active">Active</TabBarItem>
                    <TabBarItem value="archived"> Archived </TabBarItem>
                </TabBar>
            </div>
            <SecondaryButton v-if="canCreateClients()" :icon="PlusIcon" @click="createClient = true"
                >Create Client</SecondaryButton
            >
            <ClientCreateModal v-model:show="createClient"></ClientCreateModal>
        </MainContainer>
        <div class="flex items-stretch">
            <div class="flex-1 min-w-0">
                <ClientTable
                    :clients="shownClients"
                    :sort-column="tableState.sortColumn"
                    :sort-direction="tableState.sortDirection"
                    :selected-client-id="selectedClientId"
                    @sort="handleSort"
                    @select="handleSelect"></ClientTable>
            </div>
            <ClientDetailPane
                v-if="selectedClient"
                :client="selectedClient"
                @close="selectedClientId = null"></ClientDetailPane>
        </div>
    </AppLayout>
</template>
