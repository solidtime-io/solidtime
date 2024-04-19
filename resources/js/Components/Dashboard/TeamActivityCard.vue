<script lang="ts" setup>
import DashboardCard from '@/Components/Dashboard/DashboardCard.vue';
import TeamActivityCardEntry from '@/Components/Dashboard/TeamActivityCardEntry.vue';
import { UserGroupIcon } from '@heroicons/vue/20/solid';
import { router } from '@inertiajs/vue3';
import SecondaryButton from '@/Components/SecondaryButton.vue';

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
        <div
            v-if="latestTeamActivity.length <= 2"
            class="text-center flex flex-1 justify-center items-center">
            <div>
                <UserGroupIcon
                    class="w-8 text-icon-default inline pb-2"></UserGroupIcon>
                <h3 class="text-white font-semibold text-sm">
                    Invite your co-workers
                </h3>
                <p class="pb-5 text-sm">You can invite your entire team.</p>
                <SecondaryButton @click="router.visit(route('members'))"
                    >Go to Members
                </SecondaryButton>
            </div>
        </div>
    </DashboardCard>
</template>
