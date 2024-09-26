<script setup lang="ts">
import {
    ArchiveBoxIcon,
    PencilSquareIcon,
    TrashIcon,
} from '@heroicons/vue/20/solid';
import type { Client } from '@/packages/api/src';
import { canDeleteClients, canUpdateClients } from '@/utils/permissions';
import MoreOptionsDropdown from '@/packages/ui/src/MoreOptionsDropdown.vue';
import ClientEditModal from '@/Components/Common/Client/ClientEditModal.vue';
import { ref } from 'vue';

const emit = defineEmits<{
    delete: [];
    archive: [];
}>();
const props = defineProps<{
    client: Client;
}>();
const showEditModal = ref(false);
</script>

<template>
    <ClientEditModal
        :client="client"
        v-model:show="showEditModal"></ClientEditModal>
    <MoreOptionsDropdown :label="'Actions for Client ' + props.client.name">
        <div class="min-w-[150px]">
            <button
                v-if="canUpdateClients()"
                @click="showEditModal = true"
                :aria-label="'Edit Client ' + props.client.name"
                data-testid="client_edit"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
                <PencilSquareIcon
                    class="w-5 text-icon-active"></PencilSquareIcon>
                <span>Edit</span>
            </button>
            <button
                @click.prevent="emit('archive')"
                v-if="canUpdateClients()"
                :aria-label="'Archive Client ' + props.client.name"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
                <ArchiveBoxIcon class="w-5 text-icon-active"></ArchiveBoxIcon>
                <span>{{ client.is_archived ? 'Unarchive' : 'Archive' }}</span>
            </button>
            <button
                v-if="canDeleteClients()"
                @click="emit('delete')"
                :aria-label="'Delete Client ' + props.client.name"
                data-testid="client_delete"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
                <TrashIcon class="w-5 text-icon-active"></TrashIcon>
                <span>Delete</span>
            </button>
        </div>
    </MoreOptionsDropdown>
</template>

<style scoped></style>
