<script setup lang="ts">
import { LockClosedIcon } from '@heroicons/vue/16/solid';
import MockupFrame from './MockupFrame.vue';

const previewRows = [
    { name: 'Acme Corp', duration: '42h 30min', cost: '€ 5,100.00' },
    { name: 'Globex', duration: '27h 15min', cost: '€ 3,270.00' },
    { name: 'Initech', duration: '12h 45min', cost: '€ 1,530.00' },
];
</script>

<template>
    <MockupFrame :glow-in-light-mode="false">
        <div class="px-6 pt-4">
            <div
                class="grid grid-cols-[1fr_100px_100px] border-b border-gray-300/70 pb-1.5 text-[11px] text-gray-400 dark:border-white/10 dark:text-white/40">
                <span>Client</span>
                <span class="text-right">Duration</span>
                <span class="flex items-center justify-end gap-1">
                    <LockClosedIcon class="h-3 w-3" />
                    Cost
                </span>
            </div>
            <div
                v-for="row in previewRows"
                :key="row.name"
                class="grid grid-cols-[1fr_100px_100px] border-b border-gray-300/50 py-1.5 text-sm last:border-b-0 dark:border-white/5">
                <span class="text-gray-800 dark:text-white/90">{{ row.name }}</span>
                <span class="text-right tabular-nums text-gray-500 dark:text-white/50">{{
                    row.duration
                }}</span>
                <span
                    class="select-none text-right font-medium text-accent-600 blur-[5px] dark:text-accent-300"
                    >{{ row.cost }}</span
                >
            </div>
        </div>
    </MockupFrame>
</template>
