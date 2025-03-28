<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { twMerge } from 'tailwind-merge';

defineProps<{
    href?: string;
}>();
</script>

<template>
    <Component
        :is="href ? Link : 'div'"
        :href="href"
        role="row"
        :class="
            twMerge(
                'contents group [&>*]:transition [&>*]:border-row-separator [&>*]:bg-row-background [&>*]:border-b',
                href ? '[&>*]:cursor-pointer [&>*]:hover:bg-white/5' : ''
            )
        ">
        <slot></slot>
    </Component>
</template>

<style scoped></style>
