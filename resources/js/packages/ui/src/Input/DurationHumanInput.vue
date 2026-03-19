<script setup lang="ts">
import { onMounted, ref, watch, inject } from 'vue';
import {
    formatHumanReadableDuration,
    getDayJsInstance,
    parseTimeInput,
} from '@/packages/ui/src/utils/time';
import dayjs from 'dayjs';
import { twMerge } from 'tailwind-merge';
import { TextInput } from '@/packages/ui/src';
import type { Organization } from '@/packages/api/src';
import { type ComputedRef } from 'vue';

const temporaryCustomTimerEntry = ref<string>('');

const start = defineModel('start', {
    default: '',
});

const end = defineModel('end', {
    default: '',
});

const organization = inject<ComputedRef<Organization>>('organization');

function updateDuration() {
    const seconds = parseTimeInput(
        temporaryCustomTimerEntry.value,
        organization?.value?.number_format,
        organization?.value?.interval_format === 'decimal' ? 'hours' : 'minutes'
    );
    if (seconds && seconds > 0) {
        const newStartDate = getDayJsInstance()(end.value).subtract(seconds, 's');
        start.value = newStartDate.utc().format();
    }
    updateTimeEntryInputValue();
}

const props = defineProps<{
    class?: string;
}>();

watch([start, end], updateTimeEntryInputValue);
onMounted(() => updateTimeEntryInputValue());

function updateTimeEntryInputValue() {
    if (start.value && end.value) {
        const startTime = dayjs(start.value);
        const diff = getDayJsInstance()(end.value).diff(startTime, 'seconds');
        temporaryCustomTimerEntry.value = formatHumanReadableDuration(
            diff,
            organization?.value?.interval_format,
            organization?.value?.number_format
        );
    }
}
</script>

<template>
    <TextInput
        ref="inputField"
        v-model="temporaryCustomTimerEntry"
        :class="twMerge('text-text-secondary', props.class)"
        type="text"
        @blur="updateDuration"
        @keydown.enter="updateDuration" />
</template>

<style scoped></style>
