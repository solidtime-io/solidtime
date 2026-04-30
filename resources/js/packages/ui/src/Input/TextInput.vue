<script setup lang="ts">
import { computed, onMounted, ref } from 'vue';
import { twMerge } from 'tailwind-merge';

const props = withDefaults(
    defineProps<{
        name?: string;
        class?: string;
        size?: 'sm' | 'base';
    }>(),
    { size: 'base' }
);

const input = ref<HTMLInputElement | null>(null);

onMounted(() => {
    if (input.value?.hasAttribute('autofocus')) {
        input.value?.focus();
    }
});

defineExpose({ focus: () => input.value?.focus() });
const model = defineModel();

const sizeClasses = computed(() =>
    props.size === 'sm' ? 'h-7 px-2 py-0.5 text-xs' : 'h-9 px-3 py-1 text-base sm:text-sm'
);
</script>

<template>
    <input
        ref="input"
        v-model="model"
        :class="
            twMerge(
                'border-input-border border bg-input-background text-text-primary focus-visible:ring-2 focus-visible:ring-ring focus-visible:border-transparent rounded-md shadow-sm',
                sizeClasses,
                props.class
            )
        "
        :name="name" />
</template>
