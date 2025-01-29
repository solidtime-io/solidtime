<script setup lang="ts">
import type { HtmlButtonType } from '@/types/dom';
import LoadingSpinner from '../LoadingSpinner.vue';
import type { Component } from 'vue';
import { twMerge } from 'tailwind-merge';

const props = withDefaults(
    defineProps<{
        type?: HtmlButtonType;
        icon?: Component;
        loading?: boolean;
    }>(),
    {
        type: 'submit',
        loading: false,
    }
);
</script>

<template>
    <button
        :type="type"
        :disabled="loading"
        class="inline-flex items-center px-2 sm:px-3 py-1 sm:py-2 bg-accent-300/10 border border-accent-300/20 rounded-md font-medium text-xs sm:text-sm text-white hover:bg-accent-300/20 active:bg-accent-300/20 focus:outline-none focus-visible:ring-2 focus-visible:border-transparent focus-visible:ring-ring transition ease-in-out duration-150">
        <span
            :class="
                twMerge('flex items-center ', props.icon ? 'space-x-1.5' : '')
            ">
            <LoadingSpinner v-if="loading"></LoadingSpinner>
            <component
                :is="props.icon"
                v-if="props.icon && !loading"
                class="text-text-secondary w-4 -ml-0.5 mr-1"></component>
            <span>
                <slot />
            </span>
        </span>
    </button>
</template>
