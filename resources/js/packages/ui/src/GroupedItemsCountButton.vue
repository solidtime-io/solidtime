<script setup lang="ts">
import { twMerge } from 'tailwind-merge';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        expanded?: boolean;
        size?: string;
        /**
         * Test ID used for Playwright/E2E tests.
         */
        testId?: string;
    }>(),
    {
        expanded: false,
        size: 'w-7 h-7',
        testId: 'grouped_items_count_button',
    }
);

const expandedStatusClasses = computed(() => {
    if (props.expanded) {
        return 'border-card-border border bg-quaternary text-text-primary';
    }
    return 'border-card-border bg-secondary border hover:bg-tertiary hover:text-text-primary transition text-text-secondary';
});
</script>

<template>
    <button
        :data-testid="props.testId"
        :class="
            twMerge(
                'font-medium text-base rounded flex items-center transition justify-center focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:border-transparent',
                expandedStatusClasses,
                props.size
            )
        ">
        <slot></slot>
    </button>
</template>

<style scoped></style>
