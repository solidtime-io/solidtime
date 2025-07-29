<script setup lang="ts">
import { computed } from 'vue';
import { twMerge } from 'tailwind-merge';

const model = defineModel<string | number>({
    default: '',
});

const props = withDefaults(
    defineProps<{
        id?: string;
        type?: string;
        class?: string;
        rows?: number;
        placeholder?: string;
        disabled?: boolean;
        required?: boolean;
    }>(),
    {
        type: 'text',
        class: '',
        rows: 3,
        disabled: false,
        required: false,
    }
);

const classes = computed(() => {
    return twMerge(
        'block w-full rounded-md bg-input-background  border-input-border shadow-sm focus:border-transparent focus:ring-ring disabled:opacity-50',
        props.class
    );
});
</script>

<template>
    <textarea
        :id="id"
        v-model="model"
        :type="type"
        :class="classes"
        :rows="rows"
        :placeholder="placeholder"
        :disabled="disabled"
        :required="required" />
</template>
