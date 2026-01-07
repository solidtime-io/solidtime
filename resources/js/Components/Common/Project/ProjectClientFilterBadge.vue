<script setup lang="ts">
import { computed } from 'vue';
import { UserGroupIcon } from '@heroicons/vue/16/solid';
import { DropdownMenuCheckboxItem, DropdownMenuSeparator } from '@/Components/ui/dropdown-menu';
import BaseFilterBadge from './BaseFilterBadge.vue';
import type { Client } from '@/packages/api/src';
import { NO_CLIENT_ID } from './constants';

const props = defineProps<{
    value: string[];
    clients: Client[];
}>();

const emit = defineEmits<{
    remove: [];
    'update:value': [value: string[]];
}>();

const hasNoClient = computed(() => props.value.includes(NO_CLIENT_ID));

const label = computed(() => {
    const count = props.value.length;

    if (count === 0) return 'None';
    if (count === 1) {
        if (hasNoClient.value) return 'No client';
        const client = props.clients.find((c) => c.id === props.value[0]);
        return client?.name ?? 'Client';
    }
    return `${count} selected`;
});

function toggleClient(clientId: string) {
    const clientIds = props.value.includes(clientId)
        ? props.value.filter((id) => id !== clientId)
        : [...props.value, clientId];

    emit('update:value', clientIds);
}

function toggleNoClient() {
    const clientIds = hasNoClient.value
        ? props.value.filter((id) => id !== NO_CLIENT_ID)
        : [...props.value, NO_CLIENT_ID];

    emit('update:value', clientIds);
}
</script>

<template>
    <BaseFilterBadge
        :icon="UserGroupIcon"
        :label="label"
        filter-name="Client"
        @remove="emit('remove')">
        <DropdownMenuCheckboxItem :model-value="hasNoClient" @select.prevent="toggleNoClient">
            No client
        </DropdownMenuCheckboxItem>
        <DropdownMenuSeparator />
        <DropdownMenuCheckboxItem
            v-for="client in clients"
            :key="client.id"
            :model-value="value.includes(client.id)"
            @select.prevent="toggleClient(client.id)">
            {{ client.name }}
        </DropdownMenuCheckboxItem>
    </BaseFilterBadge>
</template>
