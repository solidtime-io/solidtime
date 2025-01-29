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
    canCreateProject: boolean;
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
                    ref="currentTimeEntryDescriptionInput"
                    v-model="tempDescription"
                    placeholder="What are you working on?"
                    data-testid="time_entry_description"
                    class="w-full rounded-l-lg py-4 sm:py-2.5 px-3.5 border-b border-b-card-background-separator @2xl:px-4 text-base @4xl:text-lg text-white font-medium bg-transparent border-none placeholder-muted focus:ring-0 transition"
                    type="text"
                    @keydown.enter="startTimerIfNotActive"
                    @blur="updateTimeEntryDescription" />
            </div>
            <div class="flex items-center justify-between pl-2 shrink min-w-0">
                <div
                    class="flex items-center w-[130px] @2xl:w-auto shrink min-w-0">
                    <TimeTrackerProjectTaskDropdown
                        v-model:project="currentTimeEntry.project_id"
                        v-model:task="
                            currentTimeEntry.task_id
                        "
                        :create-client
                        :can-create-project
                        :clients
                        :create-project
                        :currency="currency"
                        :projects="projects"
                        :tasks="tasks"
                        :enable-estimated-time="enableEstimatedTime"
                        @changed="updateProject"></TimeTrackerProjectTaskDropdown>
                </div>
                <div class="flex items-center @2xl:space-x-2 px-2 @2xl:px-4">
                    <TimeTrackerTagDropdown
                        v-model="
                            currentTimeEntry.tags
                        "
                        :create-tag
                        :tags="tags"
                        @changed="$emit('updateTimeEntry')"></TimeTrackerTagDropdown>
                    <BillableToggleButton
                        v-model="
                            currentTimeEntry.billable
                        "
                        @changed="$emit('updateTimeEntry')"></BillableToggleButton>
                </div>
                <div class="border-l border-card-border">
                    <TimeTrackerRangeSelector
                        v-model:current-time-entry="currentTimeEntry"
                        v-model:live-timer="liveTimer"
                        @start-live-timer="emit('startLiveTimer')"
                        @stop-live-timer="emit('stopLiveTimer')"
                        @update-timer="emit('updateTimeEntry')"
                        @start-timer="emit('startTimer')"
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
                size="large"
                @changed="onToggleButtonPress"></TimeTrackerStartStop>
        </div>
    </div>
</template>

<style scoped></style>
