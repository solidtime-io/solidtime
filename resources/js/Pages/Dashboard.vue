<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import TimeTracker from '@/Components/TimeTracker.vue';
import RecentlyTrackedTasksCard from '@/Components/Dashboard/RecentlyTrackedTasksCard.vue';
import LastSevenDaysCard from '@/Components/Dashboard/LastSevenDaysCard.vue';
import TeamActivityCard from '@/Components/Dashboard/TeamActivityCard.vue';
import ThisWeekOverview from '@/Components/Dashboard/ThisWeekOverview.vue';
import ActivityGraphCard from '@/Components/Dashboard/ActivityGraphCard.vue';
import MainContainer from '@/Pages/MainContainer.vue';

const props = defineProps<{
    latestTasks: {
        id: string;
        name: string;
        project_name: string;
        project_id: string;
    }[];
    latestTeamActivity: {
        user_id: string;
        name: string;
        description: string;
        time_entry_id: string;
        task_id: string;
        status: boolean;
    }[];
    lastSevenDays: {
        date: string;
        duration: number; // Total duration in seconds
        history: number[]; // Array representing the duration in seconds of the 3h windows for the day
    }[];
    dailyTrackedHours: [string, number][];
    weeklyProjectOverview: {
        value: number;
        name: string;
        color: string;
    }[];
    totalWeeklyTime: number;
    totalWeeklyBillableTime: number;
    totalWeeklyBillableAmount: {
        value: number;
        currency: string;
    };
    weeklyHistory: {
        date: string;
        duration: number;
    }[];
}>();
</script>

<template>
    <AppLayout title="Dashboard" data-testid="dashboard_view">
        <MainContainer
            class="pt-8 pb-6 border-b border-default-background-seperator">
            <TimeTracker></TimeTracker>
        </MainContainer>
        <MainContainer
            class="grid gap-x-6 grid-cols-2 xl:grid-cols-4 pt-5 pb-6 border-b border-default-background-seperator items-stretch">
            <RecentlyTrackedTasksCard
                :latestTasks="props.latestTasks"></RecentlyTrackedTasksCard>
            <LastSevenDaysCard
                :last7-days="props.lastSevenDays"></LastSevenDaysCard>
            <ActivityGraphCard
                :daily-hours-tracked="
                    props.dailyTrackedHours
                "></ActivityGraphCard>
            <TeamActivityCard
                :latestTeamActivity="
                    props.latestTeamActivity
                "></TeamActivityCard>
        </MainContainer>
        <MainContainer class="py-5">
            <ThisWeekOverview
                :weeklyProjectOverview="props.weeklyProjectOverview"
                :total-weekly-billable-amount="props.totalWeeklyBillableAmount"
                :total-weekly-billable-time="props.totalWeeklyBillableTime"
                :total-weekly-time="props.totalWeeklyTime"
                :weekly-history="props.weeklyHistory"></ThisWeekOverview>
        </MainContainer>
    </AppLayout>
</template>
