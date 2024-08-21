<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { ref } from 'vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { useFocus } from '@vueuse/core';
import { useTasksStore } from '@/utils/useTasks';
import type { Task, UpdateTaskBody } from '@/packages/api/src';

const { updateTask } = useTasksStore();
const show = defineModel('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    task: Task;
}>();

const taskBody = ref<UpdateTaskBody>({
    name: props.task.name,
});

async function submit() {
    await updateTask(props.task.id, taskBody.value);
    show.value = false;
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
                        v-model="taskBody.name"
                        type="text"
                        placeholder="Task Name"
                        @keydown.enter="submit()"
                        class="mt-1 block w-full"
                        required
                        autocomplete="taskName" />
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel </SecondaryButton>
            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="submit">
                Update Task
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
