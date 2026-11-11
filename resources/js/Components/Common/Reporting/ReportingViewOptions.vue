<script setup lang="ts">
import { Switch } from '@/Components/ui/switch';
import { Button, Popover, PopoverContent, PopoverTrigger } from '@/packages/ui/src';
import { Field, FieldLabel } from '@/packages/ui/src/field';
import { Settings } from '@lucide/vue';

const showAmounts = defineModel<boolean>('showAmounts', { default: true });
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                size="sm"
                aria-label="View options"
                data-testid="reporting_view_options"
                class="h-8 w-8 p-0">
                <Settings class="h-4 w-4 text-text-quaternary" />
            </Button>
        </PopoverTrigger>
        <PopoverContent align="end" class="w-64 p-4">
            <div class="space-y-4">
                <div class="text-sm font-semibold">View options</div>
                <Field orientation="horizontal" class="justify-between">
                    <FieldLabel for="reporting-show-amounts">Show amounts</FieldLabel>
                    <Switch
                        id="reporting-show-amounts"
                        v-model="showAmounts"
                        data-testid="reporting_view_options_show_amounts"
                        class="data-[state=checked]:bg-accent-500" />
                </Field>
            </div>
        </PopoverContent>
    </Popover>
</template>
