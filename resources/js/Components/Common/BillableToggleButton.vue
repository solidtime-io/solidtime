<script setup lang="ts">
import { computed } from 'vue';
import { twMerge } from 'tailwind-merge';
const active = defineModel({ default: false });
const emit = defineEmits(['changed']);
function toggleBillable() {
    active.value = !active.value;
    emit('changed', active.value);
}

const props = withDefaults(
    defineProps<{
        size: 'small' | 'base';
    }>(),
    {
        size: 'base',
    }
);

const iconColorClasses = computed(() => {
    if (active.value) {
        return 'text-accent-200/80 focus:text-accent-200 hover:text-accent-200';
    } else {
        return 'text-icon-default focus:text-icon-active hover:text-icon-active';
    }
});

const iconSizeClasses = computed(() => {
    if (props.size === 'small') {
        return 'w-5 h-5';
    } else {
        return 'w-5 sm:w-6 h-5 sm:h-6';
    }
});

const iconSizeWrapperClasses =
    props.size === 'small'
        ? 'w-6 sm:w-8 h-6 sm:h-8'
        : 'w-7 sm:w-10 h-7 sm:h-10';
</script>

<template>
    <button
        @click="toggleBillable"
        :class="
            twMerge(
                iconColorClasses,
                iconSizeWrapperClasses,
                'flex-shrink-0 ring-0 focus:outline-none focus:ring-0 transition focus:bg-card-background-separator hover:bg-card-background-separator rounded-full flex items-center justify-center'
            )
        ">
        <svg
            :class="iconSizeClasses"
            viewBox="0 0 8 14"
            fill="none"
            xmlns="http://www.w3.org/2000/svg">
            <path
                d="M4 1V13M1 10.182L1.879 10.841C3.05 11.72 4.949 11.72 6.121 10.841C7.293 9.962 7.293 8.538 6.121 7.659C5.536 7.219 4.768 7 4 7C3.275 7 2.55 6.78 1.997 6.341C0.891 5.462 0.891 4.038 1.997 3.159C3.103 2.28 4.897 2.28 6.003 3.159L6.418 3.489"
                stroke="currentColor"
                stroke-width="1.5"
                stroke-linecap="round"
                stroke-linejoin="round" />
        </svg>
    </button>
</template>

<style scoped></style>
