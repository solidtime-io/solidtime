<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import TimeTracker from '@/Components/TimeTracker.vue';
import RecentlyTrackedTasksCard from '@/Components/Dashboard/RecentlyTrackedTasksCard.vue';
import LastSevenDaysCard from '@/Components/Dashboard/LastSevenDaysCard.vue';
import TeamActivityCard from '@/Components/Dashboard/TeamActivityCard.vue';
import ThisWeekOverview from '@/Components/Dashboard/ThisWeekOverview.vue';
import { usePage } from '@inertiajs/vue3';
import type { Organization, User } from '@/types/models';
import { onMounted } from 'vue';
import { useProjectsStore } from '@/utils/useProjects';
import ActivityGraphCard from '@/Components/Dashboard/ActivityGraphCard.vue';

const page = usePage<{
    auth: {
        user: User & {
            all_teams: Organization[];
        };
    };
}>();

onMounted(async () => {
    if (page.props.auth.user.current_team_id) {
        await useProjectsStore().fetchProjects(
            page.props.auth.user.current_team_id
        );
    }
});
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
        <div
            class="py-8 sm:px-6 lg:px-8 mx-auto border-b border-default-background-seperator">
            <TimeTracker></TimeTracker>
        </div>
        <div
            class="grid gap-x-6 grid-cols-4 sm:px-6 lg:px-8 pt-6 pb-7 border-b border-default-background-seperator">
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
        </div>
        <div class="sm:px-6 lg:px-8 py-6">
            <ThisWeekOverview
                :weeklyProjectOverview="props.weeklyProjectOverview"
                :total-weekly-billable-amount="props.totalWeeklyBillableAmount"
                :total-weekly-billable-time="props.totalWeeklyBillableTime"
                :total-weekly-time="props.totalWeeklyTime"
                :weekly-history="props.weeklyHistory"></ThisWeekOverview>
        </div>
    </AppLayout>
</template>
