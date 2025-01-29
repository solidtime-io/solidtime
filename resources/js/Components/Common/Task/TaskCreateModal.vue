<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { ref } from 'vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { useFocus } from '@vueuse/core';
import { useTasksStore } from '@/utils/useTasks';
import ProjectDropdown from '@/Components/Common/Project/ProjectDropdown.vue';
import EstimatedTimeSection from '@/packages/ui/src/EstimatedTimeSection.vue';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';

const { createTask } = useTasksStore();
const show = defineModel('show', { default: false });
const saving = ref(false);

const taskName = ref('');
const estimatedTime = ref<number | null>(null);

const props = defineProps<{
    projectId: string;
}>();

async function submit() {
    await createTask({
        name: taskName.value,
        project_id: props.projectId,
        estimated_time: estimatedTime.value,
    });
    show.value = false;
    taskName.value = '';
}

const taskNameInput = ref<HTMLInputElement | null>(null);

useFocus(taskNameInput, { initialValue: true });
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Create Task </span>
            </div>
        </template>

        <template #content>
            <div class="flex items-center space-x-4">
                <div class="col-span-6 sm:col-span-4 flex-1">
                    <TextInput
                        id="taskName"
                        ref="taskNameInput"
                        v-model="taskName"
                        type="text"
                        placeholder="Task Name"
                        class="mt-1 block w-full"
                        required
                        autocomplete="taskName"
                        @keydown.enter="submit()" />
                </div>
                <div class="col-span-6 sm:col-span-4">
                    <ProjectDropdown :model-value="projectId"></ProjectDropdown>
                </div>
            </div>
            <EstimatedTimeSection
                v-if="isAllowedToPerformPremiumAction()"
                v-model="estimatedTime"
                @submit="submit()"></EstimatedTimeSection>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel </SecondaryButton>
            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="submit">
                Create Task
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
