<script setup lang="ts">
const year = new Date().getFullYear();
</script>

<template>
    <div
        role="img"
        :aria-label="`Time entries in the Toggl data export with the year ${year} selected`"
        class="w-full max-w-md rounded-lg border border-card-border bg-card-background px-4 py-3 select-none">
        <div class="text-xs font-semibold text-text-primary">Time entries</div>
        <div class="mt-2.5 flex items-center gap-2">
            <div
                class="flex h-7 w-32 min-w-0 shrink items-center rounded-md border border-input-border bg-input-background px-2.5 text-xs text-text-primary">
                {{ year }}
            </div>
            <div
                class="flex h-7 flex-none items-center whitespace-nowrap rounded-md bg-[#A5489B] px-3 text-xs font-medium text-white dark:bg-[#BE7CB7] dark:text-[#1B1B1B]">
                Export time entries
            </div>
        </div>
    </div>
</template>
