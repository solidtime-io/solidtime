<script setup lang="ts">
const bars = [45, 70, 55, 85, 65, 35, 50];
</script>

<template>
    <!-- Note: A PDF page that runs off the bottom edge of the header -->
    <div
        class="rounded-t-md border border-b-0 border-gray-200 bg-white px-5 pt-4 pb-10 shadow-xl shadow-gray-400/25 dark:border-transparent dark:shadow-2xl dark:shadow-black/50">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-[11px] font-semibold text-gray-900">Time report</p>
                <p class="text-[9px] text-gray-500">Acme Corp · October 2026</p>
            </div>
            <span
                class="rounded bg-red-500 px-1.5 py-0.5 text-[8px] font-bold tracking-wide text-white"
                >PDF</span
            >
        </div>
        <div class="mt-2.5 flex h-7 items-end gap-1">
            <div
                v-for="(height, index) in bars"
                :key="index"
                class="flex-1 rounded-sm bg-accent-500/70"
                :style="{ height: height + '%' }" />
        </div>
        <div class="mt-2 space-y-1 text-[9px] text-gray-600">
            <div class="flex justify-between">
                <span>Website Redesign</span><span class="tabular-nums">32h 15min</span>
            </div>
            <div class="flex justify-between">
                <span>Mobile App</span><span class="tabular-nums">18h 40min</span>
            </div>
        </div>
    </div>
</template>
