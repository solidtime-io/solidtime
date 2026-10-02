<script setup lang="ts">
import { computed } from 'vue';
import { CheckCircleIcon, ExclamationCircleIcon, ClockIcon } from '@heroicons/vue/20/solid';
import type { GoalStatus } from '@/packages/api/src';
import { goalStatusIsPositive, goalStatusLabel } from '@/utils/goals';

const props = defineProps<{
    status: GoalStatus;
}>();

const icon = computed(() => {
    if (props.status === 'exceeded') {
        return ExclamationCircleIcon;
    }
    if (props.status === 'achieved') {
        return CheckCircleIcon;
    }
    return ClockIcon;
});

const colorClass = computed(() => {
    if (props.status === 'exceeded') {
        return 'text-red-500';
    }
    if (goalStatusIsPositive(props.status)) {
        return 'text-green-500';
    }
    return 'text-icon-default';
});
</script>

<template>
    <span
        class="inline-flex items-center space-x-1.5 text-sm text-text-primary"
        :data-status="status">
        <component :is="icon" class="w-4" :class="colorClass"></component>
        <span>{{ goalStatusLabel(status) }}</span>
    </span>
</template>

<style scoped></style>
