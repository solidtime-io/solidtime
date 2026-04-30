<script setup lang="ts">
import { computed, inject, ref, type ComputedRef } from 'vue';
import { formatHumanReadableDuration, parseTimeInput } from '@/packages/ui/src/utils/time';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import type { Organization } from '@/packages/api/src';

const organization = inject<ComputedRef<Organization>>('organization');

const organizationSettings = computed(() => ({
    intervalFormat: organization?.value?.interval_format ?? 'hours-minutes',
    numberFormat: organization?.value?.number_format ?? 'point',
}));

const props = withDefaults(
    defineProps<{
        modelValue?: number | null;
        placeholder?: string;
        disabled?: boolean;
        inputClass?: string;
        size?: 'sm' | 'base';
        defaultUnit?: 'auto' | 'hours' | 'minutes';
    }>(),
    {
        modelValue: null,
        placeholder: '-',
        disabled: false,
        inputClass: '',
        size: 'base',
        defaultUnit: 'auto',
    }
);

const emit = defineEmits<{
    'update:modelValue': [value: number | null];
    commit: [value: number | null];
    submit: [];
}>();

const temporaryValue = ref('');
const isEditing = ref(false);
const hasPendingEdit = ref(false);
const skipNextCommit = ref(false);

function formatModelValue(value: number | null | undefined): string {
    if (!value || value === 0) {
        return '';
    }

    return formatHumanReadableDuration(
        value,
        organizationSettings.value.intervalFormat,
        organizationSettings.value.numberFormat
    );
}

const displayValue = computed({
    get() {
        if (isEditing.value) {
            return temporaryValue.value;
        }
        return formatModelValue(props.modelValue);
    },
    set(newValue: string) {
        temporaryValue.value = newValue;
        hasPendingEdit.value = true;
    },
});

function selectInput(event: Event) {
    isEditing.value = true;
    hasPendingEdit.value = false;
    skipNextCommit.value = false;
    temporaryValue.value = formatModelValue(props.modelValue);
    const target = event.target as HTMLInputElement;
    target.select();
}

function resetEditingState() {
    temporaryValue.value = '';
    isEditing.value = false;
    hasPendingEdit.value = false;
}

function commitValue() {
    if (skipNextCommit.value) {
        skipNextCommit.value = false;
        return;
    }

    const input = temporaryValue.value.trim();
    const shouldCommit = hasPendingEdit.value;
    resetEditingState();

    if (!shouldCommit) {
        return;
    }

    // Blank or literal "0" → null. Consumers decide what null means
    // (clear estimate, delete cell, etc.) by reading their own emit.
    if (input === '' || input === '0') {
        emit('update:modelValue', null);
        emit('commit', null);
        return;
    }

    const defaultUnit =
        props.defaultUnit === 'auto'
            ? organizationSettings.value.intervalFormat === 'decimal'
                ? 'hours'
                : 'minutes'
            : props.defaultUnit;
    const seconds = parseTimeInput(input, organizationSettings.value.numberFormat, defaultUnit);

    if (seconds !== null && seconds >= 0) {
        emit('update:modelValue', seconds);
        emit('commit', seconds);
    }
}

function cancelEdit(event: Event) {
    skipNextCommit.value = true;
    resetEditingState();
    (event.target as HTMLInputElement).blur();
}

function commitAndSubmit() {
    commitValue();
    emit('submit');
}
</script>

<template>
    <TextInput
        v-model="displayValue"
        data-testid="duration_seconds_input"
        name="Duration"
        :size="size"
        :disabled="disabled"
        :placeholder="isEditing ? '0' : placeholder"
        :class="inputClass"
        @focus="selectInput"
        @blur="commitValue"
        @keydown.enter.prevent="commitAndSubmit"
        @keydown.escape="cancelEdit" />
</template>
