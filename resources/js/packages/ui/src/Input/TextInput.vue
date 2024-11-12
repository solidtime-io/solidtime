<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { twMerge } from 'tailwind-merge';

const props = defineProps<{
    name?: string;
    class?: string;
}>();

const input = ref<HTMLInputElement | null>(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value?.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
const model = defineModel();
</script>

<template>
    <input
        ref="input"
        :class="
            twMerge(
                'border-input-border border bg-input-background text-white focus:ring-input-border-active focus:ring-0 focus-visible:border-input-border-active rounded-md shadow-sm',
                props.class
            )
        "
        v-model="model"
        :name="name" />
</template>
