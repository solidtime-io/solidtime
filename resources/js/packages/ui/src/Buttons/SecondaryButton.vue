<script setup lang="ts">
import type { HtmlButtonType } from '@/types/dom';
import { twMerge } from 'tailwind-merge';
import { type Component } from 'vue';
import LoadingSpinner from '../LoadingSpinner.vue';

const props = withDefaults(
    defineProps<{
        type: HtmlButtonType;
        icon?: Component;
        size: 'small' | 'base';
        loading: boolean;
        class?: string;
    }>(),
    {
        type: 'button',
        size: 'base',
        loading: false,
    }
);

const sizeClasses = {
    small: 'text-xs px-2 sm:px-2.5 py-1 sm:py-1.5',
    base: 'text-xs sm:text-sm px-2 sm:px-3 py-1 sm:py-2',
};
</script>

<template>
    <button
        :type="type"
        :disabled="loading"
        :class="
            twMerge(
                'bg-button-secondary-background border border-button-secondary-border hover:bg-button-secondary-background-hover shadow-sm transition text-white rounded-lg font-semibold inline-flex items-center space-x-1.5 focus-visible:outline-none focus-visible:border-transparent focus-visible:ring-2 focus-visible:ring-ring focus:border-transparent disabled:opacity-25 ease-in-out',
                sizeClasses[props.size],
                props.class
            )
        ">
        <span
            :class="
                twMerge('flex items-center ', props.icon ? 'space-x-1.5' : '')
            ">
            <LoadingSpinner v-if="loading"></LoadingSpinner>
            <component
                v-if="props.icon && !loading"
                :is="props.icon"
                class="text-text-tertiary w-4 -ml-0.5 mr-1"></component>
            <span>
                <slot />
            </span>
        </span>
    </button>
</template>
