<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import TimeTracker from '@/Components/TimeTracker.vue';
import { computed, onMounted, ref, watch } from 'vue';
import MainContainer from '@/Pages/MainContainer.vue';
import { useTimeEntriesStore } from '@/utils/useTimeEntries';
import { storeToRefs } from 'pinia';
import dayjs from 'dayjs';
import type { TimeEntry } from '@/utils/api';
import TimeEntryRowHeading from '@/Components/Common/TimeEntry/TimeEntryRowHeading.vue';
import TimeEntryRow from '@/Components/Common/TimeEntry/TimeEntryRow.vue';
import { useElementVisibility } from '@vueuse/core';

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
            groupedEntries[dayjs(entry.start).utc().format('YYYY-MM-DD')];
        const newEntries = [...(oldEntries ?? []), entry];
        groupedEntries[dayjs(entry.start).utc().format('YYYY-MM-DD')] =
            newEntries;
    }
    return groupedEntries;
});
</script>

<template>
    <AppLayout title="Dashboard" data-testid="dashboard_view">
        <MainContainer
            class="py-8 border-b border-default-background-seperator">
            <TimeTracker></TimeTracker>
        </MainContainer>
        <div v-for="(value, key) in groupedTimeEntries" :key="key">
            <TimeEntryRowHeading :date="key"></TimeEntryRowHeading>
            <TimeEntryRow
                :key="entry.id"
                v-for="entry in value"
                :time-entry="entry"></TimeEntryRow>
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
                class="flex justify-center items-center py-5 text-white font-medium">
                All time entries are loaded!
            </div>
        </div>
    </AppLayout>
</template>