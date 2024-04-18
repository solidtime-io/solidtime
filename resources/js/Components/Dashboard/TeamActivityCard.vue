<script lang="ts" setup>
import DashboardCard from '@/Components/Dashboard/DashboardCard.vue';
import TeamActivityCardEntry from '@/Components/Dashboard/TeamActivityCardEntry.vue';
import { UserGroupIcon } from '@heroicons/vue/20/solid';

defineProps<{
    latestTeamActivity: {
        user_id: string;
        name: string;
        description: string;
        time_entry_id: string;
        task_id: string;
        status: boolean;
    }[];
}>();
</script>

<template>
    <DashboardCard title="Team Activity" :icon="UserGroupIcon">
        <TeamActivityCardEntry
            v-for="activity in latestTeamActivity"
            :key="activity.user_id"
            :class="latestTeamActivity.length === 4 ? 'last:border-0' : ''"
            :name="activity.name"
            :description="activity.description"
            :working="activity.status"></TeamActivityCardEntry>
    </DashboardCard>
</template>
