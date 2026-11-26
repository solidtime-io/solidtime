<script setup lang="ts">
// Columns of the "Project export settings" in Clockify, in the order of the two columns there
const columns = [
    'Name',
    'Client',
    'Status',
    'Visibility',
    'Tasks',
    'Tracked (h)',
    'Estimated (h)',
    'Remaining (h)',
    'Overage (h)',
    'Progress (%)',
    'Project members',
    'Project manager',
    'Note',
];
</script>

<template>
    <div
        role="img"
        aria-label="Project export settings in Clockify with all columns selected"
        class="w-full max-w-md rounded-lg border border-card-border bg-card-background px-4 py-3 select-none">
        <div class="text-xs font-semibold text-text-primary">Project export settings</div>
        <div class="my-2 border-t border-card-border"></div>
        <div class="flex items-center gap-1.5 text-xs text-text-primary">
            <span
                class="flex h-3.5 w-3.5 flex-none items-center justify-center rounded-[2px] bg-[#03A9F4]">
                <svg viewBox="0 0 16 16" fill="none" class="h-3.5 w-3.5 text-white">
                    <path
                        d="M4.2 8.4 6.8 11l5-6"
                        stroke="currentColor"
                        stroke-width="2.2"
                        stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </span>
            Select all
        </div>
        <div class="mt-1.5 grid grid-flow-col grid-rows-7 gap-x-4 gap-y-1">
            <div
                v-for="column in columns"
                :key="column"
                class="flex items-center gap-1.5 text-xs text-text-primary">
                <span
                    class="flex h-3.5 w-3.5 flex-none items-center justify-center rounded-[2px] bg-[#03A9F4]">
                    <svg viewBox="0 0 16 16" fill="none" class="h-3.5 w-3.5 text-white">
                        <path
                            d="M4.2 8.4 6.8 11l5-6"
                            stroke="currentColor"
                            stroke-width="2.2"
                            stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </span>
                <span class="truncate">{{ column }}</span>
            </div>
        </div>
        <div class="mt-3 flex justify-end">
            <div
                class="flex h-7 items-center rounded-sm bg-[#03A9F4] px-4 text-xs font-medium uppercase text-white">
                Save
            </div>
        </div>
    </div>
</template>
