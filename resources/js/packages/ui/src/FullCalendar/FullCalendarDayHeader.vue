<script setup lang="ts">
import { computed, inject, type ComputedRef } from 'vue';
import { formatHumanReadableDuration } from '../utils/time';
import type { Organization } from '@/packages/api/src';
import type { Dayjs } from 'dayjs';

const props = defineProps<{
    date: Dayjs;
    totalSeconds?: number;
    breakSeconds?: number;
    isToday?: boolean;
}>();

const totalSecondsValue = computed(() => props.totalSeconds ?? 0);
const breakSecondsValue = computed(() => props.breakSeconds ?? 0);

const organization = inject('organization') as ComputedRef<Organization | undefined> | undefined;
const intervalFormat = computed(() => organization?.value?.interval_format);
const numberFormat = computed(() => organization?.value?.number_format);

const hasBreak = computed(() => breakSecondsValue.value > 0);

// Without breaks the work time stands alone, so it needs no label. Once break
// time joins it, both halves are labelled to keep them apart.
const durationSummary = computed(() => {
    const work = formatHumanReadableDuration(
        totalSecondsValue.value,
        intervalFormat.value,
        numberFormat.value
    );
    if (!hasBreak.value) {
        return work;
    }
    const breakTime = formatHumanReadableDuration(
        breakSecondsValue.value,
        intervalFormat.value,
        numberFormat.value
    );
    return `${work} work · ${breakTime} break`;
});
</script>

<template>
    <div class="fc-day-header-custom">
        <div class="text-sm text-foreground" :class="isToday ? 'font-semibold' : 'font-medium'">
            {{ date.format('ddd') }} {{ date.date() }}
        </div>
        <span
            class="block text-xs text-muted-foreground font-medium mt-0.5"
            data-testid="day_duration_summary"
            >{{ durationSummary }}</span
        >
    </div>
</template>
