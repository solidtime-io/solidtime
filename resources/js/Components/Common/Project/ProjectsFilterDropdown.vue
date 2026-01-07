<script setup lang="ts">
import { computed, ref } from 'vue';
import { UserGroupIcon, CheckCircleIcon } from '@heroicons/vue/16/solid';
import ListFilterIcon from '@/packages/ui/src/Icons/ListFilterIcon.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuSub,
    DropdownMenuSubTrigger,
    DropdownMenuSubContent,
    DropdownMenuCheckboxItem,
    DropdownMenuSeparator,
} from '@/Components/ui/dropdown-menu';
import { Button } from '@/packages/ui/src';
import type { Client } from '@/packages/api/src';
import { NO_CLIENT_ID } from './constants';

export interface ProjectFilters {
    status: 'active' | 'archived' | 'all';
    clientIds: string[];
}

const props = defineProps<{
    filters: ProjectFilters;
    clients: Client[];
}>();

const emit = defineEmits<{
    'update:filters': [filters: ProjectFilters];
}>();

const statusOptions = [
    { id: 'active' as const, name: 'Active' },
    { id: 'archived' as const, name: 'Archived' },
];

const open = ref(false);

function updateStatus(status: 'active' | 'archived' | 'all') {
    emit('update:filters', {
        ...props.filters,
        status,
    });
    open.value = false;
}

function toggleClient(clientId: string) {
    const clientIds = props.filters.clientIds.includes(clientId)
        ? props.filters.clientIds.filter((id) => id !== clientId)
        : [...props.filters.clientIds, clientId];

    emit('update:filters', {
        ...props.filters,
        clientIds,
    });
}

function toggleNoClient() {
    const clientIds = props.filters.clientIds.includes(NO_CLIENT_ID)
        ? props.filters.clientIds.filter((id) => id !== NO_CLIENT_ID)
        : [...props.filters.clientIds, NO_CLIENT_ID];

    emit('update:filters', {
        ...props.filters,
        clientIds,
    });
}

const hasActiveFilters = computed(() => {
    return props.filters.status !== 'all' || props.filters.clientIds.length > 0;
});
</script>

<template>
    <DropdownMenu v-model:open="open">
        <DropdownMenuTrigger as-child>
            <Button variant="ghost" size="xs" aria-label="Filter projects">
                <ListFilterIcon
                    :class="[hasActiveFilters ? '' : '-ml-0.5', 'h-4 w-4 text-icon-default']" />
                <span v-if="!hasActiveFilters" class="text-nowrap">Filter</span>
            </Button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="start" class="w-56">
            <!-- Status Filter -->
            <DropdownMenuSub>
                <DropdownMenuSubTrigger class="gap-2">
                    <CheckCircleIcon class="h-4 w-4 text-icon-default" />
                    <span>Status</span>
                </DropdownMenuSubTrigger>
                <DropdownMenuSubContent>
                    <DropdownMenuItem
                        v-for="option in statusOptions"
                        :key="option.id"
                        :class="[
                            filters.status === option.id && 'bg-accent text-accent-foreground',
                        ]"
                        @click="updateStatus(option.id)">
                        {{ option.name }}
                    </DropdownMenuItem>
                </DropdownMenuSubContent>
            </DropdownMenuSub>

            <!-- Client Filter -->
            <DropdownMenuSub v-if="clients.length > 0">
                <DropdownMenuSubTrigger class="gap-2">
                    <UserGroupIcon class="h-4 w-4 text-icon-default" />
                    <span>Client</span>
                </DropdownMenuSubTrigger>
                <DropdownMenuSubContent class="max-h-[300px] overflow-y-auto">
                    <DropdownMenuCheckboxItem
                        :model-value="filters.clientIds.includes(NO_CLIENT_ID)"
                        @select.prevent="toggleNoClient">
                        No client
                    </DropdownMenuCheckboxItem>
                    <DropdownMenuSeparator />
                    <DropdownMenuCheckboxItem
                        v-for="client in clients"
                        :key="client.id"
                        :model-value="filters.clientIds.includes(client.id)"
                        @select.prevent="toggleClient(client.id)">
                        {{ client.name }}
                    </DropdownMenuCheckboxItem>
                </DropdownMenuSubContent>
            </DropdownMenuSub>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
