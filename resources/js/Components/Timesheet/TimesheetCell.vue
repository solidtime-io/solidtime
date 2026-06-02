<script setup lang="ts">
import { computed } from 'vue';
import { CheckIcon } from '@heroicons/vue/16/solid';
import DurationSecondsInput from '@/packages/ui/src/Input/DurationSecondsInput.vue';
import LoadingSpinner from '@/packages/ui/src/LoadingSpinner.vue';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/packages/ui/src/tooltip';
import type { TimesheetCell } from '@/utils/useTimesheetGrid';
import type { CellSaveStatus } from '@/utils/timesheet/useTimesheetCellMutations';

const props = defineProps<{
    cell?: TimesheetCell;
    dayIndex: number;
    date: string;
    isToday: boolean;
    hasRunningEntry: boolean;
    saveStatus?: CellSaveStatus;
    pendingSeconds?: number;
}>();

const emit = defineEmits<{
    update: [newSeconds: number];
}>();

// Show the optimistic value while saving; `??` (not `||`) so a pending 0 (delete) wins.
const displaySeconds = computed(() => props.pendingSeconds ?? props.cell?.totalSeconds ?? 0);
const isSaving = computed(() => props.saveStatus === 'saving');

// Swap the border color (don't layer) to avoid same-specificity fights.
const inputClass = computed(() => {
    const border = props.saveStatus === 'error' ? 'border-red-500/70' : 'border-input-border';
    return [
        'w-[80px] mx-auto text-center font-medium',
        'bg-transparent text-text-primary placeholder:text-text-quaternary',
        'rounded-lg border shadow-none',
        border,
        'hover:bg-card-background',
        'focus-visible:bg-tertiary focus-visible:border-transparent',
        'focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none',
        'disabled:cursor-wait disabled:opacity-70',
    ].join(' ');
});
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
                <TooltipContent> Stop the running time entry to edit the timesheet </TooltipContent>
            </Tooltip>
        </TooltipProvider>
        <template v-else>
            <span class="relative inline-flex items-center">
                <DurationSecondsInput
                    :model-value="displaySeconds"
                    default-unit="hours"
                    placeholder="-"
                    size="sm"
                    :disabled="isSaving"
                    :input-class="inputClass"
                    @commit="(seconds) => emit('update', seconds ?? 0)" />
                <span
                    v-if="saveStatus === 'saving' || saveStatus === 'saved'"
                    class="pointer-events-none absolute left-full top-1/2 ml-1.5 flex -translate-y-1/2 items-center"
                    :aria-label="saveStatus === 'saving' ? 'Saving' : 'Saved'">
                    <LoadingSpinner
                        v-if="saveStatus === 'saving'"
                        class="h-3 w-3 m-0 text-text-tertiary" />
                    <CheckIcon v-else class="h-3 w-3 text-text-tertiary" />
                </span>
            </span>
        </template>
    </div>
</template>
