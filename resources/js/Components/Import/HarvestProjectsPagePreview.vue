<script setup lang="ts">
// Projects page in Harvest with the Export button at the top right
const projects = [
    { client: 'Acme Inc.', name: 'Website' },
    { client: 'Example Client', name: 'Mobile App' },
];
</script>

<template>
    <div
        role="img"
        aria-label="Projects page in Harvest with the Export button at the top right"
        class="w-full max-w-md select-none rounded-lg border border-card-border bg-card-background">
        <div class="flex items-center gap-1.5 border-b border-card-border px-3 py-2.5">
            <span class="flex-1 text-sm font-medium text-text-primary">Projects</span>
            <span
                class="flex h-6 items-center whitespace-nowrap rounded-md bg-[#188433] px-2 text-[11px] font-medium text-white">
                + New project
            </span>
            <span
                class="flex h-6 items-center whitespace-nowrap rounded-md border border-card-border px-2 text-[11px] text-text-primary">
                Import
            </span>
            <span
                class="flex h-6 items-center whitespace-nowrap rounded-md border border-text-secondary px-2 text-[11px] font-medium text-text-primary">
                Export
            </span>
        </div>
        <div class="px-3 py-3">
            <div class="overflow-hidden rounded-md border border-card-border">
                <div
                    class="flex items-center gap-2 border-b border-card-border bg-card-background-active px-2 py-1 text-[10px] text-text-secondary">
                    <span class="h-2.5 w-2.5 rounded-[2px] border border-text-quaternary"></span>
                    <span class="flex-1">Client</span>
                    <span class="w-12 text-right">Spent</span>
                </div>
                <template v-for="project in projects" :key="project.name">
                    <div
                        class="border-b border-card-border bg-card-background-active px-2 py-0.5 pl-6 text-[10px] text-text-secondary underline">
                        {{ project.client }}
                    </div>
                    <div
                        class="flex items-center gap-2 border-b border-card-border px-2 py-1.5 last:border-b-0">
                        <span
                            class="h-2.5 w-2.5 rounded-[2px] border border-text-quaternary"></span>
                        <span class="flex-1 text-xs font-medium text-text-primary">
                            {{ project.name }}
                        </span>
                        <span class="w-12 text-right text-xs text-text-primary">0,00</span>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>
