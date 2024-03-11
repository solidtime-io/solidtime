<script setup lang="ts">
import ProjectBadge from '@/Components/common/ProjectBadge.vue';
import TimeTrackerStartStop from '@/Components/common/TimeTrackerStartStop.vue';

defineProps<{
    title: string;
    project: string;
}>();
</script>

<template>
    <div class="px-4 py-2.5 grid grid-cols-5">
        <div class="col-span-4">
            <p class="font-semibold text-white pb-1">
                {{ title }}
            </p>
            <ProjectBadge :name="project"></ProjectBadge>
        </div>
        <div class="flex items-center justify-center">
            <TimeTrackerStartStop></TimeTrackerStartStop>
        </div>
    </div>
</template>
