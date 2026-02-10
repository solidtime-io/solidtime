<script setup lang="ts">
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { computed, ref } from 'vue';
import type { CreateClientBody, CreateProjectBody, Project } from '@/packages/api/src';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { useProjectsStore } from '@/utils/useProjects';
import { useClientsStore } from '@/utils/useClients';
import { useFocus } from '@vueuse/core';
import ClientDropdown from '@/packages/ui/src/Client/ClientDropdown.vue';
import Badge from '@/packages/ui/src/Badge.vue';
import { useClientsQuery } from '@/utils/useClientsQuery';
import ProjectColorSelector from '@/packages/ui/src/Project/ProjectColorSelector.vue';
import { UserCircleIcon } from '@heroicons/vue/20/solid';
import EstimatedTimeSection from '@/packages/ui/src/EstimatedTimeSection.vue';
import { Field, FieldLabel } from '@/packages/ui/src/field';
import ProjectBillableRateModal from '@/packages/ui/src/Project/ProjectBillableRateModal.vue';
import { getOrganizationCurrencyString } from '@/utils/money';
import ProjectEditBillableSection from '@/packages/ui/src/Project/ProjectEditBillableSection.vue';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';

const { updateProject } = useProjectsStore();
const { clients } = useClientsQuery();
const show = defineModel('show', { default: false });
const saving = ref(false);
const showBillableRateModal = ref(false);
const props = defineProps<{
    originalProject: Project;
}>();

async function createClient(body: CreateClientBody) {
    return await useClientsStore().createClient(body);
}

const project = ref<CreateProjectBody>({
    name: props.originalProject.name,
    color: props.originalProject.color,
    client_id: props.originalProject.client_id,
    billable_rate: props.originalProject.billable_rate,
    is_billable: props.originalProject.is_billable,
    estimated_time: props.originalProject.estimated_time,
});

async function submit() {
    if (props.originalProject.billable_rate !== project.value.billable_rate) {
        // make sure that the alert modal is not immediately submitted when user presses enter
        setTimeout(() => {
            showBillableRateModal.value = true;
        }, 0);
        return;
    }
    await updateProject(props.originalProject.id, project.value);
    show.value = false;
}

const projectNameInput = ref<HTMLInputElement | null>(null);

useFocus(projectNameInput, { initialValue: true });

const currentClientName = computed(() => {
    if (project.value.client_id) {
        return clients.value.find((client) => client.id === project.value.client_id)?.name;
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
            <div class="sm:flex items-center space-y-2 sm:space-y-0 sm:space-x-5">
                <Field class="flex-1 flex items-center">
                    <div class="text-center">
                        <FieldLabel for="color">Color</FieldLabel>
                        <ProjectColorSelector v-model="project.color"></ProjectColorSelector>
                    </div>
                </Field>
                <Field class="w-full">
                    <FieldLabel for="projectName">Project name</FieldLabel>
                    <TextInput
                        id="projectName"
                        ref="projectNameInput"
                        v-model="project.name"
                        type="text"
                        placeholder="Project Name"
                        class="block w-full"
                        required
                        autocomplete="projectName"
                        @keydown.enter="submit()" />
                </Field>
                <Field>
                    <FieldLabel for="client">Client</FieldLabel>
                    <ClientDropdown v-model="project.client_id" :create-client :clients="clients">
                        <template #trigger>
                            <Badge
                                class="bg-input-background cursor-pointer hover:bg-tertiary"
                                size="xlarge">
                                <div class="flex items-center space-x-2">
                                    <UserCircleIcon class="w-5 text-icon-default"></UserCircleIcon>
                                    <span class="whitespace-nowrap">
                                        {{ currentClientName }}
                                    </span>
                                </div>
                            </Badge>
                        </template>
                    </ClientDropdown>
                </Field>
            </div>
            <div>
                <div>
                    <ProjectEditBillableSection
                        v-model:is-billable="project.is_billable"
                        v-model:billable-rate="project.billable_rate"
                        :currency="getOrganizationCurrencyString()"
                        @submit="submit"></ProjectEditBillableSection>
                </div>
                <div>
                    <EstimatedTimeSection
                        v-if="isAllowedToPerformPremiumAction()"
                        v-model="project.estimated_time"
                        @submit="submit()"></EstimatedTimeSection>
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
    <ProjectBillableRateModal
        v-model:show="showBillableRateModal"
        :currency="getOrganizationCurrencyString()"
        :new-billable-rate="project.billable_rate"
        :project-name="project.name"
        @submit="submitBillableRate"></ProjectBillableRateModal>
</template>

<style scoped></style>
