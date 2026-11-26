<script setup lang="ts">
// Items of the "Data Export" section in the Toggl workspace settings
const items = [
    'Projects',
    'Project members',
    'Project tasks',
    'Clients',
    'Tags',
    'Team',
    'Teams',
    'Workspace Settings',
    'Alerts',
    'Custom Reports',
    'Scheduled Reports',
    'Tracking Reminders',
    'Invoices',
];
</script>

<template>
    <div
        role="img"
        aria-label="Data Export in the Toggl workspace settings with all items selected"
        class="w-full max-w-md rounded-lg border border-card-border bg-card-background px-4 py-3 select-none">
        <div class="text-xs font-semibold text-text-primary">Data Export</div>
        <div class="mt-2 flex items-center gap-1.5 text-xs text-text-secondary">
            <span
                class="flex h-3.5 w-3.5 flex-none items-center justify-center rounded-[3px] bg-[#A5489B] dark:bg-[#BE7CB7]">
                <svg
                    viewBox="0 0 16 16"
                    fill="none"
                    class="h-3.5 w-3.5 text-white dark:text-[#1B1B1B]">
                    <path
                        d="M4.2 8.4 6.8 11l5-6"
                        stroke="currentColor"
                        stroke-width="2.2"
                        stroke-linecap="round"
                        stroke-linejoin="round" />
                </svg>
            </span>
            {{ items.length }} items selected
        </div>
        <div class="mt-1.5 grid grid-cols-2 gap-x-4 gap-y-1">
            <div
                v-for="item in items"
                :key="item"
                class="flex items-center gap-1.5 text-xs text-text-primary">
                <span
                    class="flex h-3.5 w-3.5 flex-none items-center justify-center rounded-[3px] bg-[#A5489B] dark:bg-[#BE7CB7]">
                    <svg
                        viewBox="0 0 16 16"
                        fill="none"
                        class="h-3.5 w-3.5 text-white dark:text-[#1B1B1B]">
                        <path
                            d="M4.2 8.4 6.8 11l5-6"
                            stroke="currentColor"
                            stroke-width="2.2"
                            stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                </span>
                <span class="truncate">{{ item }}</span>
            </div>
        </div>
        <div
            class="mt-3 flex h-7 w-48 max-w-full items-center justify-center rounded-md bg-[#A5489B] text-xs font-medium text-white dark:bg-[#BE7CB7] dark:text-[#1B1B1B]">
            Export to email
        </div>
    </div>
</template>
