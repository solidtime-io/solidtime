<script setup lang="ts">
import Dropdown from '@/Components/Dropdown.vue';
import { computed, ref } from 'vue';
import TimeRangeSelector from '@/Components/Common/TimeRangeSelector.vue';
import dayjs, { Dayjs } from 'dayjs';
import parse from 'parse-duration';
import { formatDuration, getDayJsInstance } from '@/utils/time';
import type { TimeEntry } from '@/utils/api';

const currentTimeEntry = defineModel<TimeEntry>('currentTimeEntry', {
    required: true,
});
const now = defineModel<null | Dayjs>('liveTimer');

const emit = defineEmits<{
    startLiveTimer: [];
    stopLiveTimer: [];
    updateTimer: [];
    startTimer: [];
}>();

const open = ref(false);

function pauseLiveTimerUpdate(event: FocusEvent) {
    (event.target as HTMLInputElement).select();
    emit('stopLiveTimer');
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
            emit('updateTimer');
        } else {
            emit('startTimer');
        }
    } else if (isHHMM(temporaryCustomTimerEntry.value)) {
        const results = parseHHMM(temporaryCustomTimerEntry.value);
        if (results) {
            const newStartDate = dayjs()
                .subtract(parseInt(results[1]), 'h')
                .subtract(parseInt(results[2]), 'm');
            currentTimeEntry.value.start = newStartDate.utc().format();
            if (currentTimeEntry.value.id !== '') {
                emit('updateTimer');
            } else {
                emit('startTimer');
            }
        }
    }
    // try to parse natural language like "1h 30m"
    else if (time && time > 1) {
        const newStartDate = dayjs().subtract(time, 's');
        currentTimeEntry.value.start = newStartDate.utc().format();
        if (currentTimeEntry.value.id !== '') {
            emit('updateTimer');
        } else {
            emit('startTimer');
        }
    }
    // fallback to minutes if just a number is given
    now.value = dayjs().utc();
    temporaryCustomTimerEntry.value = '';
    emit('startLiveTimer');
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
            emit('updateTimer');
        } else {
            emit('startTimer');
        }
    }
}

const startTime = computed(() => {
    if (currentTimeEntry.value.start && currentTimeEntry.value.start !== '') {
        return currentTimeEntry.value.start;
    }
    return dayjs().utc().format();
});
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
                    class="w-[110px] lg:w-[130px] h-full text-white py-2.5 rounded-r-lg text-center px-4 text-base lg:text-lg font-bold bg-card-background border-none placeholder-muted focus:ring-0 transition"
                    type="text" />
            </template>
            <template #content>
                <TimeRangeSelector
                    @changed="updateTimeRange"
                    :start="startTime"
                    :end="null">
                </TimeRangeSelector>
            </template>
        </Dropdown>
    </div>
</template>

<style></style>
