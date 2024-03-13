<script setup lang="ts">
import TimeTrackerStartStop from '@/Components/common/TimeTrackerStartStop.vue';
</script>

<template>
    <div class="py-4 px-2 flex justify-between items-center">
        <div>
            <div class="text-muted font-extrabold text-xs">Current Timer</div>
            <div class="text-white font-medium text-lg py-1">1h 23min</div>
        </div>
        <TimeTrackerStartStop size="base"></TimeTrackerStartStop>
    </div>
</template>
