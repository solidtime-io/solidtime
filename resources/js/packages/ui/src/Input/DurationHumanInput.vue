<script setup lang="ts">
import parse from 'parse-duration';
import { computed, ref } from 'vue';
import {
    formatHumanReadableDuration,
    getDayJsInstance,
} from '@/packages/ui/src/utils/time';
import dayjs from 'dayjs';
import { twMerge } from 'tailwind-merge';
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
    temporaryCustomTimerEntry.value = '';
}

function isNumeric(value: string) {
    return /^-?\d+$/.test(value);
}

const props = defineProps<{
    class?: string;
}>();

const HHMMtimeRegex = /^([0-9]{1,2}):([0-5]?[0-9])$/;

const currentTime = computed({
    get() {
        if (temporaryCustomTimerEntry.value !== '') {
            return temporaryCustomTimerEntry.value;
        }
        if (start.value && end.value) {
            const startTime = dayjs(start.value);
            const diff = getDayJsInstance()(end.value).diff(
                startTime,
                'seconds'
            );
            return formatHumanReadableDuration(diff);
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
</script>

<template>
    <input
        placeholder="00:00:00"
        ref="inputField"
        @blur="updateDuration"
        @keydown.enter="updateDuration"
        v-model="currentTime"
        :class="twMerge('text-text-secondary', props.class)"
        type="text" />
</template>

<style scoped></style>
