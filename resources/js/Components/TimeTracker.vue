<script setup lang="ts">
import { ClockIcon } from '@heroicons/vue/20/solid';
import CardTitle from '@/Components/common/CardTitle.vue';
import BillableToggleButton from '@/Components/common/BillableToggleButton.vue';
import TimeTrackerStartStop from '@/Components/common/TimeTrackerStartStop.vue';
import TagDropdown from '@/Components/common/TagDropdown.vue';
import ProjectDropdown from '@/Components/common/ProjectDropdown.vue';
import { usePage } from '@inertiajs/vue3';
import { type User } from '@/types/models';
import { computed, onMounted, ref, watch } from 'vue';
import dayjs from 'dayjs';
import utc from 'dayjs/plugin/utc';
import duration from 'dayjs/plugin/duration';

import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { storeToRefs } from 'pinia';
import type { Project } from '@/utils/useProjects';
import parse from 'parse-duration';

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

const currentProject = ref<Project>();
watch(currentProject, () => {
    if (currentProject.value) {
        currentTimeEntry.value.project_id = currentProject.value.id;
        if (isActive.value) {
            useCurrentTimeEntryStore().updateTimer();
        }
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
    if (time && time > 0) {
        const newStartDate = dayjs().subtract(time, 's');
        currentTimeEntry.value.start = newStartDate.utc().format();
        if (currentTimeEntry.value.id !== '') {
            currentTimeEntryStore.updateTimer();
        } else {
            currentTimeEntryStore.startTimer();
        }
    }
    now.value = dayjs().utc();
    temporaryCustomTimerEntry.value = '';
    startLiveTimer();
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
                    @blur="updateTimeEntry"
                    class="w-full rounded-l-lg py-3 px-5 text-lg text-white focus:bg-card-background-active font-medium bg-transparent border-none placeholder-muted focus:ring-0 transition"
                    type="text" />
            </div>
            <div class="flex items-center">
                <ProjectDropdown v-model="currentProject"></ProjectDropdown>
            </div>
            <div class="flex items-center space-x-2 px-4">
                <TagDropdown
                    @changed="updateTimeEntry"
                    v-model="currentTimeEntry.tags"></TagDropdown>
                <BillableToggleButton></BillableToggleButton>
            </div>
            <div class="border-l border-card-border">
                <input
                    placeholder="00:00:00"
                    @focus="pauseLiveTimerUpdate"
                    data-testid="time_entry_time"
                    @blur="updateTimerAndStartLiveTimerUpdate"
                    @keydown.enter="updateTimerAndStartLiveTimerUpdate"
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
