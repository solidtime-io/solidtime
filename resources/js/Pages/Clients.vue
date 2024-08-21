<script setup lang="ts">
import MainContainer from '@/Pages/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PlusIcon } from '@heroicons/vue/16/solid';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { UserCircleIcon } from '@heroicons/vue/20/solid';
import { onMounted, ref } from 'vue';
import { useClientsStore } from '@/utils/useClients';
import ClientTable from '@/Components/Common/Client/ClientTable.vue';
import ClientCreateModal from '@/Components/Common/Client/ClientCreateModal.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import { canCreateClients } from '@/utils/permissions';

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
                <PageTitle :icon="UserCircleIcon" title="Clients"> </PageTitle>
            </div>
            <SecondaryButton
                v-if="canCreateClients()"
                :icon="PlusIcon"
                @click="createClient = true"
                >Create Client</SecondaryButton
            >
            <ClientCreateModal v-model:show="createClient"></ClientCreateModal>
        </MainContainer>
        <ClientTable></ClientTable>
    </AppLayout>
</template>
