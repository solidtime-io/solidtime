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
        class="inline-flex items-center px-4 py-2 bg-white/90 border border-transparent rounded-md font-semibold text-xs text-black uppercase tracking-widest hover:bg-white focus:bg-white active:bg-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150">
        <slot />
    </button>
</template>
