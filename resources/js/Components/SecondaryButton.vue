<script setup lang="ts">
import type { HtmlButtonType } from '@/types/dom';

withDefaults(
    defineProps<{
        type: HtmlButtonType;
    }>(),
    {
        type: 'button',
    }
);
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center px-4 py-2 bg-button-secondary-background border border-button-secondary-border hover:bg-button-secondary-background-hover rounded-md font-semibold text-xs text-white uppercase tracking-widest shadow-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 dark:focus:ring-offset-gray-800 disabled:opacity-25 transition ease-in-out duration-150">
        <slot />
    </button>
</template>
