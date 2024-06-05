<script setup lang="ts">
import TextInput from '@/Components/TextInput.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import DialogModal from '@/Components/DialogModal.vue';
import { computed, ref } from 'vue';
import type { CreateProjectBody } from '@/utils/api';
import { getRandomColor } from '@/utils/color';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import { useProjectsStore } from '@/utils/useProjects';
import { useFocus } from '@vueuse/core';
import ClientDropdown from '@/Components/Common/Client/ClientDropdown.vue';
import Badge from '@/Components/Common/Badge.vue';
import { useClientsStore } from '@/utils/useClients';
import { storeToRefs } from 'pinia';
import ProjectColorSelector from '@/Components/Common/Project/ProjectColorSelector.vue';
import BillableRateInput from '@/Components/Common/BillableRateInput.vue';
import { UserCircleIcon } from '@heroicons/vue/20/solid';
import InputLabel from '@/Components/InputLabel.vue';

const { createProject } = useProjectsStore();
const { clients } = storeToRefs(useClientsStore());
const show = defineModel('show', { default: false });
const saving = ref(false);

const project = ref<CreateProjectBody>({
    name: '',
    color: getRandomColor(),
    client_id: null,
    billable_rate: null,
});

async function submit() {
    await createProject(project.value);
    show.value = false;
    project.value = {
        name: '',
        color: getRandomColor(),
        client_id: null,
        billable_rate: null,
    };
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
                <span> Create Project </span>
            </div>
        </template>

        <template #content>
            <div
                class="sm:flex items-center space-y-2 sm:space-y-0 sm:space-x-4">
                <div class="flex-1 flex items-center">
                    <div class="text-center pr-5">
                        <InputLabel for="color" value="Color" />
                        <ProjectColorSelector
                            class="mt-2.5"
                            v-model="project.color"></ProjectColorSelector>
                    </div>
                    <div class="w-full">
                        <InputLabel for="projectName" value="Project name" />
                        <TextInput
                            id="projectName"
                            name="projectName"
                            ref="projectNameInput"
                            v-model="project.name"
                            type="text"
                            placeholder="The next big thing"
                            @keydown.enter="submit()"
                            class="mt-2 block w-full"
                            required
                            autocomplete="projectName" />
                    </div>
                </div>
                <div class="sm:max-w-[120px]">
                    <InputLabel for="billableRate" value="Billable Rate" />
                    <BillableRateInput
                        v-model="project.billable_rate"
                        name="billableRate" />
                </div>
                <div>
                    <InputLabel for="client" value="Client" />
                    <ClientDropdown class="mt-2" v-model="project.client_id">
                        <template #trigger>
                            <Badge
                                class="bg-input-background cursor-pointer hover:bg-tertiary"
                                size="xlarge">
                                <div class="flex items-center space-x-2">
                                    <UserCircleIcon
                                        class="w-5 text-icon-default"></UserCircleIcon>
                                    <span>
                                        {{ currentClientName }}
                                    </span>
                                </div>
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
                Create Project
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
