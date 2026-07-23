<script setup lang="ts">
import TimeTrackerStartStop from '@/packages/ui/src/TimeTrackerStartStop.vue';
import TimeTrackerRangeSelector from '@/packages/ui/src/TimeTracker/TimeTrackerRangeSelector.vue';
import TimeTrackerEntryInput from '@/packages/ui/src/TimeTracker/TimeTrackerEntryInput.vue';
import TimeTrackerProjectControls from '@/packages/ui/src/TimeTracker/TimeTrackerProjectControls.vue';
import type {
    CreateClientBody,
    CreateProjectBody,
    Project,
    Tag,
    Task,
    TimeEntry,
    Client,
} from '@/packages/api/src';
import { nextTick, ref, watch } from 'vue';
import type { Dayjs } from 'dayjs';
import { Coffee, Play } from '@lucide/vue';
import type { TimeTrackerMode } from '@/packages/ui/src/TimeTracker/types';

const currentTimeEntry = defineModel<TimeEntry>('currentTimeEntry', {
    required: true,
});
const liveTimer = defineModel<Dayjs | null>('liveTimer', { required: true });

const props = withDefaults(
    defineProps<{
        projects: Project[];
        tasks: Task[];
        tags: Tag[];
        clients: Client[];
        timeEntries: TimeEntry[];
        createTag: (name: string) => Promise<Tag | undefined>;
        createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
        createClient: (client: CreateClientBody) => Promise<Client | undefined>;
        isActive: boolean;
        currency: string;
        organizationBillableRate: number | null;
        enableEstimatedTime: boolean;
        canCreateProject: boolean;
        isOnBreak?: boolean;
        breaksEnabled?: boolean;
        canResumeAfterBreak?: boolean;
        resumeDescription?: string | null;
        timeTrackerMode?: TimeTrackerMode;
    }>(),
    {
        isOnBreak: false,
        breaksEnabled: false,
        canResumeAfterBreak: false,
        resumeDescription: null,
        timeTrackerMode: 'project',
    }
);

const emit = defineEmits<{
    startTimer: [];
    stopTimer: [];
    updateTimeEntry: [];
    startLiveTimer: [];
    stopLiveTimer: [];
    createTimeEntry: [];
    startBreak: [];
    resumeAfterBreak: [];
}>();

const entryInput = ref<InstanceType<typeof TimeTrackerEntryInput> | null>(null);

function onToggleButtonPress(newState: boolean) {
    if (newState) {
        emit('startTimer');
        entryInput.value?.focusAfterStart();
    } else {
        emit('stopTimer');
    }
}

// Pressing Enter in the range selector starts the timer, same as in the description input.
function onRangeEnter() {
    entryInput.value?.submit();
}

// After a break ends the tracker returns to the idle input; focus it so a fresh
// entry is just type + Enter.
watch(
    () => props.isOnBreak,
    async (isOnBreak, wasOnBreak) => {
        if (wasOnBreak && !isOnBreak) {
            await nextTick();
            entryInput.value?.focusAfterStart();
        }
    }
);
</script>

