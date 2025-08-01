<script setup lang="ts">
import { twMerge } from 'tailwind-merge';

const props = defineProps<{
    value?: string;
    class?: string;
}>();
</script>

<template>
    <label :class="twMerge('block font-medium text-sm text-text-primary', props.class)">
        <span v-if="value">{{ value }}</span>
        <span v-else><slot /></span>
    </label>
</template>
