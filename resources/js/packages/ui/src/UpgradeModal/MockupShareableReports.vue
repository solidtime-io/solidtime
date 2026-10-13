<script setup lang="ts">
import { LinkIcon, LockClosedIcon } from '@heroicons/vue/16/solid';
import MockupFrame from './MockupFrame.vue';

const previewRows = [
    { name: 'Website Redesign', duration: '32h 15min' },
    { name: 'Mobile App', duration: '18h 40min' },
];
</script>

<template>
    <MockupFrame>
        <!-- Note: A browser window showing the shared report, it runs into the bottom edge -->
        <div
            class="absolute inset-x-10 top-4 -bottom-px rounded-t-lg border border-b-0 border-gray-300/80 bg-white shadow-xl shadow-gray-400/20 dark:border-white/10 dark:bg-white/[0.04] dark:shadow-2xl dark:shadow-black/40">
            <div
                class="flex items-center gap-2 border-b border-gray-200 px-3 py-2 dark:border-white/10">
                <div class="flex gap-1">
                    <span class="h-2 w-2 rounded-full bg-gray-300 dark:bg-white/15" />
                    <span class="h-2 w-2 rounded-full bg-gray-300 dark:bg-white/15" />
                    <span class="h-2 w-2 rounded-full bg-gray-300 dark:bg-white/15" />
                </div>
                <div
                    class="flex min-w-0 flex-1 items-center gap-1.5 rounded-md bg-gray-100 px-2 py-1 text-[11px] text-gray-500 dark:bg-black/30 dark:text-white/50">
                    <LockClosedIcon class="h-3 w-3 shrink-0 text-gray-400 dark:text-white/30" />
                    <span class="truncate">app.solidtime.io/shared-report</span>
                </div>
                <span
                    class="flex shrink-0 items-center gap-1 rounded-md border border-gray-200 bg-gray-50 px-2 py-1 text-[11px] font-medium text-gray-700 dark:border-transparent dark:bg-white/15 dark:text-white/90">
                    <LinkIcon class="h-3 w-3" />
                    Copy link
                </span>
            </div>
            <div class="px-4 pt-2 pb-1.5">
                <p class="py-1.5 text-xs font-medium text-gray-900 dark:text-white/90">
                    Acme Corp · October
                </p>
                <div
                    v-for="row in previewRows"
                    :key="row.name"
                    class="flex justify-between border-b border-gray-100 py-1 text-xs last:border-b-0 dark:border-white/5">
                    <span class="text-gray-600 dark:text-white/70">{{ row.name }}</span>
                    <span class="tabular-nums text-gray-400 dark:text-white/50">{{
                        row.duration
                    }}</span>
                </div>
            </div>
        </div>
    </MockupFrame>
</template>
