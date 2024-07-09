<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import TimeTracker from '@/Components/TimeTracker.vue';
import { computed, onMounted, ref, watch } from 'vue';
import MainContainer from '@/Pages/MainContainer.vue';
import {
    type TimeEntriesGroupedByType,
    useTimeEntriesStore,
} from '@/utils/useTimeEntries';
import { storeToRefs } from 'pinia';
import type { TimeEntry } from '@/utils/api';
import TimeEntryRowHeading from '@/Components/Common/TimeEntry/TimeEntryRowHeading.vue';
import TimeEntryRow from '@/Components/Common/TimeEntry/TimeEntryRow.vue';
import { useElementVisibility } from '@vueuse/core';
import { ClockIcon } from '@heroicons/vue/20/solid';
import { getDayJsInstance, getLocalizedDateFromTimestamp } from '@/utils/time';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { PlusIcon } from '@heroicons/vue/16/solid';
import TimeEntryCreateModal from '@/Components/Common/TimeEntry/TimeEntryCreateModal.vue';
import TimeEntryAggregateRow from '@/Components/Common/TimeEntry/TimeEntryAggregateRow.vue';
import LoadingSpinner from '@/Components/LoadingSpinner.vue';
import dayjs from 'dayjs';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { useTasksStore } from '@/utils/useTasks';
import { useProjectsStore } from '@/utils/useProjects';

const timeEntriesStore = useTimeEntriesStore();
const { timeEntries, allTimeEntriesLoaded } = storeToRefs(timeEntriesStore);
const { updateTimeEntry, fetchTimeEntries, createTimeEntry } =
    useTimeEntriesStore();

function updateTimeEntries(timeEntries: TimeEntry[]) {
    timeEntries.forEach((entry) => {
        useTimeEntriesStore().updateTimeEntry(entry);
    });
    fetchTimeEntries();
}

const loading = ref(false);
const loadMoreContainer = ref<HTMLDivElement | null>(null);
const isLoadMoreVisible = useElementVisibility(loadMoreContainer);

async function onStartStopClick(timeEntry: TimeEntry) {
    if (timeEntry.start && !timeEntry.end) {
        await updateTimeEntry({
            ...timeEntry,
            end: dayjs().utc().format(),
        });
    } else {
        await createTimeEntry({
            ...timeEntry,
            start: dayjs().utc().format(),
            end: null,
        });
    }
    fetchTimeEntries();
    useCurrentTimeEntryStore().fetchCurrentTimeEntry();
}

function deleteTimeEntries(timeEntries: TimeEntry[]) {
    timeEntries.forEach((entry) => {
        useTimeEntriesStore().deleteTimeEntry(entry.id);
    });
    fetchTimeEntries();
}

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
    const groupedEntriesByDay: Record<string, TimeEntry[]> = {};
    for (const entry of timeEntries.value) {
        // skip current time entry
        if (entry.end === null) {
            continue;
        }
        const oldEntries =
            groupedEntriesByDay[getLocalizedDateFromTimestamp(entry.start)];
        groupedEntriesByDay[getLocalizedDateFromTimestamp(entry.start)] = [
            ...(oldEntries ?? []),
            entry,
        ];
    }
    const groupedEntriesByDayAndType: Record<
        string,
        TimeEntriesGroupedByType[]
    > = {};
    for (const dailyEntriesKey in groupedEntriesByDay) {
        const dailyEntries = groupedEntriesByDay[dailyEntriesKey];
        const newDailyEntries: TimeEntriesGroupedByType[] = [];

        for (const entry of dailyEntries) {
            // check if same entry already exists
            const oldEntriesIndex = newDailyEntries.findIndex(
                (e) =>
                    e.project_id === entry.project_id &&
                    e.task_id === entry.task_id &&
                    e.billable === entry.billable &&
                    e.description === entry.description
            );
            if (oldEntriesIndex !== -1 && newDailyEntries[oldEntriesIndex]) {
                newDailyEntries[oldEntriesIndex].timeEntries.push(entry);

                // Add up durations for time entries of the same type
                newDailyEntries[oldEntriesIndex].duration =
                    (newDailyEntries[oldEntriesIndex].duration ?? 0) +
                    (entry?.duration ?? 0);

                // adapt start end times so they show the earliest start and latest end time
                if (
                    getDayJsInstance()(entry.start).isBefore(
                        getDayJsInstance()(
                            newDailyEntries[oldEntriesIndex].start
                        )
                    )
                ) {
                    newDailyEntries[oldEntriesIndex].start = entry.start;
                }
                if (
                    getDayJsInstance()(entry.end).isAfter(
                        getDayJsInstance()(newDailyEntries[oldEntriesIndex].end)
                    )
                ) {
                    newDailyEntries[oldEntriesIndex].end = entry.end;
                }
            } else {
                newDailyEntries.push({ ...entry, timeEntries: [entry] });
            }
        }

        groupedEntriesByDayAndType[dailyEntriesKey] = newDailyEntries;
    }
    return groupedEntriesByDayAndType;
});
const showManualTimeEntryModal = ref(false);
const projectStore = useProjectsStore();
const { projects } = storeToRefs(projectStore);
const taskStore = useTasksStore();
const { tasks } = storeToRefs(taskStore);
</script>

<template>
    <TimeEntryCreateModal
        v-model:show="showManualTimeEntryModal"></TimeEntryCreateModal>
    <AppLayout title="Dashboard" data-testid="time_view">
        <MainContainer
            class="pt-5 sm:pt-8 pb-4 sm:pb-6 border-b border-default-background-separator">
            <div
                class="sm:flex items-end sm:divide-x divide-default-background-separator divide-y sm:divide-y-0 space-y-2 sm:space-y-0 sm:space-x-2">
                <div class="flex-1">
                    <TimeTracker></TimeTracker>
                </div>
                <div class="pb-2 pt-2 sm:pt-0 sm:pl-4 flex justify-center">
                    <SecondaryButton
                        class="w-full text-center flex justify-center"
                        @click="showManualTimeEntryModal = true"
                        :icon="PlusIcon"
                        >Manual time entry
                    </SecondaryButton>
                </div>
            </div>
        </MainContainer>
        <div v-for="(value, key) in groupedTimeEntries" :key="key">
            <TimeEntryRowHeading :date="key"></TimeEntryRowHeading>
            <template v-for="entry in value" :key="entry.id">
                <TimeEntryAggregateRow
                    :projects="projects"
                    :tasks="tasks"
                    @onStartStopClick="onStartStopClick"
                    @updateTimeEntries="updateTimeEntries"
                    @deleteTimeEntries="deleteTimeEntries"
                    v-if="
                        'timeEntries' in entry && entry.timeEntries.length > 1
                    "
                    :time-entry="entry"></TimeEntryAggregateRow>
                <TimeEntryRow
                    :projects="projects"
                    :tasks="tasks"
                    @updateTimeEntry="updateTimeEntry"
                    @onStartStopClick="onStartStopClick(entry)"
                    @deleteTimeEntry="deleteTimeEntries([entry])"
                    v-else
                    :time-entry="entry"></TimeEntryRow>
            </template>
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
                <LoadingSpinner></LoadingSpinner>
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
