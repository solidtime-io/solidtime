<script setup lang="ts">
import type { HtmlButtonType } from '@/types/dom';

withDefaults(
    defineProps<{
        type: HtmlButtonType;
    }>(),
    {
        type: 'submit',
    }
);
</script>

<template>
    <button
        :type="type"
        class="inline-flex items-center px-2 sm:px-3 py-1 sm:py-2 bg-accent-300/10 border border-accent-300/20 rounded-md font-medium text-xs sm:text-sm text-white hover:bg-accent-300/20 focus:bg-white active:bg-accent-300/20 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
        <slot />
    </button>
</template>
