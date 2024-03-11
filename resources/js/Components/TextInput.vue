<script setup lang="ts">
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: String,
    name: String,
});

const input = ref<HTMLInputElement | null>(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value?.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });

const emit = defineEmits(['update:modelValue']);

function updateValue(event: Event) {
    if (event.target && 'value' in event.target) {
        emit('update:modelValue', event?.target?.value);
    }
}
</script>

<template>
    <input
        ref="input"
        class="border-input-border bg-input-background text-white focus:border-input-border-active rounded-md shadow-sm"
        :value="modelValue"
        :name="name"
        @input="updateValue" />
</template>
