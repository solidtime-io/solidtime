<script setup lang="ts">
import MockupFrame from './MockupFrame.vue';

const lineItems = [
    { name: 'Website Redesign', hours: '32h', amount: '€ 3,840.00' },
    { name: 'Mobile App', hours: '18h', amount: '€ 2,160.00' },
];
</script>

<template>
    <MockupFrame>
        <!-- Note: An invoice created from tracked time, it runs off the bottom edge of the header -->
        <div
            class="absolute left-1/2 top-5 w-60 -translate-x-1/2 rounded-t-md border border-b-0 border-gray-200 bg-white px-5 pt-4 pb-10 shadow-xl shadow-gray-400/25 dark:border-transparent dark:shadow-2xl dark:shadow-black/50">
            <div class="flex items-start justify-between">
                <div>
                    <p class="text-[11px] font-semibold text-gray-900">Invoice 2026-014</p>
                    <p class="text-[9px] text-gray-500">Acme Corp · Due November 14</p>
                </div>
                <span
                    class="rounded bg-accent-500 px-1.5 py-0.5 text-[8px] font-bold tracking-wide text-white"
                    >SENT</span
                >
            </div>
            <div class="mt-3 space-y-1 text-[9px] text-gray-600">
                <div v-for="item in lineItems" :key="item.name" class="flex justify-between">
                    <span>{{ item.name }} · {{ item.hours }}</span>
                    <span class="tabular-nums">{{ item.amount }}</span>
                </div>
            </div>
            <div
                class="mt-2 flex justify-between border-t border-gray-200 pt-1.5 text-[10px] font-semibold text-gray-900">
                <span>Total</span>
                <span class="tabular-nums">€ 6,000.00</span>
            </div>
        </div>
    </MockupFrame>
</template>
