<script setup lang="ts">
import type { HtmlButtonType } from '@/types/dom';
import { twMerge } from 'tailwind-merge';
import { type Component } from 'vue';

const props = withDefaults(
    defineProps<{
        type: HtmlButtonType;
        icon?: Component;
    }>(),
    {
        type: 'button',
    }
);
</script>

<template>
    <button
        :type="type"
        class="bg-button-secondary-background border border-button-secondary-border hover:bg-button-secondary-background-hover shadow-sm transition text-white text-xs sm:text-sm px-2 sm:px-3 py-1 sm:py-2 rounded-lg font-semibold inline-flex items-center space-x-1.5 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 ease-in-out">
        <span
            :class="
                twMerge('flex items-center ', props.icon ? 'space-x-1.5' : '')
            ">
            <component
                v-if="props.icon"
                :is="props.icon"
                class="w-4 sm:w-5 h-4 sm:h-5 -ml-0.5 sm:-ml-1"></component>
            <span>
                <slot />
            </span>
        </span>
    </button>
</template>
