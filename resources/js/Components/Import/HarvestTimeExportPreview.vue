<script setup lang="ts">
// Import/Export settings of Harvest with the "Export all time" button
const navigation = ['Billing', 'Company preferences', 'Modules', 'Import/Export', 'Bulk actions'];
</script>

<template>
    <div
        role="img"
        aria-label="Import/Export settings in Harvest with the Export all time button"
        class="w-full max-w-md select-none overflow-hidden rounded-lg border border-card-border bg-card-background">
        <div class="border-b border-card-border px-3 py-2 text-sm font-medium text-text-primary">
            Settings
        </div>
        <div class="flex">
            <div class="w-32 flex-none space-y-0.5 border-r border-card-border p-2">
                <div
                    v-for="item in navigation"
                    :key="item"
                    :class="[
                        'whitespace-nowrap rounded-md px-2 py-1 text-[11px]',
                        item === 'Import/Export'
                            ? 'bg-[#FA5D00]/10 font-medium text-text-primary'
                            : 'text-text-secondary',
                    ]">
                    {{ item }}
                </div>
            </div>
            <div class="min-w-0 flex-1 space-y-1.5 px-3 py-3">
                <div class="text-sm font-semibold text-text-primary">Export data</div>
                <div class="text-[10px] leading-snug text-text-secondary">
                    Export all time and expense data from your Harvest account.
                </div>
                <div class="flex flex-wrap gap-1.5 pt-1">
                    <span
                        class="flex h-6 items-center whitespace-nowrap rounded-md border border-text-secondary px-2 text-[11px] font-medium text-text-primary">
                        Export all time
                    </span>
                    <span
                        class="flex h-6 items-center whitespace-nowrap rounded-md border border-card-border px-2 text-[11px] text-text-primary">
                        Export all expenses
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
