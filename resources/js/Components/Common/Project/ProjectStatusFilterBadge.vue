<script setup lang="ts">
import { computed } from 'vue';
import { CircleStackIcon } from '@heroicons/vue/16/solid';
import { DropdownMenuItem } from '@/Components/ui/dropdown-menu';
import BaseFilterBadge from './BaseFilterBadge.vue';

type StatusValue = 'active' | 'archived' | 'all';

const props = defineProps<{
    value: StatusValue;
}>();

const emit = defineEmits<{
    remove: [];
    'update:value': [value: StatusValue];
}>();

const statusOptions = [
    { id: 'active' as const, name: 'Active' },
    { id: 'archived' as const, name: 'Archived' },
];

const label = computed(() => {
    return statusOptions.find((opt) => opt.id === props.value)?.name ?? 'Status';
});

function updateStatus(status: StatusValue) {
    emit('update:value', status);
}
</script>

<template>
    <BaseFilterBadge
        :icon="CircleStackIcon"
        :label="label"
        filter-name="Status"
        @remove="emit('remove')">
        <DropdownMenuItem
            v-for="option in statusOptions"
            :key="option.id"
            :class="[value === option.id && 'bg-accent text-accent-foreground']"
            @click="updateStatus(option.id)">
            {{ option.name }}
        </DropdownMenuItem>
    </BaseFilterBadge>
</template>
