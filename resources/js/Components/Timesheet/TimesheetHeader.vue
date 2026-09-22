<script setup lang="ts">
import DateRangeNavigator from '@/packages/ui/src/Input/DateRangeNavigator.vue';

defineProps<{
    weekNumber: number;
    rangeLabel: string;
    weekTotalFormatted: string;
}>();

defineEmits<{
    (e: 'previous'): void;
    (e: 'next'): void;
    (e: 'current'): void;
}>();
</script>

<template>
    <div class="flex flex-wrap items-center justify-between gap-4 mb-4 px-2 sm:px-4 lg:px-6">
        <!-- Left: Week navigation -->
        <DateRangeNavigator
            :label="rangeLabel"
            :secondary-label="`W${weekNumber}`"
            previous-test-id="timesheet_prev_week"
            trigger-test-id="timesheet_week_display"
            next-test-id="timesheet_next_week"
            trigger-aria-label="Return to current week"
            @previous="$emit('previous')"
            @next="$emit('next')"
            @select="$emit('current')" />

        <!-- Right: Week total -->
        <div class="flex items-center gap-2.5">
            <span class="text-xs text-text-tertiary uppercase tracking-wider">Week Total</span>
            <span
                data-testid="timesheet_grand_total"
                class="text-sm font-semibold text-text-primary">
                {{ weekTotalFormatted }}
            </span>
        </div>
    </div>
</template>
