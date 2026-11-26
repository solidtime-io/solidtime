<script setup lang="ts">
import { computed } from 'vue';
import { ChevronDownIcon } from '@heroicons/vue/20/solid';
import { getUserTimezone } from '@/packages/ui/src/utils/settings';
import { timezoneOffset } from './timezoneOffset';

const timezone = getUserTimezone();

// Toggl lists timezones as "(UTC+01:00) Europe/Vienna"
const label = computed(() => `(UTC${timezoneOffset(timezone)}) ${timezone}`);
</script>

<template>
    <div
        role="img"
        :aria-label="`Account timezone in the Toggl account settings set to ${label}`"
        class="w-full max-w-md rounded-lg border border-card-border bg-card-background px-4 py-3 select-none">
        <div class="text-xs font-semibold text-text-primary">Time Preferences</div>
        <div class="my-2.5 border-t border-card-border"></div>
        <div class="text-[10px] font-medium uppercase tracking-wide text-text-tertiary">
            Account timezone
        </div>
        <div
            class="mt-1 flex w-64 max-w-full items-center justify-between gap-2 rounded-md border border-input-border bg-input-background px-3 py-1.5 text-xs text-text-primary">
            <span class="truncate">{{ label }}</span>
            <ChevronDownIcon class="h-3.5 w-3.5 flex-none text-text-secondary" />
        </div>
    </div>
</template>
