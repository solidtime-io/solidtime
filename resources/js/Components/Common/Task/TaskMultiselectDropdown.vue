<script setup lang="ts">
import { computed } from 'vue';
import MultiselectDropdown from '@/packages/ui/src/Input/MultiselectDropdown.vue';
import type { Task } from '@/packages/api/src';
import { useTasksQuery } from '@/utils/useTasksQuery';
import { useProjectsQuery } from '@/utils/useProjectsQuery';

// ponytail: tasks have no client_id, so client filter goes through the project's client_id
const props = defineProps<{
    projectIds?: string[];
    clientIds?: string[];
}>();

const { tasks: allTasks } = useTasksQuery();
const { projects } = useProjectsQuery();

const tasks = computed(() => {
    let result = allTasks.value;
    if (props.projectIds?.length) {
        result = result.filter((task) => props.projectIds!.includes(task.project_id));
    }
    if (props.clientIds?.length) {
        const allowedProjectIds = new Set(
            projects.value
                .filter((project) => props.clientIds!.includes(project.client_id ?? ''))
                .map((project) => project.id)
        );
        result = result.filter((task) => allowedProjectIds.has(task.project_id));
    }
    return result;
});

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
