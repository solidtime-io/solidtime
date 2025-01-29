<script setup lang="ts">
import MultiselectDropdown from '@/packages/ui/src/Input/MultiselectDropdown.vue';
import { storeToRefs } from 'pinia';
import type { Task } from '@/packages/api/src';
import { useTasksStore } from '@/utils/useTasks';

const tasksStore = useTasksStore();
const { tasks } = storeToRefs(tasksStore);

function getKeyFromItem(item: Task) {
    return item.id;
}

function getNameForItem(item: Task) {
    return item.name;
}
</script>

<template>
    <MultiselectDropdown
        search-placeholder="Search for a Task..."
        :items="tasks"
        :get-key-from-item="getKeyFromItem"
        :get-name-for-item="getNameForItem">
        <template #trigger>
            <slot name="trigger"></slot>
        </template>
    </MultiselectDropdown>
</template>
