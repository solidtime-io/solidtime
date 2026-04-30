<script setup lang="ts">
import DurationSecondsInput from '@/packages/ui/src/Input/DurationSecondsInput.vue';
import { twMerge } from 'tailwind-merge';

const model = defineModel<number | null>({
    default: null,
});

const emit = defineEmits<{
    submit: [];
}>();

const props = defineProps<{
    class?: string;
}>();
</script>

<template>
    <DurationSecondsInput
        v-model="model"
        :input-class="twMerge('placeholder:text-text-tertiary', props.class)"
        placeholder="e.g. 2h 30m or 1.5"
        default-unit="hours"
        @submit="emit('submit')" />
</template>
