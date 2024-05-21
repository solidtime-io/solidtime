<script setup lang="ts">
import TextInput from '@/Components/TextInput.vue';
import {
    formatMoney,
    getOrganizationCurrencyString,
    getOrganizationCurrencySymbol,
} from '../../utils/money';

defineProps<{
    name: string;
}>();

const model = defineModel({
    default: null,
    type: Number,
});

function cleanUpDecimalValue(value: string) {
    value = value.replace(/,/g, '');
    value = value.replace(getOrganizationCurrencySymbol(), '');
    return value.replace(/\./g, '');
}

function updateRate(value: string) {
    value = value.trim();
    if (value.includes(',')) {
        const parts = value.split(',');
        const lastPart = (parts[parts.length - 1] = parts[parts.length - 1]);
        if (lastPart.length === 2) {
            // we detected a decimal number with 2 digits after the comma
            value = cleanUpDecimalValue(value);
            model.value = parseInt(value);
        }
    } else if (value.includes('.')) {
        const parts = value.split('.');
        const lastPart = (parts[parts.length - 1] = parts[parts.length - 1]);
        if (lastPart.length === 2) {
            value = cleanUpDecimalValue(value);
            model.value = parseInt(value);
        }
    } else {
        // if it doesn't contain a comma or a dot, it's probably a whole number so let's convert it to cents
        model.value = parseInt(cleanUpDecimalValue(value)) * 100;
    }
}
function formatCents(modelValue: number) {
    const formattedValue = formatMoney(
        modelValue / 100,
        getOrganizationCurrencyString()
    );
    return formattedValue.replace(getOrganizationCurrencySymbol(), '').trim();
}
</script>

<template>
    <div class="relative">
        <TextInput
            :id="name"
            ref="projectMemberRateInput"
            :modelValue="formatCents(model)"
            @blur="updateRate($event.target.value)"
            type="text"
            :name="name"
            placeholder="Billable Rate"
            class="mt-2 block w-full"
            autocomplete="teamMemberRate" />
        <span>
            <div
                class="absolute top-0 right-0 h-full flex items-center px-4 font-medium">
                <span>
                    {{ getOrganizationCurrencyString() }}
                </span>
            </div>
        </span>
    </div>
</template>

<style scoped></style>
