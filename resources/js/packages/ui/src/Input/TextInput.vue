<script setup lang="ts">
import { onMounted, ref } from 'vue';
import { twMerge } from 'tailwind-merge';

const props = defineProps<{
    name?: string;
    class?: string;
    id?: string;
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
        :id="id"
        ref="input"
        v-model="model"
        :class="
            twMerge(
                'border-input-border border bg-input-background text-text-primary focus-visible:ring-2 focus-visible:ring-ring focus-visible:border-transparent rounded-md shadow-sm',
                props.class
            )
        "
        :name="name" />
</template>
