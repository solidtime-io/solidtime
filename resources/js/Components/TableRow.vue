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
        :class="
            twMerge(
                'contents [&>*]:hover:bg-white/5 [&>*]:transition [&>*]:cursor-pointer [&>*]:border-row-separator [&>*]:border-b',
                href ? '[&>*]:cursor-pointer' : ''
            )
        ">
        <slot></slot>
    </Component>
</template>

<style scoped></style>
