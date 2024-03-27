<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import TimeTracker from '@/Components/TimeTracker.vue';
import { computed, onMounted } from 'vue';
import MainContainer from '@/Pages/MainContainer.vue';
import { useTimeEntriesStore } from '@/utils/useTimeEntries';
import { storeToRefs } from 'pinia';
import dayjs from 'dayjs';
import type { TimeEntry } from '@/utils/api';
import TimeEntryRowHeading from '@/Components/Common/TimeEntry/TimeEntryRowHeading.vue';
import TimeEntryRow from '@/Components/Common/TimeEntry/TimeEntryRow.vue';

const timeEntriesStore = useTimeEntriesStore();
const { timeEntries } = storeToRefs(timeEntriesStore);

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
    </AppLayout>
</template>
