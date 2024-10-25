<script setup lang="ts">
import TimeTrackerTagDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerTagDropdown.vue';
import TimeTrackerStartStop from '@/packages/ui/src/TimeTrackerStartStop.vue';
import TimeTrackerRangeSelector from '@/packages/ui/src/TimeTracker/TimeTrackerRangeSelector.vue';
import BillableToggleButton from '@/packages/ui/src/Input/BillableToggleButton.vue';
import TimeTrackerProjectTaskDropdown from '@/packages/ui/src/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import type {
    CreateClientBody,
    CreateProjectBody,
    Project,
    Tag,
    Task,
    TimeEntry,
    Client,
} from '@/packages/api/src';
import { ref, watch } from 'vue';
import type { Dayjs } from 'dayjs';

const currentTimeEntry = defineModel<TimeEntry>('currentTimeEntry', {
    required: true,
});
const liveTimer = defineModel<Dayjs | null>('liveTimer', { required: true });

const currentTimeEntryDescriptionInput = ref<HTMLInputElement | null>(null);

const props = defineProps<{
    projects: Project[];
    tasks: Task[];
    tags: Tag[];
    clients: Client[];
    createTag: (name: string) => Promise<Tag | undefined>;
    createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
    isActive: boolean;
    currency: string;
    enableEstimatedTime: boolean;
}>();

const emit = defineEmits<{
    startTimer: [];
    stopTimer: [];
    updateTimeEntry: [];
    startLiveTimer: [];
    stopLiveTimer: [];
}>();

function updateProject() {
    setBillableDefaultForProject();
    emit('updateTimeEntry');
}

function startTimerIfNotActive() {
    if (!props.isActive) {
        currentTimeEntry.value.description = tempDescription.value;
        emit('startTimer');
    }
}

function setBillableDefaultForProject() {
    const project = props.projects.find(
        (project) => project.id === currentTimeEntry.value.project_id
    );
    if (project) {
        currentTimeEntry.value.billable = project.is_billable;
    }
}

function onToggleButtonPress(newState: boolean) {
    if (newState) {
        emit('startTimer');
        currentTimeEntryDescriptionInput.value?.focus();
    } else {
        emit('stopTimer');
    }
}
const tempDescription = ref(currentTimeEntry.value.description);
watch(
    () => currentTimeEntry.value.description,
    () => {
        tempDescription.value = currentTimeEntry.value.description;
    }
);
function updateTimeEntryDescription() {
    if (currentTimeEntry.value.description !== tempDescription.value) {
        currentTimeEntry.value.description = tempDescription.value;
        emit('updateTimeEntry');
    }
}
</script>

<template>
    <div
        class="flex items-center relative @container"
        data-testid="dashboard_timer">
        <div
            class="flex flex-col @2xl:flex-row w-full justify-between rounded-lg bg-card-background border-card-border border transition shadow-card">
            <div class="flex flex-1 items-center pr-6">
                <input
                    placeholder="What are you working on?"
                    data-testid="time_entry_description"
                    ref="currentTimeEntryDescriptionInput"
                    v-model="tempDescription"
                    @keydown.enter="startTimerIfNotActive"
                    @blur="updateTimeEntryDescription"
                    class="w-full rounded-l-lg py-4 sm:py-2.5 px-3.5 border-b border-b-card-background-separator @2xl:px-4 text-base @4xl:text-lg text-white font-medium bg-transparent border-none placeholder-muted focus:ring-0 transition"
                    type="text" />
            </div>
            <div class="flex items-center justify-between pl-2 shrink min-w-0">
                <div
                    class="flex items-center w-[130px] @2xl:w-auto shrink min-w-0">
                    <TimeTrackerProjectTaskDropdown
                        :createClient
                        :clients
                        :createProject
                        :currency="currency"
                        :projects="projects"
                        :tasks="tasks"
                        @changed="updateProject"
                        :enableEstimatedTime="enableEstimatedTime"
                        v-model:project="currentTimeEntry.project_id"
                        v-model:task="
                            currentTimeEntry.task_id
                        "></TimeTrackerProjectTaskDropdown>
                </div>
                <div class="flex items-center @2xl:space-x-2 px-2 @2xl:px-4">
                    <TimeTrackerTagDropdown
                        @changed="$emit('updateTimeEntry')"
                        :createTag
                        :tags="tags"
                        v-model="
                            currentTimeEntry.tags
                        "></TimeTrackerTagDropdown>
                    <BillableToggleButton
                        @changed="$emit('updateTimeEntry')"
                        v-model="
                            currentTimeEntry.billable
                        "></BillableToggleButton>
                </div>
                <div class="border-l border-card-border">
                    <TimeTrackerRangeSelector
                        @startLiveTimer="emit('startLiveTimer')"
                        @stopLiveTimer="emit('stopLiveTimer')"
                        @updateTimer="emit('updateTimeEntry')"
                        @startTimer="emit('startTimer')"
                        v-model:currentTimeEntry="currentTimeEntry"
                        v-model:liveTimer="liveTimer"
                        @keydown.enter="
                            startTimerIfNotActive
                        "></TimeTrackerRangeSelector>
                </div>
            </div>
        </div>
        <div
            class="pl-4 @2xl:pl-6 pr-3 absolute sm:relative top-[6px] sm:top-0 right-0">
            <TimeTrackerStartStop
                :active="isActive"
                @changed="onToggleButtonPress"
                size="large"></TimeTrackerStartStop>
        </div>
    </div>
</template>

<style scoped></style>
