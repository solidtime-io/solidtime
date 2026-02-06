<script setup lang="ts">
import MultiselectDropdown from '@/packages/ui/src/Input/MultiselectDropdown.vue';
import type { Task } from '@/packages/api/src';
import { useTasksQuery } from '@/utils/useTasksQuery';

const { tasks } = useTasksQuery();

function getKeyFromItem(item: Task) {
    return item.id;
}

function getNameForItem(item: Task) {
    return item.name;
}

const emit = defineEmits<{
    submit: [];
}>();
</script>

<template>
    <MultiselectDropdown
        search-placeholder="Search for a Task..."
        :items="tasks"
        :get-key-from-item="getKeyFromItem"
        :get-name-for-item="getNameForItem"
        no-item-label="No Task"
        @submit="emit('submit')">
        <template #trigger>
            <slot name="trigger"></slot>
        </template>
    </MultiselectDropdown>
</template>
