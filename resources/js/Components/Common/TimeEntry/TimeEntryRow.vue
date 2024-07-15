<script setup lang="ts">
import MainContainer from '@/Pages/MainContainer.vue';
import TimeTrackerStartStop from '@/Components/Common/TimeTrackerStartStop.vue';
import TimeEntryRangeSelector from '@/Components/Common/TimeEntry/TimeEntryRangeSelector.vue';
import type { Project, Tag, Task, TimeEntry } from '@/utils/api';
import TimeEntryDescriptionInput from '@/Components/Common/TimeEntry/TimeEntryDescriptionInput.vue';
import TimeEntryRowTagDropdown from '@/Components/Common/TimeEntry/TimeEntryRowTagDropdown.vue';
import TimeEntryRowDurationInput from '@/Components/Common/TimeEntry/TimeEntryRowDurationInput.vue';
import TimeEntryMoreOptionsDropdown from '@/Components/Common/TimeEntry/TimeEntryMoreOptionsDropdown.vue';
import TimeTrackerProjectTaskDropdown from '@/Components/Common/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import BillableToggleButton from '@/Components/Common/BillableToggleButton.vue';

const props = defineProps<{
    timeEntry: TimeEntry;
    indent?: boolean;
    projects: Project[];
    tasks: Task[];
    tags: Tag[];
}>();

const emit = defineEmits<{
    onStartStopClick: [];
    deleteTimeEntry: [];
    updateTimeEntry: [timeEntry: TimeEntry];
    createTag: [name: string, callback: (tag: Tag) => void];
}>();

function updateTimeEntryDescription(description: string) {
    emit('updateTimeEntry', { ...props.timeEntry, description });
}

function updateTimeEntryTags(tags: string[]) {
    emit('updateTimeEntry', { ...props.timeEntry, tags });
}

function updateTimeEntryBillable(billable: boolean) {
    emit('updateTimeEntry', { ...props.timeEntry, billable });
}

function updateStartEndTime(start: string, end: string | null) {
    emit('updateTimeEntry', { ...props.timeEntry, start, end });
}

function updateProjectAndTask(projectId: string, taskId: string) {
    emit('updateTimeEntry', {
        ...props.timeEntry,
        project_id: projectId,
        task_id: taskId,
    });
}
</script>

<template>
    <div
        class="border-b border-default-background-separator transition"
        data-testid="time_entry_row">
        <MainContainer>
            <div
                class="sm:flex py-1 lg:py-1.5 items-center justify-between group">
                <div class="flex space-x-1 items-center">
                    <input
                        type="checkbox"
                        class="h-4 w-4 rounded bg-card-background border-input-border text-accent-500/80 focus:ring-accent-500/80" />
                    <div class="w-7 h-7" v-if="indent === true"></div>
                    <TimeEntryDescriptionInput
                        class="flex-1 max-w-[220px] md:max-w-[400px] text-ellipsis overflow-ellipsis"
                        @changed="updateTimeEntryDescription"
                        :modelValue="
                            timeEntry.description
                        "></TimeEntryDescriptionInput>
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
                <div class="flex items-center font-medium lg:space-x-2">
                    <TimeEntryRowTagDropdown
                        @changed="updateTimeEntryTags"
                        @createTag="(...args) => emit('createTag', ...args)"
                        :tags="tags"
                        :modelValue="timeEntry.tags"></TimeEntryRowTagDropdown>
                    <BillableToggleButton
                        :modelValue="timeEntry.billable"
                        size="small"
                        @changed="
                            updateTimeEntryBillable
                        "></BillableToggleButton>
                    <div class="flex-1">
                        <TimeEntryRangeSelector
                            class="hidden lg:block"
                            :start="timeEntry.start"
                            :end="timeEntry.end"
                            @changed="
                                updateStartEndTime
                            "></TimeEntryRangeSelector>
                    </div>
                    <TimeEntryRowDurationInput
                        :start="timeEntry.start"
                        :end="timeEntry.end"
                        @changed="
                            updateStartEndTime
                        "></TimeEntryRowDurationInput>
                    <TimeTrackerStartStop
                        @changed="emit('onStartStopClick')"
                        :active="!!(timeEntry.start && !timeEntry.end)"
                        class="opacity-20 hidden sm:flex group-hover:opacity-100"></TimeTrackerStartStop>
                    <TimeEntryMoreOptionsDropdown
                        @delete="
                            emit('deleteTimeEntry')
                        "></TimeEntryMoreOptionsDropdown>
                </div>
            </div>
        </MainContainer>
    </div>
</template>

<style scoped></style>
