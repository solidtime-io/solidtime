<script setup lang="ts">
import { computed } from 'vue';
import { twMerge } from 'tailwind-merge';
import BillableIcon from '@/Components/Common/Icons/BillableIcon.vue';
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
        return 'text-accent-300 focus:text-accent-200 hover:text-accent-200';
    } else {
        return 'text-icon-default focus:text-icon-active hover:text-icon-active';
    }
});

const iconSizeClasses = computed(() => {
    if (props.size === 'small') {
        return 'w-5 h-5';
    } else {
        return 'w-5 lg:w-6 h-5 lg:h-6';
    }
});

const iconSizeWrapperClasses =
    props.size === 'small' ? 'w-6 sm:w-8 h-6 sm:h-8' : 'w-11 h-11';
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
        <BillableIcon :class="iconSizeClasses"></BillableIcon>
    </button>
</template>

<style scoped></style>
