<script setup lang="ts">
import ProjectBadge from '@/Components/Common/Project/ProjectBadge.vue';
import TimeTrackerStartStop from '@/Components/Common/TimeTrackerStartStop.vue';

defineProps<{
    title: string;
    project: string;
}>();
</script>

<template>
    <div class="px-3.5 py-2 grid grid-cols-5">
        <div class="col-span-4">
            <p class="font-semibold text-white text-sm pb-1">
                {{ title }}
            </p>
            <ProjectBadge :name="project"></ProjectBadge>
        </div>
        <div class="flex items-center justify-center">
            <TimeTrackerStartStop></TimeTrackerStartStop>
        </div>
    </div>
</template>
