<script setup lang="ts">
import { onMounted, ref } from 'vue';

defineProps({
    modelValue: String,
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
        class="border-gray-300 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300 focus:border-indigo-500 dark:focus:border-indigo-600 focus:ring-indigo-500 dark:focus:ring-indigo-600 rounded-md shadow-sm"
        :value="modelValue"
        @input="updateValue" />
</template>
