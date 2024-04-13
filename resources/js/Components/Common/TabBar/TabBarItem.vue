<script setup lang="ts">
import { twMerge } from 'tailwind-merge';
import { computed } from 'vue';

const props = defineProps<{
    active?: boolean;
}>();

const activeClass = computed(() => {
    if (props.active) {
        return 'bg-tab-background hover:bg-tab-background-active border border-tab-border text-white font-semibold';
    }
    return '';
});
</script>

<template>
    <button
        :class="
            twMerge(
                'rounded-md transition px-2 sm:px-3 py-1 sm:py-1.5 text-xs sm:text-sm font-medium hover:text-white',
                activeClass
            )
        ">
        <slot></slot>
    </button>
</template>

<style scoped></style>
