<script setup lang="ts">
import DurationSecondsInput from '@/packages/ui/src/Input/DurationSecondsInput.vue';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/packages/ui/src/tooltip';
import type { TimesheetCell } from '@/utils/useTimesheetGrid';

defineProps<{
    cell?: TimesheetCell;
    dayIndex: number;
    date: string;
    isToday: boolean;
    hasRunningEntry: boolean;
}>();

const emit = defineEmits<{
    update: [newSeconds: number];
}>();
</script>

<template>
    <div
        data-testid="timesheet_cell"
        class="flex items-center justify-center border-t border-default-background-separator"
        :class="{ 'bg-default-background': isToday }">
        <TooltipProvider v-if="hasRunningEntry" :delay-duration="100">
            <Tooltip>
                <TooltipTrigger as-child>
                    <span class="inline-block cursor-not-allowed">
                        <DurationSecondsInput
                            :model-value="cell?.totalSeconds ?? 0"
                            disabled
                            default-unit="hours"
                            placeholder="-"
                            size="sm"
                            input-class="w-[80px] mx-auto text-center font-medium
                                bg-transparent text-text-primary placeholder:text-text-quaternary
                                rounded-lg border border-input-border shadow-none
                                pointer-events-none
                                disabled:opacity-50 disabled:cursor-not-allowed" />
                    </span>
                </TooltipTrigger>
                <TooltipContent>
                    Stop the running time entry to edit the timesheet
                </TooltipContent>
            </Tooltip>
        </TooltipProvider>
        <DurationSecondsInput
            v-else
            :model-value="cell?.totalSeconds ?? 0"
            default-unit="hours"
            placeholder="-"
            size="sm"
            input-class="w-[80px] mx-auto text-center font-medium
                bg-transparent text-text-primary placeholder:text-text-quaternary
                rounded-lg border border-input-border shadow-none
                hover:bg-card-background
                focus-visible:bg-tertiary focus-visible:border-transparent
                focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
            @commit="(seconds) => emit('update', seconds ?? 0)" />
    </div>
</template>
