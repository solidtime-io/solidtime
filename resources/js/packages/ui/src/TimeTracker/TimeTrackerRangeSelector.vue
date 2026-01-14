<script setup lang="ts">
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import { computed, ref } from 'vue';
import TimeRangeSelector from '@/packages/ui/src/Input/TimeRangeSelector.vue';
import dayjs, { Dayjs } from 'dayjs';
import { formatDuration, getDayJsInstance, parseTimeInput } from '@/packages/ui/src/utils/time';
import type { TimeEntry } from '@/packages/api/src';

const currentTimeEntry = defineModel<TimeEntry>('currentTimeEntry', {
    required: true,
});
const now = defineModel<null | Dayjs>('liveTimer');

const emit = defineEmits<{
    startLiveTimer: [];
    stopLiveTimer: [];
    updateTimer: [];
    startTimer: [];
    createTimeEntry: [];
}>();

const open = ref(false);

function pauseLiveTimerUpdate(event: FocusEvent) {
    (event.target as HTMLInputElement).select();
    emit('stopLiveTimer');
}

function onTimeEntryEnterPress() {
    updateTimerAndStartLiveTimerUpdate();
    open.value = false;
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
    const seconds = parseTimeInput(temporaryCustomTimerEntry.value, 'minutes');

    if (seconds && seconds > 0) {
        const newStartDate = dayjs().subtract(seconds, 's');
        currentTimeEntry.value.start = newStartDate.utc().format();
        if (currentTimeEntry.value.id !== '') {
            emit('updateTimer');
        } else {
            emit('startTimer');
        }
    }
    now.value = dayjs().utc();
    temporaryCustomTimerEntry.value = '';
    emit('startLiveTimer');
}

const temporaryCustomTimerEntry = ref<string>('');

async function updateTimeRange(newStart: string, newEnd: string | null) {
    // prohibit updates in the future
    if (getDayJsInstance()(newStart).isBefore(getDayJsInstance()())) {
        currentTimeEntry.value.start = newStart;
        currentTimeEntry.value.end = newEnd;
        if (currentTimeEntry.value.id) {
            emit('updateTimer');
        } else if (newEnd !== null) {
            // If there's no ID but we have both start and end, create a new time entry
            emit('createTimeEntry');
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

const endTime = computed(() => {
    if (currentTimeEntry.value.end && currentTimeEntry.value.end !== '') {
        return currentTimeEntry.value.end;
    }
    return null;
});

const inputField = ref<HTMLInputElement | null>(null);

const timeRangeSelector = ref<HTMLElement | null>(null);

function openModalOnTab(e: FocusEvent) {
    pauseLiveTimerUpdate(e);

    // check if the source is inside the dropdown
    const source = e.relatedTarget as HTMLElement;
    if (source && window.document.body.querySelector<HTMLElement>('#app')?.contains(source)) {
        open.value = true;
    }
}

function openModalOnClick(e: MouseEvent) {
    pauseLiveTimerUpdate(e);

    open.value = true;
}

function focusNextElement(e: KeyboardEvent) {
    if (open.value) {
        e.preventDefault();
        const focusableElement = timeRangeSelector.value?.querySelector<HTMLElement>(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        focusableElement?.focus();
    }
}

function closeAndFocusInput() {
    open.value = false;
    inputField.value?.focus();
}
</script>

<template>
    <div class="relative">
        <Dropdown
            v-model="open"
            align="center"
            :auto-focus="false"
            :close-on-content-click="false"
            @submit="closeAndFocusInput">
            <template #trigger>
                <input
                    ref="inputField"
                    v-model="currentTime"
                    placeholder="00:00:00"
                    data-testid="time_entry_time"
                    class="w-[110px] lg:w-[130px] h-full text-text-primary py-2.5 rounded-lg border-border-secondary border text-center px-4 text-base lg:text-lg font-semibold bg-card-background border-none placeholder-text-tertiary focus:ring-0 transition"
                    type="text"
                    @focusin="openModalOnTab"
                    @click="openModalOnClick"
                    @keydown.exact.tab="focusNextElement"
                    @keydown.exact.shift.tab="open = false"
                    @blur="updateTimerAndStartLiveTimerUpdate"
                    @keydown.enter="onTimeEntryEnterPress" />
            </template>
            <template #content>
                <div ref="timeRangeSelector">
                    <TimeRangeSelector
                        :start="startTime"
                        :end="endTime"
                        @changed="updateTimeRange"
                        @close="closeAndFocusInput">
                    </TimeRangeSelector>
                </div>
            </template>
        </Dropdown>
    </div>
</template>

<style></style>
