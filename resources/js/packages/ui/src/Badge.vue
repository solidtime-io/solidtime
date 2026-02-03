<script setup lang="ts">
import { twMerge } from 'tailwind-merge';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        size?: 'base' | 'large' | 'xlarge';
        tag?: string;
        class?: string;
        color?: string;
        border?: boolean;
    }>(),
    {
        size: 'base',
        tag: 'div',
        color: 'var(--theme-color-icon-default)',
        border: true,
    }
);

const badgeClasses = {
    base: 'py-1 px-2 space-x-1.5 text-xs',
    large: 'py-1 sm:py-1.5 px-2 sm:px-3 space-x-1.5 sm:space-x-2 text-xs sm:text-sm text-text-secondary',
    xlarge: 'py-2 sm:py-2.5 px-3 sm:px-3.5 space-x-2 sm:space-x-3 text-sm sm:text-sm text-text-secondary',
};

const borderClasses = computed(() => {
    if (props.border) {
        return 'border-input-border border';
    }
    return '';
});

const tagClasses = computed(() => {
    if (props.tag === 'button') {
        return 'hover:bg-tertiary';
    }
    return '';
});
</script>

<template>
    <component
        :is="tag"
        :class="
            twMerge(
                tagClasses,
                badgeClasses[size],
                borderClasses,
                'rounded transition inline-flex items-center font-medium text-text-primary disabled:text-text-quaternary outline-0 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring min-w-0 overflow-hidden',
                props.class
            )
        ">
        <slot></slot>
    </component>
</template>

<style scoped></style>
