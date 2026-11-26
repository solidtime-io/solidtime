<script setup lang="ts">
// "Export projects" dialog of Harvest with the active projects and CSV selected
const selection = ['Active', 'Budgeted', 'Archived'];
const formats = ['CSV', 'Excel'];

const optionClass = (selected: boolean) => [
    'flex h-6 flex-1 items-center justify-center rounded-md border text-[11px]',
    selected
        ? 'border-[#FA5D00] bg-[#FA5D00]/15 text-text-primary'
        : 'border-card-border text-text-primary',
];
</script>

<template>
    <div
        role="img"
        aria-label="Export projects dialog in Harvest with the active projects and the CSV format selected"
        class="w-full max-w-md select-none overflow-hidden rounded-lg border border-card-border bg-card-background">
        <div
            class="border-b border-card-border bg-card-background-active py-1.5 text-center text-xs font-medium text-text-primary">
            Export projects
        </div>
        <div class="space-y-2.5 px-4 py-3">
            <div class="space-y-1">
                <div class="text-[11px] font-medium text-text-primary">
                    Which projects would you like to export?
                </div>
                <div class="flex gap-1.5">
                    <span
                        v-for="item in selection"
                        :key="item"
                        :class="optionClass(item === 'Active')">
                        {{ item }}
                    </span>
                </div>
            </div>
            <div class="space-y-1">
                <div class="text-[11px] font-medium text-text-primary">Choose a format:</div>
                <div class="flex gap-1.5">
                    <span v-for="item in formats" :key="item" :class="optionClass(item === 'CSV')">
                        {{ item }}
                    </span>
                </div>
            </div>
            <div class="flex gap-1.5">
                <span
                    class="flex h-6 items-center whitespace-nowrap rounded-md bg-[#188433] px-2.5 text-[11px] font-medium text-white">
                    Export projects
                </span>
                <span
                    class="flex h-6 items-center whitespace-nowrap rounded-md border border-card-border px-2.5 text-[11px] text-text-primary">
                    Cancel
                </span>
            </div>
        </div>
    </div>
</template>
