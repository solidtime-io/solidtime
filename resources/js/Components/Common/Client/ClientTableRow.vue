<script setup lang="ts">
import type { Client } from '@/packages/api/src';
import { computed, ref } from 'vue';
import { CheckCircleIcon, ArchiveBoxIcon } from '@heroicons/vue/24/outline';
import {
    PencilSquareIcon,
    ArchiveBoxIcon as ArchiveBoxIconSolid,
    TrashIcon,
} from '@heroicons/vue/20/solid';
import { useClientsStore } from '@/utils/useClients';
import ClientMoreOptionsDropdown from '@/Components/Common/Client/ClientMoreOptionsDropdown.vue';
import { useProjectsQuery } from '@/utils/useProjectsQuery';
import TableRow from '@/Components/TableRow.vue';
import ClientEditModal from '@/Components/Common/Client/ClientEditModal.vue';
import { canUpdateClients, canDeleteClients } from '@/utils/permissions';
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuTrigger,
} from '@/packages/ui/src';

const { projects } = useProjectsQuery();

const props = defineProps<{
    client: Client;
}>();

function deleteClient() {
    useClientsStore().deleteClient(props.client.id);
}

const projectCount = computed(() => {
    return projects.value.filter((projects) => projects.client_id === props.client.id).length;
});

function archiveClient() {
    useClientsStore().updateClient(props.client.id, {
        ...props.client,
        is_archived: !props.client.is_archived,
    });
}

const showEditModal = ref(false);
</script>

<template>
    <ContextMenu>
        <ContextMenuTrigger as-child>
            <TableRow>
                <ClientEditModal v-model:show="showEditModal" :client="client"></ClientEditModal>
                <div
                    class="whitespace-nowrap flex items-center space-x-5 py-4 pr-3 text-sm font-medium text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
                    <span>
                        {{ client.name }}
                    </span>
                </div>
                <div
                    class="whitespace-nowrap flex items-center px-3 py-4 text-sm text-text-primary">
                    <span> {{ projectCount }} Projects </span>
                </div>
                <div
                    class="whitespace-nowrap px-3 py-4 text-sm text-text-primary flex space-x-1.5 items-center">
                    <template v-if="client.is_archived">
                        <ArchiveBoxIcon class="w-4 text-icon-default"></ArchiveBoxIcon>
                        <span>Archived</span>
                    </template>
                    <template v-else>
                        <CheckCircleIcon class="w-4 text-icon-default"></CheckCircleIcon>
                        <span>Active</span>
                    </template>
                </div>
                <div
                    class="relative whitespace-nowrap flex items-center pl-3 text-right text-sm font-medium sm:pr-0 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
                    <ClientMoreOptionsDropdown
                        :client="client"
                        @edit="showEditModal = true"
                        @archive="archiveClient"
                        @delete="deleteClient"></ClientMoreOptionsDropdown>
                </div>
            </TableRow>
        </ContextMenuTrigger>
        <ContextMenuContent class="min-w-[160px]">
            <ContextMenuItem
                v-if="canUpdateClients()"
                class="space-x-3"
                @select="showEditModal = true">
                <PencilSquareIcon class="w-4 h-4 text-icon-default" />
                <span>Edit</span>
            </ContextMenuItem>
            <ContextMenuItem v-if="canUpdateClients()" class="space-x-3" @select="archiveClient()">
                <ArchiveBoxIconSolid class="w-4 h-4 text-icon-default" />
                <span>{{ client.is_archived ? 'Unarchive' : 'Archive' }}</span>
            </ContextMenuItem>
            <ContextMenuSeparator v-if="canDeleteClients()" />
            <ContextMenuItem
                v-if="canDeleteClients()"
                class="space-x-3 text-destructive"
                @select="deleteClient()">
                <TrashIcon class="w-4 h-4 text-icon-default" />
                <span>Delete</span>
            </ContextMenuItem>
        </ContextMenuContent>
    </ContextMenu>
</template>

<style scoped></style>
