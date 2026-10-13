<script setup lang="ts">
import { LockClosedIcon } from '@heroicons/vue/16/solid';
import MockupFrame from './MockupFrame.vue';

// Note: The first goal is the one of the free plan, the others are locked
const previewGoals = [
    {
        name: 'Billable hours per month',
        tracked: '62h',
        target: '80h',
        progress: 78,
        locked: false,
    },
    { name: 'Deep work per week', tracked: '14h', target: '20h', progress: 70, locked: true },
    { name: 'Support per week', tracked: '3h', target: '8h', progress: 40, locked: true },
];
</script>

<template>
    <MockupFrame :glow-in-light-mode="false">
        <div class="px-6 pt-4">
            <div
                class="grid grid-cols-[1fr_170px] border-b border-gray-300/70 pb-1.5 text-[11px] text-gray-400 dark:border-white/10 dark:text-white/40">
                <span>Goal</span>
                <span>Progress</span>
            </div>
            <div
                v-for="goal in previewGoals"
                :key="goal.name"
                class="grid grid-cols-[1fr_170px] items-center border-b border-gray-300/50 py-1.5 text-sm last:border-b-0 dark:border-white/5">
                <span class="flex items-center gap-1.5 text-gray-800 dark:text-white/90">
                    <LockClosedIcon
                        v-if="goal.locked"
                        class="h-3 w-3 text-gray-400 dark:text-white/40" />
                    {{ goal.name }}
                </span>
                <div
                    class="flex select-none items-center gap-2.5"
                    :class="goal.locked ? 'blur-[3px]' : ''">
                    <div
                        class="h-1.5 flex-1 overflow-hidden rounded-full bg-gray-300/60 dark:bg-white/10">
                        <div
                            class="h-full rounded-full bg-accent-500 dark:bg-accent-300"
                            :style="{ width: goal.progress + '%' }" />
                    </div>
                    <span
                        class="w-16 text-right text-xs tabular-nums text-gray-500 dark:text-white/60"
                        >{{ goal.tracked }} / {{ goal.target }}</span
                    >
                </div>
            </div>
        </div>
    </MockupFrame>
</template>
