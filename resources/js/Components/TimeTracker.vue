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
</script>

<template>
    <CardTitle title="Time Tracker" :icon="ClockIcon"></CardTitle>
    <div class="flex items-center" data-testid="dashboard_timer">
        <div
            class="flex w-full rounded-lg bg-card-background border-card-border border transition">
            <div class="flex-1 flex items-center pr-6">
                <input
                    placeholder="What are you working on?"
                    data-testid="time_entry_description"
                    v-model="currentTimeEntry.description"
                    @keydown.enter="startTimerIfNotActive"
                    @blur="updateTimeEntry"
                    class="w-full rounded-l-lg py-3 px-5 text-lg text-white focus:bg-card-background-active font-medium bg-transparent border-none placeholder-muted focus:ring-0 transition"
                    type="text" />
            </div>
            <div class="flex items-center">
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
                    v-model="currentTimeEntry.tags"></TimeTrackerTagDropdown>
                <BillableToggleButton></BillableToggleButton>
            </div>
            <div class="border-l border-card-border">
                <input
                    placeholder="00:00:00"
                    @focus="pauseLiveTimerUpdate"
                    data-testid="time_entry_time"
                    @blur="updateTimerAndStartLiveTimerUpdate"
                    @keydown.enter="onTimeEntryEnterPress"
                    v-model="currentTime"
                    class="w-40 h-full text-white py-4 rounded-r-lg text-center px-4 text-lg font-bold bg-card-background border-none placeholder-muted focus:ring-0 transition focus:bg-card-background-active"
                    type="text" />
            </div>
        </div>
        <div class="pl-6 pr-3">
            <TimeTrackerStartStop
                :active="isActive"
                @changed="onToggleButtonPress"
                size="large"></TimeTrackerStartStop>
        </div>
    </div>
</template>
