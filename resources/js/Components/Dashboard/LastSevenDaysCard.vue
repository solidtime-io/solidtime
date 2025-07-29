<script setup lang="ts">
import { useQuery } from '@tanstack/vue-query';
import { computed } from 'vue';
import DashboardCard from '@/Components/Dashboard/DashboardCard.vue';
import DayOverviewCardEntry from '@/Components/Dashboard/DayOverviewCardEntry.vue';
import { CalendarIcon } from '@heroicons/vue/20/solid';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { api } from '@/packages/api/src';
import { LoadingSpinner } from '@/packages/ui/src';

// Get the organization ID using the utility function
const organizationId = computed(() => getCurrentOrganizationId());

// Set up the query
const { data: last7Days, isLoading } = useQuery({
    queryKey: ['lastSevenDays', organizationId],
    queryFn: () => {
        return api.lastSevenDays({
            params: {
                organization: organizationId.value!,
            },
        });
    },
    enabled: computed(() => !!organizationId.value),
    placeholderData: Array.from({ length: 7 }, (_, i) => ({
        date: new Date(Date.now() - i * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
        duration: 0,
        history: Array(8).fill(0),
    })),
});
</script>

<template>
    <DashboardCard title="Last 7 Days" :icon="CalendarIcon">
        <div v-if="isLoading" class="flex justify-center items-center h-40">
            <LoadingSpinner />
        </div>
        <div v-else-if="last7Days">
            <DayOverviewCardEntry
                v-for="day in last7Days"
                :key="day.date"
                :class="last7Days.length === 7 ? 'last:border-0 first:pt-3' : ''"
                :date="day.date"
                :history="day.history"
                :duration="day.duration"></DayOverviewCardEntry>
        </div>
        <div v-else class="text-center text-gray-500 py-8">No data available</div>
    </DashboardCard>
</template>
