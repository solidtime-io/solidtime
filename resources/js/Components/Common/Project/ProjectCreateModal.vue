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
import { twMerge } from 'tailwind-merge';
import Badge from '@/Components/Common/Badge.vue';
import { useClientsStore } from '@/utils/useClients';
import { storeToRefs } from 'pinia';

const { createProject } = useProjectsStore();
const { clients } = storeToRefs(useClientsStore());
const show = defineModel('show', { default: false });
const saving = ref(false);

const project = ref<CreateProjectBody>({
    name: '',
    color: getRandomColor(),
    client_id: null,
});

async function submit() {
    await createProject(project.value);
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
                <span> Create Project </span>
            </div>
        </template>

        <template #content>
            <div class="flex items-center space-x-4">
                <div class="px-3">
                    <div
                        :style="{
                            backgroundColor: project.color,
                            boxShadow: `var(--tw-ring-inset) 0 0 0 calc(5px + var(--tw-ring-offset-width)) ${project.color}30`,
                        }"
                        class="w-4 h-4 rounded-full"></div>
                </div>
                <div class="col-span-6 sm:col-span-4 flex-1">
                    <TextInput
                        id="projectName"
                        ref="projectNameInput"
                        v-model="project.name"
                        type="text"
                        placeholder="Project Name"
                        class="mt-1 block w-full"
                        required
                        autocomplete="projectName" />
                </div>
                <div class="col-span-6 sm:col-span-4">
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
            <SecondaryButton @click="show = false"> Cancel </SecondaryButton>

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
