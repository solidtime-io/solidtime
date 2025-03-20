<script setup lang="ts">
import ProjectBadge from '@/packages/ui/src/Project/ProjectBadge.vue';
import TimeTrackerStartStop from '@/packages/ui/src/TimeTrackerStartStop.vue';
import { useProjectsStore } from '@/utils/useProjects';
import { storeToRefs } from 'pinia';
import { computed } from 'vue';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { getDayJsInstance } from '@/packages/ui/src/utils/time';
import type { TimeEntry } from "@/packages/api/src";
import { useTasksStore } from "@/utils/useTasks";
import { ChevronRightIcon } from "@heroicons/vue/16/solid";

const props = defineProps<{
    timeEntry: TimeEntry
}>();

const { projects } = storeToRefs(useProjectsStore());

const project = computed(() => {
    return projects.value.find((project) => project.id === props.timeEntry.project_id);
});

const {tasks} = storeToRefs(useTasksStore());

const task = computed(() => {
    return tasks.value.find((task) => task.id === props.timeEntry.task_id);
});

const { currentTimeEntry } = storeToRefs(useCurrentTimeEntryStore());
const { setActiveState } = useCurrentTimeEntryStore();

async function startTaskTimer() {
    if (currentTimeEntry.value.id) {
        await setActiveState(false);
    }
    currentTimeEntry.value.description = props.timeEntry.description;
    currentTimeEntry.value.project_id = props.timeEntry.project_id;
    currentTimeEntry.value.task_id = props.timeEntry.task_id;
    currentTimeEntry.value.tags = props.timeEntry.tags;
    currentTimeEntry.value.billable = props.timeEntry.billable;
    currentTimeEntry.value.start = getDayJsInstance().utc().format();
    await setActiveState(true);
    useCurrentTimeEntryStore().fetchCurrentTimeEntry();
}

</script>

<template>
    <div
        class="px-3.5 py-2 grid grid-cols-5 border-b border-b-card-background-separator">
        <div class="col-span-4">
            <p class="font-medium text-white text-sm pb-1 truncate">
                <span v-if="timeEntry.description"> {{ timeEntry.description }}</span>
                <span v-else class="text-text-tertiary">No description</span>
            </p>
            <ProjectBadge
                size="base"
                class="min-w-0 max-w-full"
                :color="project?.color">
                <div class="flex items-center lg:space-x-0.5 min-w-0">
                    <span class="whitespace-nowrap ">
                        {{ project?.name ?? 'No Project' }}
                    </span>
                    <ChevronRightIcon
                        v-if="task"
                        class="w-4 text-muted shrink-0"></ChevronRightIcon>
                    <div
                        v-if="task"
                        class="min-w-0 shrink truncate">
                        {{ task.name }}
                    </div>
                </div>
            </ProjectBadge>
        </div>
        <div class="flex items-center justify-center">
            <TimeTrackerStartStop
                @changed="startTaskTimer"></TimeTrackerStartStop>
        </div>
    </div>
</template>
