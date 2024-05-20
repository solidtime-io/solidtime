<script setup lang="ts">
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import { computed, ref } from 'vue';
import type { CreateProjectBody, Project } from '@/utils/api';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useProjectsStore } from '@/utils/useProjects';
import { useFocus } from '@vueuse/core';
import ClientDropdown from '@/Components/Common/Client/ClientDropdown.vue';
import { twMerge } from 'tailwind-merge';
import Badge from '@/Components/Common/Badge.vue';
import { useClientsStore } from '@/utils/useClients';
import { storeToRefs } from 'pinia';
import BillableRateInput from '@/Components/Common/BillableRateInput.vue';
import ProjectColorSelector from '@/Components/Common/Project/ProjectColorSelector.vue';

const { updateProject } = useProjectsStore();
const { clients } = storeToRefs(useClientsStore());
const show = defineModel('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    originalProject: Project;
}>();

const project = ref<CreateProjectBody>({
    name: props.originalProject.name,
    color: props.originalProject.color,
    client_id: props.originalProject.client_id,
    billable_rate: props.originalProject.billable_rate,
});

async function submit() {
    await updateProject(props.originalProject.id, project.value);
    show.value = false;
}

const projectNameInput = ref<HTMLInputElement | null>(null);

useFocus(projectNameInput, { initialValue: true });

const currentClientName = computed(() => {
    if (project.value.client_id) {
        return clients.value.find(
            (client) => client.id === project.value.client_id
        )?.name;
    }
    return 'No Client';
});
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Edit Project {{ props.originalProject.name }} </span>
            </div>
        </template>

        <template #content>
            <div
                class="sm:flex items-center space-y-2 sm:space-y-0 sm:space-x-4">
                <div class="flex-1 flex items-center">
                    <div class="px-3">
                        <ProjectColorSelector
                            v-model="project.color"></ProjectColorSelector>
                    </div>
                    <TextInput
                        id="projectName"
                        ref="projectNameInput"
                        v-model="project.name"
                        type="text"
                        placeholder="Project Name"
                        @keydown.enter="submit()"
                        class="mt-1 block w-full"
                        required
                        autocomplete="projectName" />
                </div>
                <div class="sm:max-w-[120px]">
                    <BillableRateInput
                        v-model="project.billable_rate"
                        name="billable_rate" />
                </div>
                <div class="">
                    <ClientDropdown v-model="project.client_id">
                        <template #trigger>
                            <Badge size="large">
                                <div
                                    :class="
                                        twMerge('inline-block rounded-full')
                                    "></div>
                                <span>
                                    {{ currentClientName }}
                                </span>
                            </Badge>
                        </template>
                    </ClientDropdown>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel</SecondaryButton>

            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="submit">
                Update Project
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
