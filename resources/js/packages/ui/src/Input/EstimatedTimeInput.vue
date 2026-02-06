<script setup lang="ts">
import { onMounted, ref, watch, inject } from 'vue';
import { formatHumanReadableDuration, parseTimeInput } from '@/packages/ui/src/utils/time';
import { twMerge } from 'tailwind-merge';
import { TextInput } from '@/packages/ui/src';
import type { Organization } from '@/packages/api/src';
import { type ComputedRef } from 'vue';

const temporaryInput = ref<string>('');

const model = defineModel<number | null>({
    default: null,
});

const emit = defineEmits<{
    submit: [];
}>();

const organization = inject<ComputedRef<Organization>>('organization');

function updateDuration() {
    const input = temporaryInput.value.trim();

    if (input === '') {
        model.value = null;
        return;
    }

    // Use parseTimeInput with 'hours' as default unit for estimated time
    const seconds = parseTimeInput(input, 'hours');
    if (seconds !== null && seconds > 0) {
        model.value = seconds;
    }

    updateInputDisplay();
}

const props = defineProps<{
    class?: string;
}>();

watch(model, updateInputDisplay);
onMounted(() => updateInputDisplay());

function updateInputDisplay() {
    if (model.value !== null && model.value > 0) {
        temporaryInput.value = formatHumanReadableDuration(
            model.value,
            organization?.value?.interval_format,
            organization?.value?.number_format
        );
    } else {
        temporaryInput.value = '';
    }
}

function selectInput(event: Event) {
    const target = event.target as HTMLInputElement;
    target.select();
}

function updateAndSubmit() {
    updateDuration();
    emit('submit');
}
</script>

<template>
    <TextInput
        ref="inputField"
        v-model="temporaryInput"
        :class="twMerge('text-text-secondary', props.class)"
        type="text"
        placeholder="e.g. 2h 30m or 1.5"
        @focus="selectInput"
        @blur="updateDuration"
        @keydown.enter="updateAndSubmit" />
</template>

<style scoped></style>
