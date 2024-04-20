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
                'contents [&>*]:transition [&>*]:border-row-separator [&>*]:border-b',
                href ? '[&>*]:cursor-pointer [&>*]:hover:bg-white/5' : ''
            )
        ">
        <slot></slot>
    </Component>
</template>

<style scoped></style>
