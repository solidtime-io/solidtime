<script setup lang="ts">
import { twMerge } from 'tailwind-merge';
import { computed } from 'vue';

const props = withDefaults(
    defineProps<{
        size: 'base' | 'large';
        tag: string;
        class?: string;
        color: string;
        border: boolean;
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
    large: 'py-1.5 px-3 space-x-2 text-sm text-muted',
};

const borderClasses = computed(() => {
    if (props.border) {
        return 'border-input-border border';
    }
    return '';
});
</script>

<template>
    <component
        :is="tag"
        :class="
            twMerge(
                props.class,
                badgeClasses[size],
                borderClasses,
                'rounded inline-flex items-center font-semibold text-white'
            )
        ">
        <slot></slot>
    </component>
</template>

<style scoped></style>
