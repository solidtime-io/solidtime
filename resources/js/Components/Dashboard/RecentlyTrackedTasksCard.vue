<script setup lang="ts">
import RecentlyTrackedTasksCardEntry from '@/Components/Dashboard/RecentlyTrackedTasksCardEntry.vue';
import DashboardCard from '@/Components/Dashboard/DashboardCard.vue';
import { CheckCircleIcon } from '@heroicons/vue/20/solid';
const props = defineProps<{
    latestTasks: {
        id: string;
        name: string;
        project_name: string;
        project_id: string;
    }[];
}>();
</script>

<template>
    <DashboardCard title="Recently Tracked Tasks" :icon="CheckCircleIcon">
        <RecentlyTrackedTasksCardEntry
            v-for="lastTask in props.latestTasks"
            :key="lastTask.id"
            :project="lastTask.project_name"
            :title="lastTask.name"></RecentlyTrackedTasksCardEntry>
    </DashboardCard>
</template>
