<script setup lang="ts">
import { twMerge } from 'tailwind-merge';
import Badge from '../Badge.vue';
import { TagIcon } from '@heroicons/vue/20/solid';

const props = withDefaults(
    defineProps<{
        name: string;
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

const indicatorClasses = {
    base: 'w-3 h-3',
    large: 'w-5 h-5',
};
</script>

<template>
    <Badge :name :size :tag :class="props.class" :color :border>
        <TagIcon :class="twMerge(indicatorClasses[size])"></TagIcon>
        <span v-if="name">
            {{ name }}
        </span>
    </Badge>
</template>

<style scoped></style>
