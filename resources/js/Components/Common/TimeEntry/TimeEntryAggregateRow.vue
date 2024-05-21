<script setup lang="ts">
import MainContainer from '@/Pages/MainContainer.vue';
import TimeTrackerStartStop from '@/Components/Common/TimeTrackerStartStop.vue';
import type { TimeEntry } from '@/utils/api';
import { storeToRefs } from 'pinia';
import TimeEntryDescriptionInput from '@/Components/Common/TimeEntry/TimeEntryDescriptionInput.vue';
import {
    type TimeEntriesGroupedByType,
    useTimeEntriesStore,
} from '@/utils/useTimeEntries';
import TimeEntryRowTagDropdown from '@/Components/Common/TimeEntry/TimeEntryRowTagDropdown.vue';
import dayjs from 'dayjs';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import TimeEntryMoreOptionsDropdown from '@/Components/Common/TimeEntry/TimeEntryMoreOptionsDropdown.vue';
import TimeTrackerProjectTaskDropdown from '@/Components/Common/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import BillableToggleButton from '@/Components/Common/BillableToggleButton.vue';
import { ref } from 'vue';
import {
    formatHumanReadableDuration,
    formatStartEnd,
} from '../../../utils/time';
import TimeEntryRow from '@/Components/Common/TimeEntry/TimeEntryRow.vue';
import GroupedItemsCountButton from '@/Components/Common/GroupedItemsCountButton.vue';

const currentTimeEntryStore = useCurrentTimeEntryStore();
const { stopTimer } = currentTimeEntryStore;
const { currentTimeEntry } = storeToRefs(currentTimeEntryStore);

const props = defineProps<{
    timeEntry: TimeEntriesGroupedByType;
}>();

const { updateTimeEntry, createTimeEntry, fetchTimeEntries } =
    useTimeEntriesStore();

async function onStartStopClick() {
    if (props.timeEntry.start && !props.timeEntry.end) {
        await updateTimeEntry({
            ...props.timeEntry,
            end: dayjs().utc().format(),
        });
    } else {
        if (currentTimeEntry.value.id) {
            await stopTimer();
        }
        await createTimeEntry({
            ...props.timeEntry,
            start: dayjs().utc().format(),
            end: null,
        });
    }
    useCurrentTimeEntryStore().fetchCurrentTimeEntry();
    fetchTimeEntries();
}

function deleteTimeEntry() {
    const timeEntries = props.timeEntry.timeEntries;
    timeEntries.forEach((entry) => {
        useTimeEntriesStore().deleteTimeEntry(entry.id);
    });
    fetchTimeEntries();
}

function updateTimeEntryDescription(description: string) {
    const timeEntries = props.timeEntry.timeEntries;
    timeEntries.forEach((entry) => {
        updateTimeEntry({ ...entry, description });
        entry.description = description;
    });
}

function updateTimeEntryTags(tags: string[]) {
    const timeEntries = props.timeEntry.timeEntries as TimeEntry[];
    timeEntries.forEach((entry) => {
        updateTimeEntry({ ...entry, tags });
        entry.tags = tags;
    });
}

function updateTimeEntryBillable(billable: boolean) {
    const timeEntries = props.timeEntry.timeEntries as TimeEntry[];
    timeEntries.forEach((entry) => {
        updateTimeEntry({ ...entry, billable });
        entry.billable = billable;
    });
}

function updateProjectAndTask(projectId: string, taskId: string) {
    const timeEntries = props.timeEntry.timeEntries as TimeEntry[];
    timeEntries.forEach((entry) => {
        updateTimeEntry({
            ...entry,
            project_id: projectId,
            task_id: taskId,
        });
        entry.project_id = projectId;
        entry.task_id = taskId;
    });
}

const expanded = ref(false);
</script>

<template>
    <div
        class="border-b border-default-background-separator transition"
        data-testid="time_entry_row">
        <MainContainer>
            <div class="sm:flex py-1.5 items-center justify-between group">
                <div class="flex space-x-3 items-center">
                    <input
                        type="checkbox"
                        class="h-4 w-4 rounded bg-card-background border-input-border text-accent-500/80 focus:ring-accent-500/80" />
                    <GroupedItemsCountButton
                        :expanded="expanded"
                        @click="expanded = !expanded">
                        {{ timeEntry?.timeEntries?.length }}
                    </GroupedItemsCountButton>
                    <TimeEntryDescriptionInput
                        @changed="updateTimeEntryDescription"
                        class="flex-1"
                        :modelValue="
                            timeEntry.description
                        "></TimeEntryDescriptionInput>
                    <TimeTrackerProjectTaskDropdown
                        :showBadgeBorder="false"
                        @changed="updateProjectAndTask"
                        :project="timeEntry.project_id"
                        :task="
                            timeEntry.task_id
                        "></TimeTrackerProjectTaskDropdown>
                </div>
                <div class="flex items-center font-medium space-x-2">
                    <TimeEntryRowTagDropdown
                        @changed="updateTimeEntryTags"
                        :modelValue="timeEntry.tags"></TimeEntryRowTagDropdown>
                    <BillableToggleButton
                        :modelValue="timeEntry.billable"
                        size="small"
                        @changed="
                            updateTimeEntryBillable
                        "></BillableToggleButton>
                    <div class="flex-1">
                        <button
                            @click="expanded = !expanded"
                            class="text-muted w-[110px] px-2 py-2 bg-transparent text-center hover:bg-card-background rounded-lg border border-transparent hover:border-card-border text-sm font-medium">
                            {{ formatStartEnd(timeEntry.start, timeEntry.end) }}
                        </button>
                    </div>
                    <button
                        @click="expanded = !expanded"
                        class="text-white w-[100px] px-3 py-2 bg-transparent text-center hover:bg-card-background rounded-lg border border-transparent hover:border-card-border text-sm font-semibold">
                        {{
                            formatHumanReadableDuration(timeEntry.duration ?? 0)
                        }}
                    </button>

                    <TimeTrackerStartStop
                        @changed="onStartStopClick"
                        :active="!!(timeEntry.start && !timeEntry.end)"
                        class="opacity-20 hidden sm:flex group-hover:opacity-100"></TimeTrackerStartStop>
                    <TimeEntryMoreOptionsDropdown
                        @delete="
                            deleteTimeEntry
                        "></TimeEntryMoreOptionsDropdown>
                </div>
            </div>
        </MainContainer>
        <div
            v-if="expanded"
            class="w-full border-t border-default-background-separator bg-black/15">
            <TimeEntryRow
                indent
                :key="subEntry.id"
                v-for="subEntry in timeEntry.timeEntries"
                :time-entry="subEntry"></TimeEntryRow>
        </div>
    </div>
</template>

<style scoped></style>
