<script setup lang="ts">
import { computed, inject, type ComputedRef } from 'vue';
import { formatDate, formatHumanReadableDuration } from '../utils/time';
import type { Organization } from '@/packages/api/src';
import type { Dayjs } from 'dayjs';

const props = defineProps<{
    date: Dayjs;
    totalMinutes?: number;
}>();

const totalSeconds = computed(() => (props.totalMinutes ?? 0) * 60);

// Injected organization for formatting settings
const organization = inject('organization') as ComputedRef<Organization | undefined> | undefined;
const intervalFormat = computed(() => organization?.value?.interval_format);
const numberFormat = computed(() => organization?.value?.number_format);
const dateFormat = computed(() => organization?.value?.date_format);
</script>

<template>
    <div class="fc-day-header-custom">
        <div class="text-xs text-muted-foreground font-medium">
            {{ date.format('ddd') }}
        </div>
        <span>{{ formatDate(date.toISOString(), dateFormat) }}</span>
        <span class="block text-xs text-muted-foreground font-medium mt-1">
            {{ formatHumanReadableDuration(totalSeconds, intervalFormat, numberFormat) }}
        </span>
    </div>
</template>
