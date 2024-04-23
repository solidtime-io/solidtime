<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import TimeTracker from '@/Components/TimeTracker.vue';
import { computed, onMounted, ref, watch } from 'vue';
import MainContainer from '@/Pages/MainContainer.vue';
import { useTimeEntriesStore } from '@/utils/useTimeEntries';
import { storeToRefs } from 'pinia';
import type { TimeEntry } from '@/utils/api';
import TimeEntryRowHeading from '@/Components/Common/TimeEntry/TimeEntryRowHeading.vue';
import TimeEntryRow from '@/Components/Common/TimeEntry/TimeEntryRow.vue';
import { useElementVisibility } from '@vueuse/core';
import { ClockIcon } from '@heroicons/vue/20/solid';
import { getLocalizedDateFromTimestamp } from '@/utils/time';

const timeEntriesStore = useTimeEntriesStore();
const { timeEntries, allTimeEntriesLoaded } = storeToRefs(timeEntriesStore);

const loading = ref(false);
const loadMoreContainer = ref<HTMLDivElement | null>(null);
const isLoadMoreVisible = useElementVisibility(loadMoreContainer);

watch(isLoadMoreVisible, async (isVisible) => {
    if (
        isVisible &&
        timeEntries.value.length > 0 &&
        !allTimeEntriesLoaded.value
    ) {
        loading.value = true;
        await timeEntriesStore.fetchMoreTimeEntries();
    }
});

onMounted(async () => {
    await timeEntriesStore.fetchTimeEntries();
});

const groupedTimeEntries = computed(() => {
    const groupedEntries: Record<string, TimeEntry[]> = {};
    for (const entry of timeEntries.value) {
        const oldEntries =
            groupedEntries[getLocalizedDateFromTimestamp(entry.start)];
        const newEntries = [...(oldEntries ?? []), entry];
        groupedEntries[getLocalizedDateFromTimestamp(entry.start)] = newEntries;
    }
    return groupedEntries;
});
</script>

<template>
    <AppLayout title="Dashboard" data-testid="time_view">
        <MainContainer
            class="pt-5 sm:pt-8 pb-4 sm:pb-6 border-b border-default-background-separator">
            <TimeTracker></TimeTracker>
        </MainContainer>
        <div v-for="(value, key) in groupedTimeEntries" :key="key">
            <TimeEntryRowHeading :date="key"></TimeEntryRowHeading>
            <TimeEntryRow
                :key="entry.id"
                v-for="entry in value"
                :time-entry="entry"></TimeEntryRow>
        </div>
        <div
            v-if="Object.keys(groupedTimeEntries).length === 0"
            class="text-center pt-12">
            <ClockIcon class="w-8 text-icon-default inline pb-2"></ClockIcon>
            <h3 class="text-white font-semibold">No time entries found</h3>
            <p class="pb-5">Create your first time entry now!</p>
        </div>
        <div ref="loadMoreContainer">
            <div
                v-if="loading && !allTimeEntriesLoaded"
                class="flex justify-center items-center py-5 text-white font-medium">
                <svg
                    class="animate-spin -ml-1 mr-3 h-5 w-5 text-white"
                    xmlns="http://www.w3.org/2000/svg"
                    fill="none"
                    viewBox="0 0 24 24">
                    <circle
                        class="opacity-25"
                        cx="12"
                        cy="12"
                        r="10"
                        stroke="currentColor"
                        stroke-width="4"></circle>
                    <path
                        class="opacity-75"
                        fill="currentColor"
                        d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                </svg>
                <span> Loading more time entries... </span>
            </div>
            <div
                v-else-if="allTimeEntriesLoaded"
                class="flex justify-center items-center py-5 text-muted font-medium">
                All time entries are loaded!
            </div>
        </div>
    </AppLayout>
</template>
