<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import TimeTrackerStartStop from '@/packages/ui/src/TimeTrackerStartStop.vue';
import TimeEntryRangeSelector from '@/packages/ui/src/TimeEntry/TimeEntryRangeSelector.vue';
import type {
    Client,
    CreateClientBody,
    CreateProjectBody,
    Project,
    Tag,
    Task,
    TimeEntry,
} from '@/packages/api/src';
import TimeEntryDescriptionInput from '@/packages/ui/src/TimeEntry/TimeEntryDescriptionInput.vue';
import TimeEntryRowTagDropdown from '@/packages/ui/src/TimeEntry/TimeEntryRowTagDropdown.vue';
import TimeEntryRowDurationInput from '@/packages/ui/src/TimeEntry/TimeEntryRowDurationInput.vue';
import TimeEntryMoreOptionsDropdown from '@/packages/ui/src/TimeEntry/TimeEntryMoreOptionsDropdown.vue';
import TimeTrackerProjectTaskDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import BillableToggleButton from '@/packages/ui/src/Input/BillableToggleButton.vue';

const props = defineProps<{
    timeEntry: TimeEntry;
    indent?: boolean;
    projects: Project[];
    tasks: Task[];
    tags: Tag[];
    clients: Client[];
    createTag: (name: string) => Promise<Tag | undefined>;
    createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
    onStartStopClick: () => void;
    deleteTimeEntry: () => void;
    updateTimeEntry: (timeEntry: TimeEntry) => void;
    currency: string;
}>();

function updateTimeEntryDescription(description: string) {
    props.updateTimeEntry({ ...props.timeEntry, description });
}

function updateTimeEntryTags(tags: string[]) {
    props.updateTimeEntry({ ...props.timeEntry, tags });
}

function updateTimeEntryBillable(billable: boolean) {
    props.updateTimeEntry({ ...props.timeEntry, billable });
}

function updateStartEndTime(start: string, end: string | null) {
    props.updateTimeEntry({ ...props.timeEntry, start, end });
}

function updateProjectAndTask(projectId: string, taskId: string) {
    props.updateTimeEntry({
        ...props.timeEntry,
        project_id: projectId,
        task_id: taskId,
    });
}
</script>

<template>
    <div
        class="border-b border-default-background-separator transition min-w-0"
        data-testid="time_entry_row">
        <MainContainer class="min-w-0">
            <div
                class="sm:flex py-1 lg:py-1.5 min-w-0 items-center justify-between group">
                <div class="flex space-x-1 items-center min-w-0">
                    <input
                        type="checkbox"
                        class="h-4 w-4 rounded bg-card-background border-input-border text-accent-500/80 focus:ring-accent-500/80" />
                    <div class="w-7 h-7" v-if="indent === true"></div>
                    <TimeEntryDescriptionInput
                        @changed="updateTimeEntryDescription"
                        :modelValue="
                            timeEntry.description
                        "></TimeEntryDescriptionInput>
                    <TimeTrackerProjectTaskDropdown
                        :createProject
                        :createClient
                        :clients
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
                        @changed="updateTimeEntryTags"
                        :createTag
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
    </div>
</template>

<style scoped></style>
