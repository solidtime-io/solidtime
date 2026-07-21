<script setup lang="ts">
import { ExclamationTriangleIcon, ArrowRightIcon } from '@heroicons/vue/20/solid';
import { DropdownMenu, DropdownMenuContent, DropdownMenuTrigger } from '@/packages/ui/src';

// Warning affordance for a misplaced break: an amber triangle that opens a small
// popover offering to jump to the break's day in the calendar. Rendered by the
// caller only when the break is actually misplaced (see `showPlacementHint`).
defineProps<{
    // Local day (YYYY-MM-DD) the calendar should navigate to.
    fixDate: string;
    // Delegated navigation — packages/ui stays router-agnostic.
    fixInCalendar?: (date: string) => void;
}>();
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                data-testid="break_placement_hint"
                title="This break does not align with your work entries"
                class="flex items-center justify-center shrink-0 rounded-full p-0.5 text-amber-500 hover:bg-amber-500/10 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring">
                <ExclamationTriangleIcon class="w-4 h-4" />
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent class="min-w-[260px]" align="start">
            <div class="px-3 py-2 space-y-1.5">
                <p class="text-xs text-text-secondary">
                    This break is not directly between work entries.
                </p>
                <button
                    v-if="fixInCalendar"
                    type="button"
                    data-testid="break_fix_in_calendar"
                    class="inline-flex items-center gap-1 text-sm font-medium text-accent-400 hover:underline"
                    @click="fixInCalendar(fixDate)">
                    Fix in calendar
                    <ArrowRightIcon class="w-3.5 h-3.5" />
                </button>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
