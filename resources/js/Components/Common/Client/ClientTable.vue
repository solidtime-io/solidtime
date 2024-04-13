<script setup lang="ts">
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { UserCircleIcon } from '@heroicons/vue/24/solid';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useClientsStore } from '@/utils/useClients';
import ClientTableRow from '@/Components/Common/Client/ClientTableRow.vue';
import ClientCreateModal from '@/Components/Common/Client/ClientCreateModal.vue';
import ClientTableHeading from '@/Components/Common/Client/ClientTableHeading.vue';

const { clients } = storeToRefs(useClientsStore());

const createClient = ref(false);
</script>

<template>
    <ClientCreateModal v-model:show="createClient"></ClientCreateModal>
    <div class="flow-root max-w-[100vw] overflow-x-auto">
        <div class="inline-block min-w-full align-middle">
            <div
                data-testid="client_table"
                class="grid min-w-full"
                style="grid-template-columns: 1fr 150px 80px">
                <ClientTableHeading></ClientTableHeading>
                <div
                    class="col-span-2 py-24 text-center"
                    v-if="clients.length === 0">
                    <UserCircleIcon
                        class="w-8 text-icon-default inline pb-2"></UserCircleIcon>
                    <h3 class="text-white font-semibold">No clients found</h3>
                    <p class="pb-5">Create your first client now!</p>
                    <SecondaryButton
                        @click="createClient = true"
                        :icon="PlusIcon"
                        >Create your First Client
                    </SecondaryButton>
                </div>
                <template v-for="client in clients" :key="client.id">
                    <ClientTableRow :client="client"></ClientTableRow>
                </template>
            </div>
        </div>
    </div>
</template>