<template>
    <div class="flex items-center relative @container" data-testid="dashboard_timer">
        <div
            class="flex flex-col @2xl:flex-row w-full justify-between rounded-lg border transition shadow-card"
            :class="
                isOnBreak
                    ? 'bg-amber-500/10 border-amber-500/30'
                    : 'bg-card-background border-card-border'
            ">
            <div class="flex flex-1 items-center relative">
                <div
                    v-if="isOnBreak"
                    class="flex w-full items-center gap-2 py-4 sm:py-2.5 px-3.5 @2xl:px-4 text-base font-medium text-amber-600 dark:text-amber-400">
                    <Coffee class="w-5 h-5 shrink-0" />
                    <span>On break</span>
                </div>
                <TimeTrackerEntryInput
                    v-else
                    ref="entryInput"
                    v-model:current-time-entry="currentTimeEntry"
                    :time-entries="timeEntries"
                    :projects="projects"
                    :tasks="tasks"
                    :is-active="isActive"
                    @start-timer="emit('startTimer')"
                    @update-time-entry="emit('updateTimeEntry')"></TimeTrackerEntryInput>
                <div class="@2xl:hidden pr-3 shrink-0 flex items-center space-x-2">
                    <button
                        v-if="breaksEnabled && !isOnBreak && isActive"
                        type="button"
                        title="Take a break"
                        aria-label="Take a break"
                        class="flex items-center justify-center w-8 h-8 rounded-full bg-quaternary text-text-tertiary hover:text-amber-500 focus:ring-2 focus:ring-border-tertiary transition"
                        @click="emit('startBreak')">
                        <Coffee class="w-4 h-4" />
                    </button>
                    <TimeTrackerStartStop
                        :active="isActive"
                        :variant="isOnBreak ? 'break' : 'primary'"
                        @changed="onToggleButtonPress"></TimeTrackerStartStop>
                </div>
            </div>
            <div class="flex items-center justify-between pl-2 shrink min-w-0">
                <TimeTrackerProjectControls
                    v-if="!isOnBreak && timeTrackerMode !== 'simple'"
                    v-model:current-time-entry="currentTimeEntry"
                    :projects="projects"
                    :tasks="tasks"
                    :tags="tags"
                    :clients="clients"
                    :create-tag="createTag"
                    :create-project="createProject"
                    :create-client="createClient"
                    :currency="currency"
                    :organization-billable-rate="organizationBillableRate"
                    :enable-estimated-time="enableEstimatedTime"
                    :can-create-project="canCreateProject"
                    @update-time-entry="emit('updateTimeEntry')"></TimeTrackerProjectControls>
                <button
                    v-if="isOnBreak && canResumeAfterBreak"
                    type="button"
                    class="mx-2 flex min-w-0 shrink items-center gap-1.5 h-8 px-3 rounded-md bg-transparent border border-amber-500/40 hover:bg-amber-500/15 text-sm font-medium text-amber-600 dark:text-amber-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 transition"
                    @click="emit('resumeAfterBreak')">
                    <Play class="w-4 h-4 shrink-0" />
                    <span class="truncate">{{
                        resumeDescription ? `Resume "${resumeDescription}"` : 'Resume'
                    }}</span>
                </button>
                <div
                    class="border-l"
                    :class="isOnBreak ? 'border-amber-500/40' : 'border-card-border'">
                    <TimeTrackerRangeSelector
                        v-model:current-time-entry="currentTimeEntry"
                        v-model:live-timer="liveTimer"
                        :is-on-break="isOnBreak"
                        @start-live-timer="emit('startLiveTimer')"
                        @stop-live-timer="emit('stopLiveTimer')"
                        @update-timer="emit('updateTimeEntry')"
                        @start-timer="emit('startTimer')"
                        @create-time-entry="emit('createTimeEntry')"
                        @keydown.enter="onRangeEnter"></TimeTrackerRangeSelector>
                </div>
            </div>
        </div>
        <div class="pl-4 @2xl:pl-6 pr-3 hidden @2xl:flex items-center space-x-3">
            <button
                v-if="breaksEnabled && !isOnBreak && isActive"
                type="button"
                title="Take a break"
                aria-label="Take a break"
                class="flex items-center justify-center w-9 h-9 rounded-full bg-quaternary text-text-tertiary hover:text-amber-500 focus:ring-2 focus:ring-border-tertiary transition"
                @click="emit('startBreak')">
                <Coffee class="w-5 h-5" />
            </button>
            <TimeTrackerStartStop
                :active="isActive"
                :variant="isOnBreak ? 'break' : 'primary'"
                size="large"
                @changed="onToggleButtonPress"></TimeTrackerStartStop>
        </div>
    </div>
</template>

<style scoped></style>
