<script setup lang="ts">
import type { Component } from 'vue';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title: string;
    icon: Component;
    current?: boolean;
    href: string;
}>();
</script>

<template>
    <li>
        <Link
            :href="href"
            :class="[
                current
                    ? 'bg-menu-active text-white'
                    : 'text-muted hover:text-white hover:bg-menu-active ',
                'group flex gap-x-2 rounded-md px-2 py-1.5 transition leading-6 font-semibold text-sm items-center',
            ]">
            <component
                :is="icon"
                :class="[
                    current
                        ? 'text-icon-active'
                        : 'text-icon-default group-hover:text-icon-active',
                    'transition h-5 w-5 shrink-0',
                ]"
                aria-hidden="true" />
            {{ title }}
        </Link>
    </li>
</template>
