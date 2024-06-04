<script setup lang="ts">
import type { Client } from '@/utils/api';
import { computed, ref } from 'vue';
import { CheckCircleIcon } from '@heroicons/vue/20/solid';
import { useClientsStore } from '@/utils/useClients';
import { storeToRefs } from 'pinia';
import ClientMoreOptionsDropdown from '@/Components/Common/Client/ClientMoreOptionsDropdown.vue';
import { useProjectsStore } from '@/utils/useProjects';
import TableRow from '@/Components/TableRow.vue';
import ClientEditModal from '@/Components/Common/Client/ClientEditModal.vue';

const { projects } = storeToRefs(useProjectsStore());

const props = defineProps<{
    client: Client;
}>();

function deleteClient() {
    useClientsStore().deleteClient(props.client.id);
}

const projectCount = computed(() => {
    return projects.value.filter(
        (projects) => projects.client_id === props.client.id
    ).length;
});

const showEditModal = ref(false);
</script>

<template>
    <TableRow>
        <ClientEditModal
            :client="client"
            v-model:show="showEditModal"></ClientEditModal>
        <div
            class="whitespace-nowrap flex items-center space-x-5 3xl:pl-12 py-4 pr-3 text-sm font-medium text-white pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
            <span>
                {{ client.name }}
            </span>
            <span class="text-muted"> {{ projectCount }} Projects </span>
        </div>
        <div
            class="whitespace-nowrap px-3 py-4 text-sm text-muted flex space-x-1 items-center font-medium">
            <CheckCircleIcon class="w-5"></CheckCircleIcon>
            <span>Active</span>
        </div>
        <div
            class="relative whitespace-nowrap flex items-center pl-3 text-right text-sm font-medium sm:pr-0 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
            <ClientMoreOptionsDropdown
                :client="client"
                @edit="showEditModal = true"
                @delete="deleteClient"></ClientMoreOptionsDropdown>
        </div>
    </TableRow>
</template>

<style scoped></style>
