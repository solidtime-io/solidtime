<script setup lang="ts">
import {
    calculateDifference,
    formatHumanReadableDuration,
} from '@/packages/ui/src/utils/time';
import { computed, defineProps, ref } from 'vue';
import parse from 'parse-duration';
import dayjs from 'dayjs';

const props = defineProps<{
    start: string;
    end: string | null;
}>();
const emit = defineEmits<{
    changed: [start: string, end: string | null];
}>();

const temporaryCustomTimerEntry = ref<string>('');

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
    const target = event.target as HTMLInputElement;
    target.select();
}
</script>

<template>
    <input
        data-testid="time_entry_duration_input"
        class="text-white w-[100px] px-3 py-2 bg-transparent text-center hover:bg-card-background rounded-lg border border-transparent hover:border-card-border text-sm font-semibold"
        @focus="selectInput"
        @blur="updateTimerAndStartLiveTimerUpdate"
        @keydown.enter="updateTimerAndStartLiveTimerUpdate"
        v-model="currentTime" />
</template>

<style scoped></style>
