<script setup lang="ts">
import { twMerge } from 'tailwind-merge';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        expanded?: boolean;
        size: string;
    }>(),
    {
        expanded: false,
        size: 'w-7 h-7',
    }
);

const expandedStatusClasses = computed(() => {
    if (props.expanded) {
        return 'border-card-border border bg-card-background-active text-white';
    }
    return 'border-card-border border bg-card-background text-muted';
});
</script>

<template>
    <button
        :class="
            twMerge(
                'font-medium rounded flex items-center transition justify-center',
                expandedStatusClasses,
                props.size
            )
        ">
        <slot></slot>
    </button>
</template>

<style scoped></style>
