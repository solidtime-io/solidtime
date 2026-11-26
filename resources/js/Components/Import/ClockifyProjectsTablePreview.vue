<script setup lang="ts">
// Projects table in Clockify with the Export menu open
const columns = ['Name', 'Client', 'Tracked'];
const menuItems = ['Save as CSV', 'Save as Excel', 'Customize...'];
</script>

<template>
    <div
        role="img"
        aria-label="Projects table in Clockify with the Export menu at its top right opened"
        class="relative w-full max-w-md select-none">
        <div class="overflow-hidden border border-card-border bg-card-background">
            <div
                class="flex h-8 items-center justify-between border-b border-card-border bg-card-background-active">
                <span class="px-3 text-[11px] text-text-secondary">Projects</span>
                <span
                    class="flex h-full items-center gap-1 border-l border-card-border px-3 text-[11px] text-text-primary">
                    Export
                    <svg viewBox="0 0 8 5" class="h-1 w-1.5 fill-text-secondary">
                        <path d="M0 0h8L4 5z" />
                    </svg>
                </span>
            </div>
            <div
                class="flex h-7 items-center gap-3 border-b border-card-border px-3 text-[10px] uppercase tracking-wider text-text-secondary">
                <span class="h-3 w-3 flex-none rounded-[2px] border border-input-border"></span>
                <span
                    v-for="column in columns"
                    :key="column"
                    class="flex flex-1 items-center gap-1">
                    {{ column }}
                    <svg viewBox="0 0 8 11" class="h-2 w-1.5 fill-text-tertiary">
                        <path d="M0 4.5h8L4 0zM0 6.5h8L4 11z" />
                    </svg>
                </span>
            </div>
            <div class="flex h-8 items-center gap-3 px-3 text-xs text-text-primary">
                <span class="h-3 w-3 flex-none rounded-[2px] border border-input-border"></span>
                <span class="flex flex-1 items-center gap-1.5">
                    <span class="h-1 w-1 rounded-full bg-[#A5489B]"></span>
                    Website
                </span>
                <span class="flex-1 text-text-secondary">–</span>
                <span class="flex-1 text-text-secondary">–</span>
            </div>
        </div>
        <div
            class="absolute right-px top-8 w-28 bg-card-background py-0.5 shadow-lg ring-1 ring-card-border">
            <div
                v-for="item in menuItems"
                :key="item"
                :class="[
                    'px-3 py-1 text-xs text-text-primary',
                    item === 'Customize...' && 'bg-card-background-active',
                ]">
                {{ item }}
            </div>
        </div>
    </div>
</template>
