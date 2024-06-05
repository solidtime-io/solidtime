<script setup lang="ts">
import Dropdown from '@/Components/Dropdown.vue';
import { computed, ref } from 'vue';
import TimeRangeSelector from '@/Components/Common/TimeRangeSelector.vue';
import dayjs from 'dayjs';
import parse from 'parse-duration';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { storeToRefs } from 'pinia';
import { formatDuration, getDayJsInstance } from '@/utils/time';
const currentTimeEntryStore = useCurrentTimeEntryStore();
const { startLiveTimer, stopLiveTimer, updateTimer, startTimer } =
    currentTimeEntryStore;
const { currentTimeEntry, now } = storeToRefs(currentTimeEntryStore);

defineEmits(['changed']);

const open = ref(false);

function pauseLiveTimerUpdate(event: FocusEvent) {
    (event.target as HTMLInputElement).select();
    stopLiveTimer();
}

function onTimeEntryEnterPress() {
    updateTimerAndStartLiveTimerUpdate();
    const activeElement = document.activeElement as HTMLElement;
    activeElement?.blur();
}

const currentTime = computed({
    get() {
        if (temporaryCustomTimerEntry.value !== '') {
            return temporaryCustomTimerEntry.value;
        }
        if (now.value && currentTimeEntry.value.start) {
            const startTime = dayjs(currentTimeEntry.value.start);
            const diff = now.value.diff(startTime, 'seconds');
            return formatDuration(diff);
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
const temporaryCustomTimerEntry = ref<string>('');

async function updateTimeRange(newStart: string) {
    // prohibit updates in the future
    if (getDayJsInstance()(newStart).isBefore(getDayJsInstance()())) {
        currentTimeEntry.value.start = newStart;
        if (currentTimeEntry.value.id) {
            await updateTimer();
        } else {
            await startTimer();
        }
    }
}
</script>

<template>
    <div class="relative">
        <Dropdown
            v-model="open"
            @submit="open = false"
            align="bottom"
            :close-on-content-click="false">
            <template #trigger>
                <input
                    placeholder="00:00:00"
                    @focus="pauseLiveTimerUpdate"
                    data-testid="time_entry_time"
                    @blur="updateTimerAndStartLiveTimerUpdate"
                    @keydown.enter="onTimeEntryEnterPress"
                    v-model="currentTime"
                    class="w-[110px] sm:w-[130px] h-full text-white py-2.5 rounded-r-lg text-center px-4 text-sm sm:text-lg font-bold bg-card-background border-none placeholder-muted focus:ring-0 transition"
                    type="text" />
            </template>
            <template #content>
                <TimeRangeSelector
                    @changed="updateTimeRange"
                    :start="currentTimeEntry.start"
                    :end="null">
                </TimeRangeSelector>
            </template>
        </Dropdown>
    </div>
</template>

<style></style>
