<script setup lang="ts">
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { PlusCircleIcon } from '@heroicons/vue/24/solid';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { computed, ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useTasksStore } from '@/utils/useTasks';
import TaskTableRow from '@/Components/Common/Task/TaskTableRow.vue';
import TaskTableHeading from '@/Components/Common/Task/TaskTableHeading.vue';
import TaskCreateModal from '@/Components/Common/Task/TaskCreateModal.vue';

const { tasks } = storeToRefs(useTasksStore());

const props = defineProps<{
    projectId: string;
}>();

const projectTasks = computed(() => {
    return tasks.value.filter((task) => task.project_id === props.projectId);
});

const createTask = ref(false);
</script>

<template>
    <TaskCreateModal
        :project-id="props.projectId"
        v-model:show="createTask"></TaskCreateModal>
    <div class="flow-root">
        <div class="inline-block min-w-full align-middle">
            <div
                data-testid="task_table"
                class="grid min-w-full"
                style="grid-template-columns: 1fr 150px 80px">
                <TaskTableHeading></TaskTableHeading>
                <div
                    class="col-span-5 py-24 text-center"
                    v-if="projectTasks.length === 0">
                    <PlusCircleIcon
                        class="w-8 text-icon-default inline pb-2"></PlusCircleIcon>
                    <h3 class="text-white font-semibold">No tasks found</h3>
                    <p class="pb-5">Create your first task now!</p>
                    <SecondaryButton @click="createTask = true" :icon="PlusIcon"
                        >Create your First Task
                    </SecondaryButton>
                </div>
                <template v-for="task in projectTasks" :key="task.id">
                    <TaskTableRow :task="task"></TaskTableRow>
                </template>
            </div>
        </div>
    </div>
</template>
