<script setup lang="ts">
import { ProjectBadge } from '@/packages/ui/src';
import type { TimeEntry } from '@/packages/api/src';
import { twMerge } from 'tailwind-merge';
import { ChevronRightIcon } from '@heroicons/vue/16/solid';
import { computed } from 'vue';
import type { Project, Task } from '@/packages/api/src';

const props = defineProps<{
    timeEntry: TimeEntry;
    highlighted: boolean;
    projects?: Project[];
    tasks?: Task[];
}>();
const project = computed(() => {
    return props.projects?.find(
        (iteratingProject) => iteratingProject.id === props.timeEntry.project_id
    );
});
const task = computed(() => {
    return props.tasks?.find((iteratingTask) => iteratingTask.id === props.timeEntry.task_id);
});
</script>

<template>
    <button
        tabindex="-1"
        :data-select-id="timeEntry.id"
        :class="
            twMerge(
                'px-2 py-1.5 flex items-center space-x-2 w-full rounded text-left',
                props.highlighted && 'bg-card-background-active'
            )
        ">
        <span
            v-if="timeEntry.description !== ''"
            class="text-sm font-medium truncate min-w-0 flex-1">
            {{ timeEntry.description }}
        </span>
        <span v-else class="text-sm text-text-tertiary font-medium flex-1"> No Description </span>
        <ProjectBadge
            ref="projectDropdownTrigger"
            :color="project?.color"
            :name="project?.name"
            class="shrink min-w-0 max-w-[50%]">
            <div v-if="project" class="flex items-center lg:space-x-1 min-w-0">
                <span class="text-xs whitespace-nowrap shrink-0">
                    {{ project?.name }}
                </span>
                <ChevronRightIcon
                    v-if="task"
                    class="w-4 lg:w-5 text-text-secondary shrink-0"></ChevronRightIcon>
                <div v-if="task" class="min-w-0 text-xs truncate">
                    {{ task.name }}
                </div>
            </div>
            <div v-else>No Project</div>
        </ProjectBadge>
    </button>
</template>

<style scoped></style>
