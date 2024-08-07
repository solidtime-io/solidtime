<script setup lang="ts">
import MainContainer from '@/Pages/MainContainer.vue';
import TimeTrackerStartStop from '@/Components/Common/TimeTrackerStartStop.vue';
import type {
    CreateClientBody,
    CreateProjectBody,
    Project,
    Tag,
    Task,
    TimeEntry,
    Client,
} from '@/utils/api';
import TimeEntryDescriptionInput from '@/Components/Common/TimeEntry/TimeEntryDescriptionInput.vue';
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
import type { TimeEntriesGroupedByType } from '@/types/time-entries';

const props = defineProps<{
    timeEntry: TimeEntriesGroupedByType;
    projects: Project[];
    tasks: Task[];
    tags: Tag[];
    clients: Client[];
    createTag: (name: string) => Promise<Tag | undefined>;
    createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
    onStartStopClick: (timeEntry: TimeEntry) => void;
    updateTimeEntries: (timeEntries: TimeEntry[]) => void;
    deleteTimeEntries: (timeEntries: TimeEntry[]) => void;
}>();

function updateTimeEntryDescription(description: string) {
    const updatedTimeEntries = props.timeEntry.timeEntries.map((entry) => {
        return { ...entry, description };
    });
    props.updateTimeEntries(updatedTimeEntries);
}

function updateTimeEntryTags(tags: string[]) {
    const updatedTimeEntries = props.timeEntry.timeEntries.map((entry) => {
        return { ...entry, tags };
    });
    props.updateTimeEntries(updatedTimeEntries);
}

function updateTimeEntryBillable(billable: boolean) {
    const updatedTimeEntries = props.timeEntry.timeEntries.map((entry) => {
        return { ...entry, billable };
    });
    props.updateTimeEntries(updatedTimeEntries);
}

function updateProjectAndTask(projectId: string, taskId: string) {
    const updatedTimeEntries = props.timeEntry.timeEntries.map((entry) => {
        return { ...entry, project_id: projectId, task_id: taskId };
    });
    props.updateTimeEntries(updatedTimeEntries);
}

const expanded = ref(false);
</script>

<template>
    <div
        class="border-b border-default-background-separator transition"
        data-testid="time_entry_row">
        <MainContainer>
            <div class="sm:flex py-1.5 items-center justify-between group">
                <div class="flex space-x-3 items-center min-w-0">
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
                        :clients
                        :createProject
                        :createClient
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
                        :createTag
                        :tags="tags"
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
                            class="hidden lg:block text-muted w-[110px] px-2 py-2 bg-transparent text-center hover:bg-card-background rounded-lg border border-transparent hover:border-card-border text-sm font-medium">
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
                        @changed="onStartStopClick(timeEntry)"
                        :active="!!(timeEntry.start && !timeEntry.end)"
                        class="opacity-20 hidden sm:flex group-hover:opacity-100"></TimeTrackerStartStop>
                    <TimeEntryMoreOptionsDropdown
                        @delete="
                            deleteTimeEntries([timeEntry])
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
                :createClient
                :clients
                :createProject
                :tags="tags"
                indent
                :updateTimeEntry="(arg) => updateTimeEntries([arg])"
                :onStartStopClick="() => onStartStopClick(subEntry)"
                :deleteTimeEntry="() => deleteTimeEntries([subEntry])"
                :createTag
                :key="subEntry.id"
                v-for="subEntry in timeEntry.timeEntries"
                :time-entry="subEntry"></TimeEntryRow>
        </div>
    </div>
</template>

<style scoped></style>
