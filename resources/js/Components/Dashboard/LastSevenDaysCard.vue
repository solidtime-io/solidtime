<script setup lang="ts">
import DashboardCard from '@/Components/Dashboard/DashboardCard.vue';
import DayOverviewCardEntry from '@/Components/Dashboard/DayOverviewCardEntry.vue';
import { CalendarIcon } from '@heroicons/vue/20/solid';
defineProps<{
    last7Days: {
        date: string;
        duration: number; // Total duration in seconds
        history: number[]; // Array representing the duration in seconds of the 3h windows for the day
    }[];
}>();
</script>

<template>
    <DashboardCard title="Last 7 Days" :icon="CalendarIcon">
        <DayOverviewCardEntry
            v-for="day in last7Days"
            :key="day.date"
            :date="day.date"
            :duration="day.duration"></DayOverviewCardEntry>
    </DashboardCard>
</template>
