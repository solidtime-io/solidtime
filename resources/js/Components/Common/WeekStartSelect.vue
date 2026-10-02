<script setup lang="ts">
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/packages/ui/src';
import { weekdayOptions } from '@/utils/weekdays';
import type { Weekday } from '@/packages/api/src';

const model = defineModel<Weekday | ''>({ required: true });

defineProps<{
    id?: string;
    disabled?: boolean;
}>();
</script>

<template>
    <Select v-model="model" :disabled="disabled">
        <SelectTrigger :id="id" class="w-full font-normal">
            <SelectValue placeholder="Select a week day" />
        </SelectTrigger>
        <SelectContent>
            <SelectItem
                v-for="weekday in weekdayOptions"
                :key="weekday.value"
                :value="weekday.value">
                {{ weekday.label }}
            </SelectItem>
        </SelectContent>
    </Select>
</template>
