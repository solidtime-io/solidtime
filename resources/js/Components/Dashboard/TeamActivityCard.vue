<script lang="ts" setup>
import { useQuery } from '@tanstack/vue-query';
import { computed } from 'vue';
import DashboardCard from '@/Components/Dashboard/DashboardCard.vue';
import TeamActivityCardEntry from '@/Components/Dashboard/TeamActivityCardEntry.vue';
import { UserGroupIcon } from '@heroicons/vue/20/solid';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { api } from '@/packages/api/src';
import { LoadingSpinner } from '@/packages/ui/src';
import { router } from '@inertiajs/vue3';

// Get the organization ID using the utility function
const organizationId = computed(() => getCurrentOrganizationId());

// Set up the query
const { data: latestTeamActivity, isLoading } = useQuery({
    queryKey: ['latestTeamActivity', organizationId],
    queryFn: () => {
        return api.latestTeamActivity({
            params: {
                organization: organizationId.value!,
            },
        });
    },
    enabled: computed(() => !!organizationId.value),
});
</script>

<template>
    <DashboardCard title="Team Activity" :icon="UserGroupIcon">
        <div v-if="isLoading" class="flex justify-center items-center h-40">
            <LoadingSpinner />
        </div>
        <div v-else-if="latestTeamActivity">
            <TeamActivityCardEntry
                v-for="activity in latestTeamActivity"
                :key="activity.time_entry_id"
                :class="latestTeamActivity.length === 4 ? 'last:border-0' : ''"
                :name="activity.name"
                :description="activity.description"
                :working="activity.status"></TeamActivityCardEntry>
        </div>
        <div v-else class="text-center text-gray-500 py-8">No team activity found</div>
        <div
            v-if="latestTeamActivity && latestTeamActivity.length <= 1"
            :class="latestTeamActivity?.length === 1 ? 'pb-5' : 'py-5'"
            class="text-center flex flex-1 justify-center items-center">
            <div>
                <UserGroupIcon class="w-8 text-icon-default inline pb-2"></UserGroupIcon>
                <h3 class="text-text-primary font-semibold text-sm">Invite your co-workers</h3>
                <p class="pb-5 text-sm">You can invite your entire team.</p>
                <SecondaryButton @click="router.visit(route('members'))"
                    >Go to Members
                </SecondaryButton>
            </div>
        </div>
    </DashboardCard>
</template>
