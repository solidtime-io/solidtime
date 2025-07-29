<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query';
import { computed } from 'vue';
import RecentlyTrackedTasksCardEntry from '@/Components/Dashboard/RecentlyTrackedTasksCardEntry.vue';
import DashboardCard from '@/Components/Dashboard/DashboardCard.vue';
import { CheckCircleIcon } from '@heroicons/vue/20/solid';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { PlusCircleIcon } from '@heroicons/vue/24/solid';
import { router } from '@inertiajs/vue3';
import { getCurrentMembershipId, getCurrentOrganizationId } from '@/utils/useUser';
import { api } from '@/packages/api/src';
import { LoadingSpinner } from '@/packages/ui/src';

// Get the organization ID using the utility function
const organizationId = computed(() => getCurrentOrganizationId());

// Function to fetch latest tasks using the API client

// Set up the query
const {
    data: timeEntriesResponse,
    isLoading,
    refetch,
} = useQuery({
    queryKey: ['timeEntries', organizationId],
    queryFn: () => {
        return api.getTimeEntries({
            params: {
                organization: organizationId.value!,
            },
            queries: {
                member_id: getCurrentMembershipId(),
            },
        });
    },
    enabled: computed(() => !!organizationId.value),
});

const latestTasks = computed(() => {
    if (!timeEntriesResponse.value) {
        return [];
    }

    return timeEntriesResponse.value.data;
});

const filteredLatestTasks = computed(() => {
    // do not include running time entries
    const finishedTimeEntries = latestTasks.value.filter((item) => item.end !== null);

    // filter out duplicates based on description, task, project, tags and billable
    return finishedTimeEntries
        .filter((item, index, self) => {
            return (
                index ===
                self.findIndex(
                    (t) =>
                        t.description === item.description &&
                        t.task_id === item.task_id &&
                        t.project_id === item.project_id &&
                        t.tags.length === item.tags.length &&
                        t.tags.every((tag) => item.tags.includes(tag)) &&
                        t.billable === item.billable
                )
            );
        })
        .slice(0, 4);
});

// Listen for dashboard refresh events
window.addEventListener('dashboard:refresh', () => {
    refetch();
});
</script>

<template>
    <DashboardCard title="Recent Time Entries" :icon="CheckCircleIcon">
        <div v-if="isLoading" class="flex justify-center items-center h-40">
            <LoadingSpinner />
        </div>
        <div v-else-if="filteredLatestTasks && filteredLatestTasks.length > 0">
            <RecentlyTrackedTasksCardEntry
                v-for="lastTask in filteredLatestTasks"
                :key="lastTask.id"
                :time-entry="lastTask"
                :class="
                    filteredLatestTasks.length === 4 ? 'last:border-0' : ''
                "></RecentlyTrackedTasksCardEntry>
        </div>
        <div v-else class="text-center flex flex-1 justify-center items-center">
            <div>
                <PlusCircleIcon class="w-8 text-icon-default inline pb-2"></PlusCircleIcon>
                <h3 class="text-text-primary font-semibold text-sm">No recent tasks found</h3>
                <p class="pb-5 text-sm">Create tasks inside of a project!</p>
                <SecondaryButton @click="router.visit(route('projects'))"
                    >Go to Projects
                </SecondaryButton>
            </div>
        </div>
        <div
            v-if="latestTasks && latestTasks.length === 1"
            class="text-center flex flex-1 justify-center items-center text-sm">
            <div>
                <PlusCircleIcon class="w-8 text-icon-default inline pb-2"></PlusCircleIcon>
                <h3 class="text-text-primary font-semibold">Add more tasks</h3>
                <p class="pb-5">Create tasks inside of a project!</p>
                <SecondaryButton @click="router.visit(route('projects'))"
                    >Go to Projects
                </SecondaryButton>
            </div>
        </div>
    </DashboardCard>
</template>
