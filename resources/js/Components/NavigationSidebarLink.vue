<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import type { Component } from 'vue';
defineProps<{
    title: string;
    icon?: Component;
    current?: boolean;
    href: string;
}>();
</script>

<template>
    <Link :href="href" class="block group">
        <div
            :class="[
                current
                    ? 'bg-menu-active text-text-primary'
                    : 'text-muted group-hover:text-text-primary group-hover:bg-menu-active ',
                'group flex gap-x-2 rounded-md transition leading-6 py-1 px-2 font-medium text-sm items-center',
            ]">
            <component
                :is="icon"
                v-if="icon"
                :class="[
                    current
                        ? 'text-icon-active'
                        : 'text-icon-default group-hover:text-icon-active',
                    'transition h-5 w-5 shrink-0',
                ]"
                aria-hidden="true" />
            {{ title }}
        </div>
    </Link>
</template>

<style scoped></style>
