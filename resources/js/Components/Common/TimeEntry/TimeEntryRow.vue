<script setup lang="ts">
import MainContainer from '@/Pages/MainContainer.vue';
import TimeTrackerStartStop from '@/Components/Common/TimeTrackerStartStop.vue';
import TimeEntryRangeSelector from '@/Components/Common/TimeEntry/TimeEntryRangeSelector.vue';
import type { Project, TimeEntry } from '@/utils/api';
import { computed } from 'vue';
import { useProjectsStore } from '@/utils/useProjects';
import { storeToRefs } from 'pinia';
import ProjectDropdown from '@/Components/Common/Project/ProjectDropdown.vue';
import TimeEntryDescriptionInput from '@/Components/Common/TimeEntry/TimeEntryDescriptionInput.vue';
import { useTimeEntriesStore } from '@/utils/useTimeEntries';
import TimeEntryRowTagDropdown from '@/Components/Common/TimeEntry/TimeEntryRowTagDropdown.vue';
import TimeEntryRowDurationInput from '@/Components/Common/TimeEntry/TimeEntryRowDurationInput.vue';
import dayjs from 'dayjs';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import TimeEntryMoreOptionsDropdown from '@/Components/Common/TimeEntry/TimeEntryMoreOptionsDropdown.vue';

const projectsStore = useProjectsStore();
const { projects } = storeToRefs(projectsStore);

const currentTimeEntryStore = useCurrentTimeEntryStore();
const { stopTimer, updateTimer } = currentTimeEntryStore;
const { currentTimeEntry } = storeToRefs(currentTimeEntryStore);

const props = defineProps<{
    timeEntry: TimeEntry;
}>();

const { updateTimeEntry, createTimeEntry, fetchTimeEntries } =
    useTimeEntriesStore();

const timeEntryProject = computed<Project | undefined>(() => {
    return projects.value.find(
        (project) => project.id === props.timeEntry.project_id
    );
});

async function updateStartEndTime(start: string, end: string | null) {
    if (currentTimeEntry.value.id === props.timeEntry.id) {
        currentTimeEntry.value.start = start;
        currentTimeEntry.value.end = end;
        await updateTimer();
    } else {
        await updateTimeEntry({ ...props.timeEntry, start, end });
    }
    await fetchTimeEntries();
}

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
    useTimeEntriesStore().deleteTimeEntry(props.timeEntry.id);
    fetchTimeEntries();
}

function updateTimeEntryDescription(description: string) {
    updateTimeEntry({ ...props.timeEntry, description });
}
</script>

<template>
    <div
        class="border-b border-card-border transition"
        data-testid="time_entry_row">
        <MainContainer>
            <div class="flex py-1.5 items-center justify-between group">
                <div class="flex space-x-1 items-center">
                    <input
                        type="checkbox"
                        class="h-4 w-4 rounded bg-card-background border-input-border text-accent-500/80 focus:ring-accent-500/80" />
                    <TimeEntryDescriptionInput
                        @changed="updateTimeEntryDescription"
                        :modelValue="
                            timeEntry.description
                        "></TimeEntryDescriptionInput>
                    <ProjectDropdown
                        :border="false"
                        :value="timeEntryProject"></ProjectDropdown>
                </div>
                <div class="flex items-center font-medium space-x-2">
                    <TimeEntryRowTagDropdown
                        @changed="updateTimeEntry(timeEntry)"
                        :modelValue="timeEntry.tags"></TimeEntryRowTagDropdown>
                    <div>
                        <TimeEntryRangeSelector
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
                        @changed="onStartStopClick"
                        :active="!!(timeEntry.start && !timeEntry.end)"
                        class="opacity-20 group-hover:opacity-100"></TimeTrackerStartStop>
                    <TimeEntryMoreOptionsDropdown
                        @delete="
                            deleteTimeEntry
                        "></TimeEntryMoreOptionsDropdown>
                </div>
            </div>
        </MainContainer>
    </div>
</template>

<style scoped></style>
