<script setup lang="ts">
import {
    calculateDifference,
    formatHumanReadableDuration,
} from '@/packages/ui/src/utils/time';
import { computed, defineProps, ref } from 'vue';
import parse from 'parse-duration';
import dayjs from 'dayjs';
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import TimeRangeSelector from '@/packages/ui/src/Input/TimeRangeSelector.vue';

const props = defineProps<{
    start: string;
    end: string | null;
}>();
const emit = defineEmits<{
    changed: [start: string, end: string | null];
}>();

const temporaryCustomTimerEntry = ref<string>('');
const open = ref(false);

function updateTimerAndStartLiveTimerUpdate() {
    const time = parse(temporaryCustomTimerEntry.value, 's');
    if (time && time > 0) {
        let newEndDate = props.end;
        let newStartDate = props.start;
        if (props.end) {
            // only update end for time entries that are already finished
            newEndDate = dayjs(props.start).utc().add(time, 's').format();
        } else {
            newStartDate = dayjs().utc().subtract(time, 's').format();
        }
        emit('changed', newStartDate, newEndDate);
    }
    temporaryCustomTimerEntry.value = '';
}

const currentTime = computed({
    get() {
        if (temporaryCustomTimerEntry.value !== '') {
            return temporaryCustomTimerEntry.value;
        }
        return formatHumanReadableDuration(
            calculateDifference(props.start, props.end)
        );
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

function selectInput(event: Event) {
    open.value = true;
    const target = event.target as HTMLInputElement;
    target.select();
}
</script>

<template>
    <Dropdown
        v-model="open"
        @submit="open = false"
        align="bottom"
        :close-on-content-click="false">
        <template #trigger>
            <input
                data-testid="time_entry_duration_input"
                class="text-white w-[100px] px-3 py-2 bg-transparent text-center hover:bg-card-background rounded-lg border border-transparent hover:border-card-border text-sm font-semibold focus-visible:border-transparent focus-visible:ring-2 focus-visible:ring-white/80"
                @focus="selectInput"
                @keydown.tab="open = false"
                @blur="updateTimerAndStartLiveTimerUpdate"
                @keydown.enter="updateTimerAndStartLiveTimerUpdate"
                v-model="currentTime" />
        </template>
        <template #content>
            <TimeRangeSelector
                @changed="
                    (newStart: string, newEnd: string) =>
                        emit('changed', newStart, newEnd)
                "
                :start="start"
                :end="end">
            </TimeRangeSelector>
        </template>
    </Dropdown>
</template>

<style scoped></style>
