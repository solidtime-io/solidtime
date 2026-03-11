<script setup lang="ts">
import { computed, inject, type ComputedRef } from 'vue';
import { formatHumanReadableDuration } from '../utils/time';
import type { Organization } from '@/packages/api/src';
import type { Dayjs } from 'dayjs';

const props = defineProps<{
    date: Dayjs;
    totalSeconds?: number;
    isToday?: boolean;
}>();

const totalSecondsValue = computed(() => props.totalSeconds ?? 0);

const organization = inject('organization') as ComputedRef<Organization | undefined> | undefined;
const intervalFormat = computed(() => organization?.value?.interval_format);
const numberFormat = computed(() => organization?.value?.number_format);
</script>

<template>
    <div class="fc-day-header-custom">
        <div class="text-sm text-foreground" :class="isToday ? 'font-semibold' : 'font-medium'">
            {{ date.format('ddd') }} {{ date.date() }}
        </div>
        <span class="block text-xs text-muted-foreground font-medium mt-0.5">
            {{ formatHumanReadableDuration(totalSecondsValue, intervalFormat, numberFormat) }}
        </span>
    </div>
</template>
