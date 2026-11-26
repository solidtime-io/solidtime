<script setup lang="ts">
import { GlobeAltIcon } from '@heroicons/vue/24/outline';
import ClockifySelect from './ClockifySelect.vue';
</script>

<template>
    <div
        role="img"
        aria-label="Clockify preferences with the language English, the date format MM/DD/YYYY and the time format 12-hour"
        class="w-full max-w-md rounded-lg border border-card-border bg-card-background px-4 py-3 select-none">
        <div class="text-xs font-semibold text-text-primary">Language</div>
        <ClockifySelect value="English" class="mt-1.5 w-64 max-w-full">
            <template #icon>
                <GlobeAltIcon class="h-3.5 w-3.5 flex-none text-text-secondary" />
            </template>
        </ClockifySelect>
        <div class="my-2.5 border-t border-card-border"></div>
        <div class="grid grid-cols-2 gap-3">
            <div class="min-w-0">
                <div class="text-[11px] text-text-secondary">Date format</div>
                <ClockifySelect value="MM/DD/YYYY" class="mt-1" />
            </div>
            <div class="min-w-0">
                <div class="text-[11px] text-text-secondary">Time format</div>
                <ClockifySelect value="12-hour" class="mt-1" />
            </div>
        </div>
    </div>
</template>
