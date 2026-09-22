<script setup lang="ts">
import { ChevronLeft, ChevronRight, CalendarIcon } from '@lucide/vue';
import { Button } from '../Buttons';

withDefaults(
    defineProps<{
        label: string;
        secondaryLabel?: string;
        previousDisabled?: boolean;
        nextDisabled?: boolean;
        previousTestId?: string;
        triggerTestId?: string;
        nextTestId?: string;
        triggerAriaLabel?: string;
    }>(),
    {
        secondaryLabel: undefined,
        previousTestId: undefined,
        triggerTestId: undefined,
        nextTestId: undefined,
        triggerAriaLabel: undefined,
    }
);

const emit = defineEmits<{
    previous: [];
    next: [];
    select: [];
}>();

const triggerClass =
    'h-8 w-[13.5rem] justify-center rounded-none border-l border-r border-border-secondary px-2.5 text-text-primary tabular-nums hover:bg-card-background-active';
</script>

<template>
    <div
        class="tabular-nums inline-flex items-stretch overflow-hidden rounded-md border border-input bg-card-background shadow-xs dark:bg-transparent"
        role="group"
        aria-label="Date range navigation">
        <Button
            type="button"
            variant="ghost"
            size="icon"
            class="h-8 w-8 shrink-0 rounded-none hover:bg-card-background-active"
            aria-label="Previous"
            :disabled="previousDisabled"
            :data-testid="previousTestId"
            @click="emit('previous')">
            <ChevronLeft class="h-4 w-4" />
        </Button>

        <slot name="trigger" :trigger-class="triggerClass">
            <Button
                type="button"
                variant="ghost"
                size="sm"
                :class="triggerClass"
                :aria-label="triggerAriaLabel"
                :data-testid="triggerTestId"
                @click="emit('select')">
                <CalendarIcon class="h-4 w-4 shrink-0 text-icon-default" />
                <span class="min-w-0 truncate">{{ label }}</span>
                <span v-if="secondaryLabel" class="shrink-0 text-text-tertiary">
                    &middot; {{ secondaryLabel }}
                </span>
            </Button>
        </slot>

        <Button
            type="button"
            variant="ghost"
            size="icon"
            class="h-8 w-8 shrink-0 rounded-none hover:bg-card-background-active"
            aria-label="Next"
            :disabled="nextDisabled"
            :data-testid="nextTestId"
            @click="emit('next')">
            <ChevronRight class="h-4 w-4" />
        </Button>
    </div>
</template>
