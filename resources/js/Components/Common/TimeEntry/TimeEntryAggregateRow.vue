<script setup lang="ts">
import MainContainer from '@/Pages/MainContainer.vue';
import TimeTrackerStartStop from '@/Components/Common/TimeTrackerStartStop.vue';
import type { Project, Task, TimeEntry } from '@/utils/api';
import TimeEntryDescriptionInput from '@/Components/Common/TimeEntry/TimeEntryDescriptionInput.vue';
import { type TimeEntriesGroupedByType } from '@/utils/useTimeEntries';
import TimeEntryRowTagDropdown from '@/Components/Common/TimeEntry/TimeEntryRowTagDropdown.vue';
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

const props = defineProps<{
    timeEntry: TimeEntriesGroupedByType;
    projects: Project[];
    tasks: Task[];
}>();

const emit = defineEmits<{
    onStartStopClick: [timeEntry: TimeEntry];
    updateTimeEntries: [timeEntries: TimeEntry[]];
    deleteTimeEntries: [timeEntries: TimeEntry[]];
}>();

function updateTimeEntryDescription(description: string) {
    const timeEntries = props.timeEntry.timeEntries;
    timeEntries.forEach((entry) => {
        entry.description = description;
    });
    emit('updateTimeEntries', timeEntries);
}

function updateTimeEntryTags(tags: string[]) {
    const timeEntries = props.timeEntry.timeEntries as TimeEntry[];
    timeEntries.forEach((entry) => {
        entry.tags = tags;
    });
    emit('updateTimeEntries', timeEntries);
}

function updateTimeEntryBillable(billable: boolean) {
    const timeEntries = props.timeEntry.timeEntries as TimeEntry[];
    timeEntries.forEach((entry) => {
        entry.billable = billable;
    });
    emit('updateTimeEntries', timeEntries);
}

function updateProjectAndTask(projectId: string, taskId: string) {
    const timeEntries = props.timeEntry.timeEntries as TimeEntry[];
    timeEntries.forEach((entry) => {
        entry.project_id = projectId;
        entry.task_id = taskId;
    });
    emit('updateTimeEntries', timeEntries);
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
                    <div class="flex items-center">
                        <GroupedItemsCountButton
                            :expanded="expanded"
                            @click="expanded = !expanded">
                            {{ timeEntry?.timeEntries?.length }}
                        </GroupedItemsCountButton>
                        <TimeEntryDescriptionInput
                            @changed="updateTimeEntryDescription"
                            :modelValue="
                                timeEntry.description
                            "></TimeEntryDescriptionInput>
                    </div>
                    <TimeTrackerProjectTaskDropdown
                        :projects="projects"
                        :tasks="tasks"
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
                        @changed="emit('onStartStopClick', timeEntry)"
                        :active="!!(timeEntry.start && !timeEntry.end)"
                        class="opacity-20 hidden sm:flex group-hover:opacity-100"></TimeTrackerStartStop>
                    <TimeEntryMoreOptionsDropdown
                        @delete="
                            emit('deleteTimeEntries', timeEntry.timeEntries)
                        "></TimeEntryMoreOptionsDropdown>
                </div>
            </div>
        </MainContainer>
        <div
            v-if="expanded"
            class="w-full border-t border-default-background-separator bg-black/15">
            <TimeEntryRow
                :projects="projects"
                :tasks="tasks"
                indent
                @updateTimeEntry="
                    (timeEntry) => emit('updateTimeEntries', [timeEntry])
                "
                @onStartStopClick="emit('onStartStopClick', subEntry)"
                @deleteTimeEntry="emit('deleteTimeEntries', [subEntry])"
                :key="subEntry.id"
                v-for="subEntry in timeEntry.timeEntries"
                :time-entry="subEntry"></TimeEntryRow>
        </div>
    </div>
</template>

<style scoped></style>
