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

const start = defineModel<string>('start', {
    default: '',
});

const end = defineModel<string>('end', {
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
        if (props.anchor === 'start') {
            end.value = getDayJsInstance()(start.value).add(seconds, 's').utc().format();
        } else {
            start.value = getDayJsInstance()(end.value).subtract(seconds, 's').utc().format();
        }
    }
    updateTimeEntryInputValue();
}

const props = withDefaults(
    defineProps<{
        class?: string;
        // The endpoint to keep fixed when the duration changes.
        anchor?: 'start' | 'end';
    }>(),
    { anchor: 'end', class: undefined }
);

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
