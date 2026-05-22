<script setup lang="ts">
import { computed } from 'vue';
import { GlobeAltIcon } from '@heroicons/vue/16/solid';
import { DropdownMenuItem } from '@/packages/ui/src';
import BaseFilterBadge from './BaseFilterBadge.vue';

type VisibilityValue = 'public' | 'private' | 'all';

const props = defineProps<{
    value: VisibilityValue;
}>();

const emit = defineEmits<{
    remove: [];
    'update:value': [value: VisibilityValue];
}>();

const visibilityOptions = [
    { id: 'public' as const, name: 'Public' },
    { id: 'private' as const, name: 'Private' },
];

const label = computed(() => {
    return visibilityOptions.find((opt) => opt.id === props.value)?.name ?? 'Visibility';
});

function updateVisibility(visibility: VisibilityValue) {
    emit('update:value', visibility);
}
</script>

<template>
    <BaseFilterBadge
        :icon="GlobeAltIcon"
        :label="label"
        filter-name="Visibility"
        @remove="emit('remove')">
        <DropdownMenuItem
            v-for="option in visibilityOptions"
            :key="option.id"
            :class="[value === option.id && 'bg-accent text-accent-foreground']"
            @click="updateVisibility(option.id)">
            {{ option.name }}
        </DropdownMenuItem>
    </BaseFilterBadge>
</template>
