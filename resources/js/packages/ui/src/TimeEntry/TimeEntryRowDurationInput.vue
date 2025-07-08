<script setup lang="ts">
import {
    calculateDifference,
    formatHumanReadableDuration,
    parseTimeInput,
} from '@/packages/ui/src/utils/time';
import { computed, defineProps, ref, inject, type ComputedRef } from 'vue';
import dayjs from 'dayjs';
import type { Organization } from '@/packages/api/src';

const organization = inject<ComputedRef<Organization>>('organization');

const organizationSettings = computed(() => ({
    intervalFormat: organization?.value?.interval_format ?? 'hours-minutes',
    numberFormat: organization?.value?.number_format ?? 'point',
}));

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
    const defaultUnit =
        organizationSettings?.value?.intervalFormat === 'decimal'
            ? 'hours'
            : 'minutes';
    const { seconds } = parseTimeInput(
        temporaryCustomTimerEntry.value,
        defaultUnit
    );
    if (seconds && seconds > 0) {
        let newEndDate = props.end;
        let newStartDate = props.start;
        if (props.end) {
            // only update end for time entries that are already finished
            newEndDate = dayjs(props.start).utc().add(seconds, 's').format();
        } else {
            newStartDate = dayjs().utc().subtract(seconds, 's').format();
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
            calculateDifference(props.start, props.end),
            organizationSettings.value.intervalFormat,
            organizationSettings.value.numberFormat
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
    <input
        v-model="currentTime"
        data-testid="time_entry_duration_input"
        name="Duration"
        class="text-text-primary w-[90px] px-2.5 py-1.5 bg-transparent text-right hover:bg-card-background rounded-lg border border-transparent hover:border-card-border text-sm font-medium focus-visible:bg-tertiary focus-visible:border-transparent focus-visible:ring-2 focus-visible:ring-ring"
        @focus="selectInput"
        @keydown.tab="open = false"
        @blur="updateTimerAndStartLiveTimerUpdate"
        @keydown.enter="updateTimerAndStartLiveTimerUpdate" />
</template>

<style scoped></style>
