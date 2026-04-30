<script setup lang="ts">
import { Button } from '@/packages/ui/src/Buttons';
import { ChevronLeftIcon, ChevronRightIcon, CalendarIcon } from '@heroicons/vue/20/solid';

defineProps<{
    isCurrentWeek: boolean;
    weekNumber: number;
    weekRangeDisplay: string;
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
        <div class="flex items-center gap-2">
            <Button
                variant="outline"
                size="icon"
                class="h-8 w-8"
                data-testid="timesheet_prev_week"
                @click="$emit('previous')">
                <ChevronLeftIcon class="h-4 w-4" />
            </Button>
            <button
                data-testid="timesheet_week_display"
                class="flex items-center gap-2 px-3 py-1.5 text-sm font-medium text-text-primary hover:bg-card-background rounded-md transition"
                @click="$emit('current')">
                <CalendarIcon class="h-4 w-4 text-icon-default" />
                <span v-if="isCurrentWeek">This week</span>
                <span v-else>{{ weekRangeDisplay }}</span>
                <span class="text-text-tertiary">&middot; W{{ weekNumber }}</span>
            </button>
            <Button
                variant="outline"
                size="icon"
                class="h-8 w-8"
                data-testid="timesheet_next_week"
                @click="$emit('next')">
                <ChevronRightIcon class="h-4 w-4" />
            </Button>
        </div>

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
