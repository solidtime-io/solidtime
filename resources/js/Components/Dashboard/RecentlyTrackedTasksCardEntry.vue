<script setup lang="ts">
import ProjectBadge from '@/packages/ui/src/Project/ProjectBadge.vue';
import TimeTrackerStartStop from '@/packages/ui/src/TimeTrackerStartStop.vue';
import { useProjectsStore } from '@/utils/useProjects';
import { storeToRefs } from 'pinia';
import { computed } from 'vue';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { getDayJsInstance } from '@/packages/ui/src/utils/time';

const props = defineProps<{
    title: string;
    project_id: string;
    task_id: string;
}>();

const { projects } = storeToRefs(useProjectsStore());

const project = computed(() => {
    return projects.value.find((project) => project.id === props.project_id);
});

const { currentTimeEntry } = storeToRefs(useCurrentTimeEntryStore());
const { setActiveState } = useCurrentTimeEntryStore();

async function startTaskTimer() {
    if (currentTimeEntry.value.id) {
        await setActiveState(false);
    }
    currentTimeEntry.value.project_id = props.project_id;
    currentTimeEntry.value.task_id = props.task_id;
    currentTimeEntry.value.start = getDayJsInstance().utc().format();
    await setActiveState(true);
    useCurrentTimeEntryStore().fetchCurrentTimeEntry();
}
</script>

<template>
    <div
        class="px-3.5 py-2 grid grid-cols-5 border-b border-b-card-background-separator">
        <div class="col-span-4">
            <p class="font-semibold text-white text-sm pb-1">
                {{ title }}
            </p>
            <ProjectBadge
                :name="project?.name"
                :color="project?.color"></ProjectBadge>
        </div>
        <div class="flex items-center justify-center">
            <TimeTrackerStartStop
                @changed="startTaskTimer"></TimeTrackerStartStop>
        </div>
    </div>
</template>
