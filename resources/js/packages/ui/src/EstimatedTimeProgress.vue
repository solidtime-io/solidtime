<script setup lang="ts">
import { computed } from 'vue';

const props = defineProps<{ estimated: number; current: number }>();
function formatHours(seconds: number) {
    return Math.round(seconds / 60 / 60) + 'h';
}

const isOverEstimate = computed(() => props.current > props.estimated);
const progressBarPercentage = computed(() => {
    return (props.current / props.estimated) * 100;
});
const formattedProgressBarPercentage = computed(() => {
    return progressBarPercentage.value.toFixed(1) + '%';
});
</script>
<template>
    <div class="w-full">
        <div class="bg-tertiary h-1 rounded relative overflow-hidden w-full">
            <div
                class="h-full"
                :class="{
                    'bg-accent-200': !isOverEstimate,
                    'bg-red-500': isOverEstimate,
                }"
                :style="{ width: progressBarPercentage + '%' }"></div>
        </div>
        <div class="text-xs font-semibold pt-1.5">
            {{ formattedProgressBarPercentage }} of
            {{ formatHours(estimated) }}
        </div>
    </div>
</template>

<style scoped></style>
