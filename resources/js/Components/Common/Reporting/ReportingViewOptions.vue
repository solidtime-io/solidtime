<script setup lang="ts">
import { Switch } from '@/Components/ui/switch';
import { Popover, PopoverContent, PopoverTrigger, SecondaryButton } from '@/packages/ui/src';
import { Field, FieldLabel } from '@/packages/ui/src/field';
import { ChevronDown, SlidersHorizontal } from '@lucide/vue';

const showAmounts = defineModel<boolean>('showAmounts', { default: true });
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <SecondaryButton
                :icon="SlidersHorizontal"
                :aria-label="showAmounts ? 'View options' : 'View options, amounts hidden'"
                data-testid="reporting_view_options"
                class="relative">
                <span class="flex items-center gap-1">
                    View
                    <ChevronDown class="h-3 w-3 text-text-tertiary" />
                </span>
                <span
                    v-if="!showAmounts"
                    class="absolute -top-1 -right-1 h-2.5 w-2.5 rounded-full bg-accent-400 ring-2 ring-default-background"></span>
            </SecondaryButton>
        </PopoverTrigger>
        <PopoverContent align="end" class="w-64 p-4">
            <div class="space-y-4">
                <div class="text-sm font-semibold">View options</div>
                <div class="space-y-1.5">
                    <Field orientation="horizontal" class="justify-between">
                        <FieldLabel for="reporting-show-amounts">Show amounts</FieldLabel>
                        <Switch
                            id="reporting-show-amounts"
                            v-model="showAmounts"
                            data-testid="reporting_view_options_show_amounts"
                            class="data-[state=checked]:bg-accent-500" />
                    </Field>
                    <p class="text-xs text-text-tertiary">
                        Also applies to exports and saved reports.
                    </p>
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>
