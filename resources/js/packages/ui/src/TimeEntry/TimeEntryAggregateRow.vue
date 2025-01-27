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
import { Checkbox } from '@/packages/ui/src';

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
    updateTimeEntry: (timeEntry: TimeEntry) => void;
    deleteTimeEntries: (timeEntries: TimeEntry[]) => void;
    currency: string;
    selectedTimeEntries: TimeEntry[];
    enableEstimatedTime: boolean;
    canCreateProject: boolean;
}>();
const emit = defineEmits<{
    selected: [TimeEntry[]];
    unselected: [TimeEntry[]];
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

function onSelectChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.checked) {
        emit('selected', [...props.timeEntry.timeEntries]);
    } else {
        emit('unselected', [...props.timeEntry.timeEntries]);
    }
}
</script>

<template>
    <div
        class="border-b border-default-background-separator min-w-0 transition"
        data-testid="time_entry_row">
        <MainContainer class="min-w-0">
            <div
                class="sm:flex py-2 items-center min-w-0 justify-between group">
                <div class="flex space-x-3 items-center min-w-0">
                    <Checkbox
                        @update:checked="onSelectChange"
                        :checked="
                            timeEntry.timeEntries.every(
                                (aggregateTimeEntry: TimeEntry) =>
                                    selectedTimeEntries.includes(
                                        aggregateTimeEntry
                                    )
                            )
                        " />
                    <div class="flex items-center min-w-0">
                        <GroupedItemsCountButton
                            :expanded="expanded"
                            @click="expanded = !expanded">
                            {{ timeEntry?.timeEntries?.length }}
                        </GroupedItemsCountButton>
                        <TimeEntryDescriptionInput
                            class="min-w-0 mr-4"
                            @changed="updateTimeEntryDescription"
                            :modelValue="
                                timeEntry.description
                            "></TimeEntryDescriptionInput>
                        <TimeTrackerProjectTaskDropdown
                            :clients
                            :createProject
                            :createClient
                            :canCreateProject
                            :projects="projects"
                            :tasks="tasks"
                            :showBadgeBorder="false"
                            @changed="updateProjectAndTask"
                            :project="timeEntry.project_id"
                            :enableEstimatedTime
                            :currency="currency"
                            class="border border-border-primary"
                            :task="
                                timeEntry.task_id
                            "></TimeTrackerProjectTaskDropdown>
                    </div>
                </div>
                <div class="flex items-center font-medium lg:space-x-2">
                    <TimeEntryRowTagDropdown
                        :createTag
                        :tags="tags"
                        @changed="updateTimeEntryTags"
                        :modelValue="timeEntry.tags"></TimeEntryRowTagDropdown>
                    <BillableToggleButton
                        :modelValue="timeEntry.billable"
                        class="opacity-50 focus-visible:opacity-100 group-hover:opacity-100"
                        size="small"
                        @changed="
                            updateTimeEntryBillable
                        "></BillableToggleButton>
                    <div class="flex-1">
                        <button
                            @click="expanded = !expanded"
                            class="hidden lg:block text-muted w-[105px] px-1 py-1.5 bg-transparent text-center hover:bg-card-background rounded-lg border border-transparent hover:border-card-border text-sm font-medium focus-visible:outline-none focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:bg-tertiary">
                            {{ formatStartEnd(timeEntry.start, timeEntry.end) }}
                        </button>
                    </div>
                    <button
                        @click="expanded = !expanded"
                        class="text-white min-w-[90px] px-2 py-1.5 bg-transparent text-center hover:bg-card-background rounded-lg border border-transparent hover:border-card-border text-sm font-semibold focus-visible:outline-none focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:bg-tertiary">
                        {{
                            formatHumanReadableDuration(timeEntry.duration ?? 0)
                        }}
                    </button>

                    <TimeTrackerStartStop
                        @changed="onStartStopClick(timeEntry)"
                        :active="!!(timeEntry.start && !timeEntry.end)"
                        class="opacity-20 hidden sm:flex group-hover:opacity-100 focus-visible:opacity-100"></TimeTrackerStartStop>
                    <TimeEntryMoreOptionsDropdown
                        @delete="
                            deleteTimeEntries(timeEntry?.timeEntries ?? [])
                        "></TimeEntryMoreOptionsDropdown>
                </div>
            </div>
        </MainContainer>
        <div
            v-if="expanded"
            class="w-full border-t border-default-background-separator bg-black/15">
            <TimeEntryRow
                :projects="projects"
                :enableEstimatedTime
                :canCreateProject
                :tasks="tasks"
                :selected="
                    !!selectedTimeEntries.find(
                        (filterEntry: TimeEntry) =>
                            filterEntry.id === subEntry.id
                    )
                "
                @selected="emit('selected', [subEntry])"
                @unselected="emit('unselected', [subEntry])"
                :createClient
                :clients
                :createProject
                :tags="tags"
                indent
                :updateTimeEntry="
                    (timeEntry: TimeEntry) => updateTimeEntry(timeEntry)
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
