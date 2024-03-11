<script setup lang="ts">
import type { Component } from 'vue';

defineProps<{
    title: string;
    icon: Component;
    current?: boolean;
    href: string;
}>();
</script>

<template>
    <li>
        <a
            :href="href"
            :class="[
                current
                    ? 'bg-menu-active text-white'
                    : 'text-indigo-200 hover:text-white hover:bg-menu-active',
                'group flex gap-x-3 rounded-md px-3 py-2 transition leading-6 font-medium',
            ]">
            <component
                :is="icon"
                :class="[
                    current
                        ? 'text-icon-active'
                        : 'text-icon-default group-hover:text-icon-active',
                    'transition h-6 w-6 shrink-0',
                ]"
                aria-hidden="true" />
            {{ title }}
        </a>
    </li>
</template>
