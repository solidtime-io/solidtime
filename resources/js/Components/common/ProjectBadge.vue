<script setup lang="ts">
import { twMerge } from 'tailwind-merge';

const props = withDefaults(
    defineProps<{
        name: string;
        size: 'base' | 'large';
        tag: string;
        class?: string;
        color: string;
    }>(),
    {
        size: 'base',
        tag: 'div',
        color: 'var(--theme-color-icon-default)',
    }
);

const indicatorClasses = {
    base: 'w-2.5 h-2.5',
    large: 'w-3 h-3',
};

const badgeClasses = {
    base: 'py-1 px-2 space-x-1.5 text-xs',
    large: 'py-1.5 px-3 space-x-2 text-sm text-muted',
};
</script>

<template>
    <component
        :is="tag"
        :class="
            twMerge(
                props.class,
                badgeClasses[size],
                'border-input-border border rounded inline-flex items-center font-semibold text-white'
            )
        ">
        <div
            :style="{ backgroundColor: color }"
            :class="
                twMerge(indicatorClasses[size], 'inline-block rounded-full')
            "></div>
        <span>
            {{ name }}
        </span>
    </component>
</template>

<style scoped></style>
