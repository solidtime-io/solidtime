<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import TimeTrackerStartStop from '../TimeTrackerStartStop.vue';
import type {
    CreateClientBody,
    CreateProjectBody,
    Project,
    Tag,
    Task,
    TimeEntry,
    Client,
} from '@/packages/api/src';
import TimeEntryDescriptionInput from '@/packages/ui/src/TimeEntry/TimeEntryDescriptionInput.vue';
import TimeEntryRowTagDropdown from '@/packages/ui/src/TimeEntry/TimeEntryRowTagDropdown.vue';
import TimeEntryMoreOptionsDropdown from '@/packages/ui/src/TimeEntry/TimeEntryMoreOptionsDropdown.vue';
import TimeTrackerProjectTaskDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import BillableToggleButton from '@/packages/ui/src/Input/BillableToggleButton.vue';
import { ref } from 'vue';
import {
    formatHumanReadableDuration,
    formatStartEnd,
} from '@/packages/ui/src/utils/time';
import TimeEntryRow from '@/packages/ui/src/TimeEntry/TimeEntryRow.vue';
import GroupedItemsCountButton from '@/packages/ui/src/GroupedItemsCountButton.vue';
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
    updateTimeEntries: (ids: string[], changes: Partial<TimeEntry>) => void;
    deleteTimeEntries: (timeEntries: TimeEntry[]) => void;
    currency: string;
}>();

function updateTimeEntryDescription(description: string) {
    props.updateTimeEntries(
        props.timeEntry.timeEntries.map((timeEntry: TimeEntry) => timeEntry.id),
        { description: description }
    );
}

function updateTimeEntryTags(tags: string[]) {
    props.updateTimeEntries(
        props.timeEntry.timeEntries.map((timeEntry: TimeEntry) => timeEntry.id),
        { tags: tags }
    );
}

function updateTimeEntryBillable(billable: boolean) {
    props.updateTimeEntries(
        props.timeEntry.timeEntries.map((timeEntry: TimeEntry) => timeEntry.id),
        { billable: billable }
    );
}

function updateProjectAndTask(projectId: string, taskId: string) {
    props.updateTimeEntries(
        props.timeEntry.timeEntries.map((timeEntry: TimeEntry) => timeEntry.id),
        { project_id: projectId, task_id: taskId }
    );
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
                        :currency="currency"
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
                :updateTimeEntry="
                    (timeEntry: TimeEntry) =>
                        updateTimeEntries([timeEntry.id], { ...timeEntry })
                "
                :onStartStopClick="() => onStartStopClick(subEntry)"
                :deleteTimeEntry="() => deleteTimeEntries([subEntry])"
                :currency="currency"
                :createTag
                :key="subEntry.id"
                v-for="subEntry in timeEntry.timeEntries"
                :time-entry="subEntry"></TimeEntryRow>
        </div>
    </div>
</template>

<style scoped></style>
