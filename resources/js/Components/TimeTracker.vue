<script setup lang="ts">
import { ClockIcon } from '@heroicons/vue/20/solid';
import CardTitle from '@/Components/Common/CardTitle.vue';
import BillableToggleButton from '@/Components/Common/BillableToggleButton.vue';
import TimeTrackerStartStop from '@/Components/Common/TimeTrackerStartStop.vue';
import { usePage } from '@inertiajs/vue3';
import { type User } from '@/types/models';
import { computed, onMounted, ref, watch } from 'vue';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import duration from 'dayjs/plugin/duration';

import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { storeToRefs } from 'pinia';
import TimeTrackerTagDropdown from '@/Components/Common/TimeTracker/TimeTrackerTagDropdown.vue';
import TimeTrackerProjectTaskDropdown from '@/Components/Common/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { switchOrganization } from '@/utils/useOrganization';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TimeTrackerRangeSelector from '@/Components/Common/TimeTracker/TimeTrackerRangeSelector.vue';
import { useProjectsStore } from '@/utils/useProjects';

const page = usePage<{
    auth: {
        user: User;
    };
}>();
dayjs.extend(duration);

dayjs.extend(utc);

const currentTimeEntryStore = useCurrentTimeEntryStore();
const { currentTimeEntry, isActive, now } = storeToRefs(currentTimeEntryStore);
const { startLiveTimer, stopLiveTimer, setActiveState } = currentTimeEntryStore;
const currentTimeEntryDescriptionInput = ref<HTMLInputElement | null>(null);

watch(isActive, () => {
    if (isActive.value) {
        startLiveTimer();
    } else {
        stopLiveTimer();
    }
});

onMounted(async () => {
    if (page.props.auth.user.current_team_id) {
        await currentTimeEntryStore.fetchCurrentTimeEntry();
        now.value = dayjs().utc();
    }
});

function setBillableDefaultForProject() {
    const projectssStore = useProjectsStore();
    const { projects } = storeToRefs(projectssStore);
    const project = projects.value.find(
        (project) => project.id === currentTimeEntry.value.project_id
    );
    if (project) {
        currentTimeEntry.value.billable = project.is_billable;
    }
}

function updateProject() {
    setBillableDefaultForProject();
    updateTimeEntry();
}

function updateTimeEntry() {
    if (currentTimeEntry.value.id) {
        useCurrentTimeEntryStore().updateTimer();
    }
}

function onToggleButtonPress(newState: boolean) {
    setActiveState(newState);
    if (newState) {
        currentTimeEntryDescriptionInput.value?.focus();
    }
}

function startTimerIfNotActive() {
    if (!isActive.value) {
        setActiveState(true);
    }
}

const isRunningInDifferentOrganization = computed(() => {
    return (
        currentTimeEntry.value.organization_id &&
        getCurrentOrganizationId() &&
        currentTimeEntry.value.organization_id !== getCurrentOrganizationId()
    );
});

function switchToTimeEntryOrganization() {
    if (currentTimeEntry.value.organization_id) {
        switchOrganization(currentTimeEntry.value.organization_id);
    }
}
</script>

<template>
    <CardTitle title="Time Tracker" :icon="ClockIcon"></CardTitle>
    <div class="relative">
        <div
            class="absolute w-full h-full backdrop-blur-sm z-10 flex items-center justify-center"
            v-if="isRunningInDifferentOrganization">
            <div
                class="w-full h-[calc(100%+10px)] absolute bg-default-background opacity-75 backdrop-blur-sm"></div>
            <div class="flex space-x-3 items-center w-full z-20 justify-center">
                <span class="text-sm text-white">
                    The Timer is running in a different organization.
                </span>
                <SecondaryButton @click="switchToTimeEntryOrganization"
                    >Switch to organization</SecondaryButton
                >
            </div>
        </div>
        <div class="flex items-center relative" data-testid="dashboard_timer">
            <div
                class="flex flex-col sm:flex-row w-full rounded-lg bg-card-background border-card-border border transition shadow-card">
                <div class="flex-1 flex items-center pr-6">
                    <input
                        placeholder="What are you working on?"
                        data-testid="time_entry_description"
                        ref="currentTimeEntryDescriptionInput"
                        v-model="currentTimeEntry.description"
                        @keydown.enter="startTimerIfNotActive"
                        @blur="updateTimeEntry"
                        class="w-full rounded-l-lg py-4 sm:py-2.5 px-3 border-b border-b-card-background-separator sm:px-4 text-base sm:text-lg text-white font-medium bg-transparent border-none placeholder-muted focus:ring-0 transition"
                        type="text" />
                </div>
                <div class="flex items-center justify-between pl-2">
                    <div class="flex items-center w-[130px] sm:w-auto">
                        <TimeTrackerProjectTaskDropdown
                            @changed="updateProject"
                            v-model:project="currentTimeEntry.project_id"
                            v-model:task="
                                currentTimeEntry.task_id
                            "></TimeTrackerProjectTaskDropdown>
                    </div>
                    <div class="flex items-center space-x-2 px-4">
                        <TimeTrackerTagDropdown
                            @changed="updateTimeEntry"
                            v-model="
                                currentTimeEntry.tags
                            "></TimeTrackerTagDropdown>
                        <BillableToggleButton
                            @changed="updateTimeEntry"
                            v-model="
                                currentTimeEntry.billable
                            "></BillableToggleButton>
                    </div>
                    <div class="border-l border-card-border">
                        <TimeTrackerRangeSelector></TimeTrackerRangeSelector>
                    </div>
                </div>
            </div>
            <div
                class="pl-6 pr-3 absolute sm:relative top-[6px] sm:top-0 right-0">
                <TimeTrackerStartStop
                    :active="isActive"
                    @changed="onToggleButtonPress"
                    size="large"></TimeTrackerStartStop>
            </div>
        </div>
    </div>
</template>
