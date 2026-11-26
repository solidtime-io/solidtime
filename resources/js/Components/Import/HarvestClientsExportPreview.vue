<script setup lang="ts">
// Clients page in Harvest with the Import/Export menu open
const menuGroups = [
    ['Import clients from CSV', 'Import contacts from CSV'],
    ['Export clients', 'Export contacts'],
];
</script>

<template>
    <div
        role="img"
        aria-label="Clients page in Harvest with the Import/Export menu at the top right opened"
        class="relative w-full max-w-md select-none rounded-lg border border-card-border bg-card-background">
        <div class="flex items-center gap-1.5 border-b border-card-border px-3 py-2.5">
            <span class="flex-1 text-sm font-medium text-text-primary">Clients</span>
            <span
                class="flex h-6 items-center whitespace-nowrap rounded-md bg-[#188433] px-2 text-[11px] font-medium text-white">
                + New client
            </span>
            <span
                class="flex h-6 items-center whitespace-nowrap gap-1 rounded-md border border-card-border px-2 text-[11px] text-text-primary">
                Import/Export
                <svg viewBox="0 0 10 6" class="h-1.5 w-2.5 stroke-text-secondary" fill="none">
                    <path d="M1 1l4 4 4-4" stroke-width="1.5" />
                </svg>
            </span>
        </div>
        <div class="space-y-1.5 px-3 py-3">
            <div
                v-for="client in ['Acme Inc.', 'Example Client', 'Globex']"
                :key="client"
                class="flex items-center gap-2 rounded-md border border-card-border px-2 py-1.5">
                <span
                    class="rounded border border-card-border px-1.5 text-[10px] text-text-secondary"
                    >Edit</span
                >
                <span class="text-xs font-medium text-text-primary">{{ client }}</span>
            </div>
        </div>
        <div
            class="absolute right-2 top-10 w-44 rounded-md border border-card-border bg-card-background py-1 shadow-lg">
            <template v-for="(group, index) in menuGroups" :key="index">
                <div v-if="index > 0" class="my-1 border-t border-card-border"></div>
                <div
                    v-for="item in group"
                    :key="item"
                    :class="[
                        'whitespace-nowrap px-3 py-1 text-[11px]',
                        item === 'Export clients'
                            ? 'bg-text-primary text-card-background'
                            : 'text-text-primary',
                    ]">
                    {{ item }}
                </div>
            </template>
        </div>
    </div>
</template>
