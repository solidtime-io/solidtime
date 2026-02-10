<script setup lang="ts">
import EstimatedTimeInput from '@/packages/ui/src/Input/EstimatedTimeInput.vue';
import { ClockIcon } from '@heroicons/vue/20/solid';
import { Field, FieldLabel } from './field';

const model = defineModel<number | null>();
const emit = defineEmits(['submit']);
</script>

<template>
    <Field class="pt-6">
        <div class="flex items-center space-x-1">
            <ClockIcon class="text-text-quaternary w-4"></ClockIcon>
            <FieldLabel for="billable">Time Estimated</FieldLabel>
        </div>
        <EstimatedTimeInput v-model="model" @submit="emit('submit')"></EstimatedTimeInput>
        <div class="flex items-center text-text-secondary text-xs pt-2 pl-1">
            <span>
                <span class="font-semibold">Info:</span>
                You can type natural language like
                <span class="font-semibold">2h 30m</span>
            </span>
        </div>
    </Field>
</template>

<style scoped></style>
