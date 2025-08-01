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
    Organization,
} from '@/packages/api/src';
import TimeEntryDescriptionInput from '@/packages/ui/src/TimeEntry/TimeEntryDescriptionInput.vue';
import TimeEntryRowTagDropdown from '@/packages/ui/src/TimeEntry/TimeEntryRowTagDropdown.vue';
import TimeEntryMoreOptionsDropdown from '@/packages/ui/src/TimeEntry/TimeEntryMoreOptionsDropdown.vue';
import TimeTrackerProjectTaskDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import BillableToggleButton from '@/packages/ui/src/Input/BillableToggleButton.vue';
import { ref, inject, type ComputedRef } from 'vue';
import { formatHumanReadableDuration, formatStartEnd } from '@/packages/ui/src/utils/time';
import TimeEntryRow from '@/packages/ui/src/TimeEntry/TimeEntryRow.vue';
import GroupedItemsCountButton from '@/packages/ui/src/GroupedItemsCountButton.vue';
import type { TimeEntriesGroupedByType } from '@/types/time-entries';
import { Checkbox } from '@/packages/ui/src';
import { twMerge } from 'tailwind-merge';
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

const organization = inject<ComputedRef<Organization>>('organization');

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

function onSelectChange(checked: boolean) {
    if (checked) {
        emit('selected', [...props.timeEntry.timeEntries]);
    } else {
        emit('unselected', [...props.timeEntry.timeEntries]);
    }
}
</script>

<template>
    <div
        class="border-b border-default-background-separator bg-row-background min-w-0 transition"
        data-testid="time_entry_row">
        <MainContainer class="min-w-0">
            <div class="sm:flex py-1.5 items-center min-w-0 justify-between group">
                <div class="flex space-x-3 items-center min-w-0">
                    <Checkbox
                        :checked="
                            timeEntry.timeEntries.every((aggregateTimeEntry: TimeEntry) =>
                                selectedTimeEntries.includes(aggregateTimeEntry)
                            )
                        "
                        @update:checked="onSelectChange" />
                    <div class="flex items-center min-w-0">
                        <GroupedItemsCountButton :expanded="expanded" @click="expanded = !expanded">
                            {{ timeEntry?.timeEntries?.length }}
                        </GroupedItemsCountButton>
                        <TimeEntryDescriptionInput
                            class="min-w-0 mr-4"
                            :model-value="timeEntry.description"
                            @changed="updateTimeEntryDescription"></TimeEntryDescriptionInput>
                        <TimeTrackerProjectTaskDropdown
                            :clients
                            :create-project
                            :create-client
                            :can-create-project
                            :projects="projects"
                            :tasks="tasks"
                            :show-badge-border="false"
                            :project="timeEntry.project_id"
                            :enable-estimated-time
                            :currency="currency"
                            :task="timeEntry.task_id"
                            @changed="updateProjectAndTask"></TimeTrackerProjectTaskDropdown>
                    </div>
                </div>
                <div class="flex items-center font-medium lg:space-x-2">
                    <TimeEntryRowTagDropdown
                        :create-tag
                        :tags="tags"
                        :model-value="timeEntry.tags"
                        @changed="updateTimeEntryTags"></TimeEntryRowTagDropdown>
                    <BillableToggleButton
                        :model-value="timeEntry.billable"
                        class="opacity-50 focus-visible:opacity-100 group-hover:opacity-100"
                        size="small"
                        @changed="updateTimeEntryBillable"></BillableToggleButton>
                    <div class="flex-1">
                        <button
                            :class="
                                twMerge(
                                    'text-text-secondary px-1 py-1.5 bg-transparent text-center hover:bg-card-background rounded-lg border border-transparent hover:border-card-border text-sm font-medium focus-visible:outline-none focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:bg-tertiary',
                                    organization?.time_format === '12-hours'
                                        ? 'w-[170px]'
                                        : 'w-[120px]'
                                )
                            "
                            @click="expanded = !expanded">
                            {{
                                formatStartEnd(
                                    timeEntry.start,
                                    timeEntry.end,
                                    organization?.time_format
                                )
                            }}
                        </button>
                    </div>
                    <button
                        class="text-text-primary min-w-[90px] px-2.5 py-1.5 bg-transparent text-right hover:bg-card-background rounded-lg border border-transparent hover:border-card-border text-sm font-medium focus-visible:outline-none focus:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:bg-tertiary"
                        @click="expanded = !expanded">
                        {{
                            formatHumanReadableDuration(
                                timeEntry.duration ?? 0,
                                organization?.interval_format,
                                organization?.number_format
                            )
                        }}
                    </button>

                    <TimeTrackerStartStop
                        :active="!!(timeEntry.start && !timeEntry.end)"
                        class="opacity-20 flex group-hover:opacity-100 focus-visible:opacity-100"
                        @changed="onStartStopClick(timeEntry)"></TimeTrackerStartStop>
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
                v-for="subEntry in timeEntry.timeEntries"
                :key="subEntry.id"
                :projects="projects"
                :enable-estimated-time
                :can-create-project
                :tasks="tasks"
                :selected="
                    !!selectedTimeEntries.find(
                        (filterEntry: TimeEntry) => filterEntry.id === subEntry.id
                    )
                "
                :create-client
                :clients
                :create-project
                :tags="tags"
                indent
                :update-time-entry="(timeEntry: TimeEntry) => updateTimeEntry(timeEntry)"
                :on-start-stop-click="() => onStartStopClick(subEntry)"
                :delete-time-entry="() => deleteTimeEntries([subEntry])"
                :currency="currency"
                :create-tag
                :time-entry="subEntry"
                @selected="emit('selected', [subEntry])"
                @unselected="emit('unselected', [subEntry])"></TimeEntryRow>
        </div>
    </div>
</template>

<style scoped></style>
