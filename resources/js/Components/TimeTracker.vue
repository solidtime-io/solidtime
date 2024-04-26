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
import parse from 'parse-duration';
import TimeTrackerTagDropdown from '@/Components/Common/TimeTracker/TimeTrackerTagDropdown.vue';
import TimeTrackerProjectTaskDropdown from '@/Components/Common/TimeTracker/TimeTrackerProjectTaskDropdown.vue';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { switchOrganization } from '@/utils/useOrganization';
import SecondaryButton from '@/Components/SecondaryButton.vue';

const page = usePage<{
    auth: {
        user: User;
    };
}>();
dayjs.extend(duration);

dayjs.extend(utc);

const currentTimeEntryStore = useCurrentTimeEntryStore();
const { currentTimeEntry, isActive, now } = storeToRefs(currentTimeEntryStore);
const { startLiveTimer, stopLiveTimer, onToggleButtonPress } =
    currentTimeEntryStore;

watch(isActive, () => {
    if (isActive.value) {
        startLiveTimer();
    } else {
        stopLiveTimer();
    }
});

const temporaryCustomTimerEntry = ref<string>('');

const currentTime = computed({
    get() {
        if (temporaryCustomTimerEntry.value !== '') {
            return temporaryCustomTimerEntry.value;
        }
        if (now.value && currentTimeEntry.value.start) {
            const startTime = dayjs(currentTimeEntry.value.start);
            const diff = now.value.diff(startTime);
            return dayjs(diff).utc().format('HH:mm:ss');
        }
        return null;
    },
    // setter
    set(newValue) {
        if (newValue) {
            temporaryCustomTimerEntry.value = newValue;
        } else {
            temporaryCustomTimerEntry.value = '';
        }
    },
});

onMounted(async () => {
    if (page.props.auth.user.current_team_id) {
        await currentTimeEntryStore.fetchCurrentTimeEntry();
        now.value = dayjs().utc();
    }
});

function updateTimeEntry() {
    if (currentTimeEntry.value.id) {
        useCurrentTimeEntryStore().updateTimer();
    }
}

function pauseLiveTimerUpdate() {
    stopLiveTimer();
}

function updateTimerAndStartLiveTimerUpdate() {
    const time = parse(temporaryCustomTimerEntry.value, 's');

    if (isNumeric(temporaryCustomTimerEntry.value)) {
        const newStartDate = dayjs().subtract(
            parseInt(temporaryCustomTimerEntry.value),
            'm'
        );
        currentTimeEntry.value.start = newStartDate.utc().format();
        if (currentTimeEntry.value.id !== '') {
            currentTimeEntryStore.updateTimer();
        } else {
            currentTimeEntryStore.startTimer();
        }
    } else if (isHHMM(temporaryCustomTimerEntry.value)) {
        const results = parseHHMM(temporaryCustomTimerEntry.value);
        if (results) {
            const newStartDate = dayjs()
                .subtract(parseInt(results[1]), 'h')
                .subtract(parseInt(results[2]), 'm');
            currentTimeEntry.value.start = newStartDate.utc().format();
            if (currentTimeEntry.value.id !== '') {
                currentTimeEntryStore.updateTimer();
            } else {
                currentTimeEntryStore.startTimer();
            }
        }
    }
    // try to parse natural language like "1h 30m"
    else if (time && time > 1) {
        const newStartDate = dayjs().subtract(time, 's');
        currentTimeEntry.value.start = newStartDate.utc().format();
        if (currentTimeEntry.value.id !== '') {
            currentTimeEntryStore.updateTimer();
        } else {
            currentTimeEntryStore.startTimer();
        }
    }
    // fallback to minutes if just a number is given
    now.value = dayjs().utc();
    temporaryCustomTimerEntry.value = '';
    startLiveTimer();
}

function isNumeric(value: string) {
    return /^-?\d+$/.test(value);
}

const HHMMtimeRegex = /^([0-9]{1,2}):([0-5]?[0-9])$/;

function isHHMM(value: string): boolean {
    return HHMMtimeRegex.test(value);
}

function parseHHMM(value: string): string[] | null {
    return value.match(HHMMtimeRegex);
}

function startTimerIfNotActive() {
    if (!isActive.value) {
        onToggleButtonPress(true);
    }
}

function onTimeEntryEnterPress() {
    updateTimerAndStartLiveTimerUpdate();
    const activeElement = document.activeElement as HTMLElement;
    activeElement?.blur();
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
                class="flex flex-col sm:flex-row w-full rounded-lg bg-card-background border-card-border border transition">
                <div class="flex-1 flex items-center pr-6">
                    <input
                        placeholder="What are you working on?"
                        data-testid="time_entry_description"
                        v-model="currentTimeEntry.description"
                        @keydown.enter="startTimerIfNotActive"
                        @blur="updateTimeEntry"
                        class="w-full rounded-l-lg py-2.5 px-3 border-b border-b-card-background-separator sm:px-4 text-sm sm:text-lg text-white focus:bg-card-background-active font-medium bg-transparent border-none placeholder-muted focus:ring-0 transition"
                        type="text" />
                </div>
                <div class="flex items-center justify-between pl-2">
                    <div class="flex items-center w-[130px] sm:w-auto">
                        <TimeTrackerProjectTaskDropdown
                            @changed="updateTimeEntry"
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
                        <input
                            placeholder="00:00:00"
                            @focus="pauseLiveTimerUpdate"
                            data-testid="time_entry_time"
                            @blur="updateTimerAndStartLiveTimerUpdate"
                            @keydown.enter="onTimeEntryEnterPress"
                            v-model="currentTime"
                            class="w-[110px] sm:w-[130px] h-full text-white py-2.5 rounded-r-lg text-center px-4 text-sm sm:text-lg font-bold bg-card-background border-none placeholder-muted focus:ring-0 transition focus:bg-card-background-active"
                            type="text" />
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
