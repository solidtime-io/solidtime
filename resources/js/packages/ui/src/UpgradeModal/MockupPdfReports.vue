<script setup lang="ts">
import MockupFrame from './MockupFrame.vue';
import MockupPdfPage from './MockupPdfPage.vue';
</script>

<template>
    <MockupFrame>
        <!-- Note: The PDF report with two more pages fanned out behind it -->
        <div class="absolute left-1/2 top-5 h-full w-56 -translate-x-1/2">
            <div
                class="absolute inset-x-0 top-3 h-48 origin-bottom -translate-x-10 -rotate-[8deg] rounded-md border border-gray-200 bg-white/80 shadow-lg shadow-gray-400/20 dark:border-transparent dark:bg-white/70 dark:shadow-xl dark:shadow-black/40" />
            <div
                class="absolute inset-x-0 top-3 h-48 origin-bottom translate-x-10 rotate-[8deg] rounded-md border border-gray-200 bg-white/80 shadow-lg shadow-gray-400/20 dark:border-transparent dark:bg-white/70 dark:shadow-xl dark:shadow-black/40" />
            <MockupPdfPage class="absolute inset-x-0 top-0" />
        </div>
    </MockupFrame>
</template>
