<script setup lang="ts">
import DayOverviewCardChart from '@/Components/Dashboard/DayOverviewCardChart.vue';

defineProps<{
    date: string;
    duration: number;
    history: number[];
}>();
import {
    formatHumanReadableDate,
    formatHumanReadableDuration,
} from '@/utils/time';
</script>

<template>
    <div
        class="px-3.5 py-2 flex justify-between @container border-b border-card-background-separator">
        <div class="flex items-center">
            <p class="font-semibold text-sm text-white">
                {{ formatHumanReadableDate(date) }}
            </p>
        </div>
        <div class="items-center justify-center flex-1 hidden @2xs:flex">
            <DayOverviewCardChart :history="history"></DayOverviewCardChart>
        </div>
        <div
            class="flex text-sm items-center justify-center text-muted font-semibold">
            {{ formatHumanReadableDuration(duration) }}
        </div>
    </div>
</template>
