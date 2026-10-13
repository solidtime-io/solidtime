<script setup lang="ts">
import { LockClosedIcon } from '@heroicons/vue/16/solid';
import MockupFrame from './MockupFrame.vue';

const previewRows = [
    { name: 'Website Redesign', spent: '32h', estimate: '40h', progress: 80, muted: false },
    { name: 'Mobile App', spent: '11h', estimate: '20h', progress: 55, muted: true },
    { name: 'Support Retainer', spent: '6h', estimate: '20h', progress: 30, muted: false },
];
</script>

<template>
    <MockupFrame :glow-in-light-mode="false">
        <div class="px-6 pt-4">
            <div
                class="grid grid-cols-[1fr_170px] border-b border-gray-300/70 pb-1.5 text-[11px] text-gray-400 dark:border-white/10 dark:text-white/40">
                <span>Project</span>
                <span class="flex items-center gap-1">
                    <LockClosedIcon class="h-3 w-3" />
                    Progress
                </span>
            </div>
            <div
                v-for="row in previewRows"
                :key="row.name"
                class="grid grid-cols-[1fr_170px] items-center border-b border-gray-300/50 py-1.5 text-sm last:border-b-0 dark:border-white/5">
                <span class="text-gray-800 dark:text-white/90">{{ row.name }}</span>
                <div class="flex select-none items-center gap-2.5 blur-[3px]">
                    <div
                        class="h-1.5 flex-1 overflow-hidden rounded-full bg-gray-300/60 dark:bg-white/10">
                        <div
                            class="h-full rounded-full"
                            :class="
                                row.muted
                                    ? 'bg-gray-400 dark:bg-white/40'
                                    : 'bg-accent-500 dark:bg-accent-300'
                            "
                            :style="{ width: row.progress + '%' }" />
                    </div>
                    <span
                        class="w-16 text-right text-xs tabular-nums text-gray-500 dark:text-white/60"
                        >{{ row.spent }} / {{ row.estimate }}</span
                    >
                </div>
            </div>
        </div>
    </MockupFrame>
</template>
