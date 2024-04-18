<script setup lang="ts">
import RecentlyTrackedTasksCardEntry from '@/Components/Dashboard/RecentlyTrackedTasksCardEntry.vue';
import DashboardCard from '@/Components/Dashboard/DashboardCard.vue';
import { CheckCircleIcon } from '@heroicons/vue/20/solid';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { PlusCircleIcon } from '@heroicons/vue/24/solid';
import { router } from '@inertiajs/vue3';

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
            :class="props.latestTasks.length === 4 ? 'last:border-0' : ''"
            :key="lastTask.id"
            :project_id="lastTask.project_id"
            :task_id="lastTask.id"
            :title="lastTask.name"></RecentlyTrackedTasksCardEntry>
        <div
            v-if="props.latestTasks.length === 0"
            class="text-center flex flex-1 justify-center items-center">
            <div>
                <PlusCircleIcon
                    class="w-8 text-icon-default inline pb-2"></PlusCircleIcon>
                <h3 class="text-white font-semibold text-sm">
                    No recent tasks found
                </h3>
                <p class="pb-5 text-sm">Create tasks inside of a project!</p>
                <SecondaryButton @click="router.visit(route('projects'))"
                    >Go to Projects
                </SecondaryButton>
            </div>
        </div>
        <div
            v-if="props.latestTasks.length === 1"
            class="text-center flex flex-1 justify-center items-center">
            <div>
                <PlusCircleIcon
                    class="w-8 text-icon-default inline pb-2"></PlusCircleIcon>
                <h3 class="text-white font-semibold">Add more tasks</h3>
                <p class="pb-5">Create tasks inside of a project!</p>
                <SecondaryButton @click="router.visit(route('projects'))"
                    >Go to Projects
                </SecondaryButton>
            </div>
        </div>
    </DashboardCard>
</template>
