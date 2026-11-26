<script setup lang="ts">
import { computed } from 'vue';
import { getUserTimezone } from '@/packages/ui/src/utils/settings';
import ClockifySelect from './ClockifySelect.vue';
import { timezoneOffset } from './timezoneOffset';

const timezone = getUserTimezone();

// Clockify lists timezones as "(UTC +01:00) Europe/Vienna"
const label = computed(() => `(UTC ${timezoneOffset(timezone)}) ${timezone}`);
</script>

<template>
    <div
        role="img"
        :aria-label="`Time zone in the Clockify preferences set to ${label}`"
        class="w-full max-w-md rounded-lg border border-card-border bg-card-background px-4 py-3 select-none">
        <div class="text-xs font-semibold text-text-primary">Time settings</div>
        <div class="mt-1.5 text-[11px] text-text-secondary">Time zone</div>
        <ClockifySelect :value="label" class="mt-1 w-64 max-w-full" />
    </div>
</template>
