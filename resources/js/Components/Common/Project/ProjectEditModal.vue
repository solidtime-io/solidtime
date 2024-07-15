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
import Badge from '@/Components/Common/Badge.vue';
import { useClientsStore } from '@/utils/useClients';
import { storeToRefs } from 'pinia';
import ProjectColorSelector from '@/Components/Common/Project/ProjectColorSelector.vue';
import ProjectEditBillableSection from '@/Components/Common/Project/ProjectEditBillableSection.vue';
import { UserCircleIcon } from '@heroicons/vue/20/solid';
import InputLabel from '@/Components/InputLabel.vue';
import ProjectBillableRateModal from '@/Components/Common/Project/ProjectBillableRateModal.vue';

const { updateProject } = useProjectsStore();
const { clients } = storeToRefs(useClientsStore());
const show = defineModel('show', { default: false });
const saving = ref(false);
const showBillableRateModal = ref(false);
const props = defineProps<{
    originalProject: Project;
}>();

const project = ref<CreateProjectBody>({
    name: props.originalProject.name,
    color: props.originalProject.color,
    client_id: props.originalProject.client_id,
    billable_rate: props.originalProject.billable_rate,
    is_billable: props.originalProject.is_billable,
});

async function submit() {
    if (props.originalProject.billable_rate !== project.value.billable_rate) {
        showBillableRateModal.value = true;
        return;
    }
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

async function submitBillableRate() {
    await updateProject(props.originalProject.id, project.value);
    show.value = false;
    showBillableRateModal.value = false;
}
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
                class="sm:flex items-center space-y-2 sm:space-y-0 sm:space-x-5">
                <div class="flex-1 flex items-center">
                    <div class="text-center">
                        <InputLabel for="color" value="Color" />
                        <ProjectColorSelector
                            class="mt-1"
                            v-model="project.color"></ProjectColorSelector>
                    </div>
                </div>
                <div class="w-full">
                    <InputLabel for="projectName" value="Project name" />
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
                <div class="">
                    <InputLabel for="client" value="Client" />
                    <ClientDropdown class="mt-1" v-model="project.client_id">
                        <template #trigger>
                            <Badge
                                class="bg-input-background cursor-pointer hover:bg-tertiary"
                                size="xlarge">
                                <div class="flex items-center space-x-2">
                                    <UserCircleIcon
                                        class="w-5 text-icon-default"></UserCircleIcon>
                                    <span class="whitespace-nowrap">
                                        {{ currentClientName }}
                                    </span>
                                </div>
                            </Badge>
                        </template>
                    </ClientDropdown>
                </div>
            </div>
            <ProjectEditBillableSection
                @submit="submit"
                v-model:isBillable="project.is_billable"
                v-model:billableRate="
                    project.billable_rate
                "></ProjectEditBillableSection>
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
    <ProjectBillableRateModal
        v-model:show="showBillableRateModal"
        @submit="submitBillableRate"
        :new-billable-rate="project.billable_rate"
        :project-name="project.name"></ProjectBillableRateModal>
</template>

<style scoped></style>
