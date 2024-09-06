<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import TimeTracker from '@/Components/TimeTracker.vue';
import RecentlyTrackedTasksCard from '@/Components/Dashboard/RecentlyTrackedTasksCard.vue';
import LastSevenDaysCard from '@/Components/Dashboard/LastSevenDaysCard.vue';
import TeamActivityCard from '@/Components/Dashboard/TeamActivityCard.vue';
import ThisWeekOverview from '@/Components/Dashboard/ThisWeekOverview.vue';
import ActivityGraphCard from '@/Components/Dashboard/ActivityGraphCard.vue';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import { canViewMembers } from '@/utils/permissions';
import { router } from '@inertiajs/vue3';

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
    dailyTrackedHours: { duration: number; date: string }[];
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

function refreshDashboardData() {
    router.reload({
        only: [
            'latestTasks',
            'latestTeamActivity',
            'lastSevenDays',
            'dailyTrackedHours',
            'weeklyProjectOverview',
            'totalWeeklyTime',
            'totalWeeklyBillableTime',
            'totalWeeklyBillableAmount',
            'weeklyHistory',
        ],
    });
}
</script>

<template>
    <AppLayout title="Dashboard" data-testid="dashboard_view">
        <MainContainer
            class="pt-5 sm:pt-8 pb-4 sm:pb-6 border-b border-default-background-separator">
            <TimeTracker @change="refreshDashboardData"></TimeTracker>
        </MainContainer>
        <MainContainer
            class="grid gap-5 sm:gap-6 grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 pt-3 sm:pt-5 pb-4 sm:pb-6 border-b border-default-background-separator items-stretch">
            <RecentlyTrackedTasksCard
                :latestTasks="props.latestTasks"></RecentlyTrackedTasksCard>
            <LastSevenDaysCard
                :last7-days="props.lastSevenDays"></LastSevenDaysCard>
            <ActivityGraphCard
                :daily-hours-tracked="
                    props.dailyTrackedHours
                "></ActivityGraphCard>
            <TeamActivityCard
                v-if="canViewMembers()"
                class="flex lg:hidden xl:flex"
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
