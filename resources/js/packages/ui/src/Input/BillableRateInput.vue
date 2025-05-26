<script setup lang="ts">
import { ref } from 'vue';
import { useFocus } from '@vueuse/core';
import {
    NumberField,
    NumberFieldContent,
    NumberFieldDecrement,
    NumberFieldIncrement,
    NumberFieldInput,
} from '@/Components/ui/number-field';

const props = defineProps<{
    name: string;
    focus?: boolean;
    currency: string;
}>();

const model = defineModel<number | null>({
    default: null,
});

const billableRateInput = ref<HTMLInputElement | null>(null);
useFocus(billableRateInput, { initialValue: props.focus });

function formatValue(modelValue: number | null) {
    return modelValue ? modelValue / 100 : 0;
}
</script>

<template>
    <div class="relative">
        <NumberField
            :id="name"
            ref="billableRateInput"
            :model-value="formatValue(model)"
            :step-snapping="false"
            class="block w-full"
            :format-options="{
                style: 'currency',
                currency: currency,
                currencyDisplay: 'code',
                currencySign: 'accounting',
            }"
            @update:model-value="(value) => (model = value * 100)">
            <NumberFieldContent>
                <NumberFieldDecrement />
                <NumberFieldInput placeholder="Billable Rate" />
                <NumberFieldIncrement />
            </NumberFieldContent>
        </NumberField>
    </div>
</template>

<style scoped></style>
