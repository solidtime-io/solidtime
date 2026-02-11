<script setup lang="ts">
import EstimatedTimeInput from '@/packages/ui/src/Input/EstimatedTimeInput.vue';
import { ClockIcon } from '@heroicons/vue/20/solid';
import { Field, FieldDescription, FieldLabel } from './field';

const model = defineModel<number | null>();
const emit = defineEmits(['submit']);
</script>

<template>
    <Field>
        <FieldLabel for="time_estimated" :icon="ClockIcon">Time Estimated</FieldLabel>
        <EstimatedTimeInput
            id="time_estimated"
            v-model="model"
            @submit="emit('submit')"></EstimatedTimeInput>
        <FieldDescription>
            You can type natural language like
            <span class="font-semibold">2h 30m</span>
        </FieldDescription>
    </Field>
</template>

<style scoped></style>
