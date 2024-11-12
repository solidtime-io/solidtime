<script setup lang="ts">
import parse from 'parse-duration';
import { onMounted, ref, watch } from 'vue';
import {
    formatHumanReadableDuration,
    getDayJsInstance,
} from '@/packages/ui/src/utils/time';
import dayjs from 'dayjs';
import { twMerge } from 'tailwind-merge';
import { TextInput } from '@/packages/ui/src';
const temporaryCustomTimerEntry = ref<string>('');

const start = defineModel('start', {
    default: '',
});

const end = defineModel('end', {
    default: '',
});

function isHHMM(value: string): boolean {
    return HHMMtimeRegex.test(value);
}

function parseHHMM(value: string): string[] | null {
    return value.match(HHMMtimeRegex);
}

function updateDuration() {
    const time = parse(temporaryCustomTimerEntry.value, 's');

    if (isNumeric(temporaryCustomTimerEntry.value)) {
        const newStartDate = getDayJsInstance()(end.value).subtract(
            parseInt(temporaryCustomTimerEntry.value),
            'm'
        );
        start.value = newStartDate.utc().format();
    } else if (isHHMM(temporaryCustomTimerEntry.value)) {
        const results = parseHHMM(temporaryCustomTimerEntry.value);
        if (results) {
            const newStartDate = getDayJsInstance()(end.value)
                .subtract(parseInt(results[1]), 'h')
                .subtract(parseInt(results[2]), 'm');
            start.value = newStartDate.utc().format();
        }
    }
    // try to parse natural language like "1h 30m"
    else if (time && time > 1) {
        const newStartDate = getDayJsInstance()(end.value).subtract(time, 's');
        start.value = newStartDate.utc().format();
    }
    // fallback to minutes if just a number is given
    updateTimeEntryInputValue();
}

function isNumeric(value: string) {
    return /^-?\d+$/.test(value);
}

const props = defineProps<{
    class?: string;
}>();

const HHMMtimeRegex = /^([0-9]{1,2}):([0-5]?[0-9])$/;

watch([start, end], updateTimeEntryInputValue);
onMounted(() => updateTimeEntryInputValue());

function updateTimeEntryInputValue() {
    if (start.value && end.value) {
        const startTime = dayjs(start.value);
        const diff = getDayJsInstance()(end.value).diff(startTime, 'seconds');
        temporaryCustomTimerEntry.value = formatHumanReadableDuration(diff);
    }
}
</script>

<template>
    <TextInput
        ref="inputField"
        @blur="updateDuration"
        @keydown.enter="updateDuration"
        v-model="temporaryCustomTimerEntry"
        :class="twMerge('text-text-secondary', props.class)"
        type="text" />
</template>

<style scoped></style>
