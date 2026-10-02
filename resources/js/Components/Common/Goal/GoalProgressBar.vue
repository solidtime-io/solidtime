<script setup lang="ts">
import { computed, inject, type ComputedRef } from 'vue';
import type { Goal, Organization } from '@/packages/api/src';
import { formatHumanReadableDuration } from '@/packages/ui/src/utils/time';

const props = defineProps<{
    goal: Goal;
    hideTarget?: boolean;
}>();

const organization = inject<ComputedRef<Organization>>('organization');

// Progress is computed on the server, it is refreshed by the goals query while the page is open
const percentage = computed(() => {
    if (props.goal.target_seconds <= 0) {
        return 0;
    }
    return Math.min(100, (props.goal.progress.tracked_seconds / props.goal.target_seconds) * 100);
});

const barClass = computed(() => {
    if (props.goal.progress.status === 'exceeded') {
        return 'bg-red-500';
    }
    if (props.goal.progress.status === 'achieved') {
        return 'bg-green-500';
    }
    return 'bg-accent-200';
});

function format(seconds: number) {
    return formatHumanReadableDuration(
        seconds,
        organization?.value?.interval_format,
        organization?.value?.number_format
    );
}
</script>

<template>
    <div class="w-full min-w-0" data-testid="goal_progress">
        <div class="bg-tertiary h-1 rounded relative overflow-hidden w-full">
            <div
                class="h-full transition-all duration-500"
                :class="barClass"
                :style="{ width: percentage + '%' }"></div>
        </div>
        <div class="text-xs pt-1.5 flex items-center justify-between space-x-2">
            <span class="text-text-primary tabular-nums" data-testid="goal_tracked_time">{{
                format(goal.progress.tracked_seconds)
            }}</span>
            <span v-if="!hideTarget" class="text-text-secondary">
                of {{ format(goal.target_seconds) }}
            </span>
        </div>
    </div>
</template>

<style scoped></style>
