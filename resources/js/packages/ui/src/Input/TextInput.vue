<script setup lang="ts">
import { onMounted, ref } from 'vue';

defineProps<{
    name?: string;
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
        class="border-input-border border bg-input-background text-white focus:ring-input-border-active focus:ring-0 focus-visible:border-input-border-active rounded-md shadow-sm"
        v-model="model"
        :name="name" />
</template>
