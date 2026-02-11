<script setup lang="ts">
import { computed, nextTick, ref, watch } from 'vue';
import { useProjectsQuery } from '@/utils/useProjectsQuery';
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxInput,
    ComboboxItem,
    ComboboxRoot,
    ComboboxViewport,
} from 'radix-vue';
import { Check, Plus } from 'lucide-vue-next';
import type { CreateClientBody, CreateProjectBody, Project } from '@/packages/api/src';
import { UseFocusTrap } from '@vueuse/integrations/useFocusTrap/component';
import ProjectCreateModal from '@/packages/ui/src/Project/ProjectCreateModal.vue';
import { useProjectsStore } from '@/utils/useProjects';
import { useClientsStore } from '@/utils/useClients';
import { useClientsQuery } from '@/utils/useClientsQuery';
import { getOrganizationCurrencyString } from '@/utils/money';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import { canCreateProjects } from '@/utils/permissions';

const searchValue = ref('');
const searchInput = ref<HTMLElement | null>(null);
const model = defineModel<string | null>({
    default: null,
});
const open = ref(false);
const showCreateProject = ref(false);
const { projects } = useProjectsQuery();
const { clients } = useClientsQuery();
const emit = defineEmits(['update:modelValue', 'changed']);

const activeClients = computed(() => clients.value.filter((c) => !c.is_archived));

const sortedProjects = ref<Project[]>([]);

const shownProjects = computed(() => {
    return sortedProjects.value.filter((project) => {
        return project.name.toLowerCase().includes(searchValue.value?.toLowerCase()?.trim() || '');
    });
});

async function handleCreateProject(projectBody: CreateProjectBody) {
    const newProject = await useProjectsStore().createProject(projectBody);
    if (newProject) {
        model.value = newProject.id;
        emit('changed');
    }
    return newProject;
}

async function handleCreateClient(clientBody: CreateClientBody) {
    return await useClientsStore().createClient(clientBody);
}

watch(open, (isOpen) => {
    if (isOpen) {
        nextTick(() => {
            // @ts-expect-error We need to access the actual HTML Element to focus as radix-vue does not support any other way right now
            searchInput.value?.$el?.focus();
        });

        sortedProjects.value = [...projects.value].sort((iteratingProject) => {
            return model.value === iteratingProject.id ? -1 : 1;
        });
    }
});

const currentProject = computed(() => {
    return projects.value.find((project) => project.id === model.value);
});

function isProjectSelected(project: Project) {
    return model.value === project.id;
}

const selectedProjectName = computed(() => {
    return currentProject.value?.name || 'No Project';
});

const selectedProjectColor = computed(() => {
    return currentProject.value?.color || 'var(--theme-color-icon-default)';
});

function updateValue(project: Project) {
    model.value = project.id;
    emit('changed');
}
</script>

<template>
    <Dropdown v-model="open" align="start">
        <template #trigger>
            <slot
                name="trigger"
                :selected-project-name="selectedProjectName"
                :selected-project-color="selectedProjectColor"></slot>
        </template>

        <template #content>
            <UseFocusTrap v-if="open" :options="{ immediate: true, allowOutsideClick: true }">
                <ComboboxRoot
                    v-model:search-term="searchValue"
                    v-model:open="open"
                    :model-value="currentProject"
                    class="relative"
                    @update:model-value="updateValue">
                    <ComboboxAnchor>
                        <ComboboxInput
                            ref="searchInput"
                            class="bg-transparent border-0 placeholder-muted-foreground text-sm text-popover-foreground py-2 px-3 focus:ring-0 border-b border-popover-border focus:border-popover-border w-full"
                            placeholder="Search for a project..." />
                    </ComboboxAnchor>
                    <ComboboxContent>
                        <ComboboxViewport
                            class="w-[--reka-popper-anchor-width] max-h-60 overflow-y-scroll p-1">
                            <ComboboxItem
                                v-for="project in shownProjects"
                                :key="project.id"
                                :value="project"
                                class="relative flex w-full cursor-default select-none items-center rounded-sm py-1.5 pl-2 pr-8 text-sm outline-none data-[highlighted]:bg-accent data-[highlighted]:text-accent-foreground"
                                :data-project-id="project.id">
                                <span class="flex items-center gap-2">
                                    <span
                                        :style="{ backgroundColor: project.color }"
                                        class="w-3 h-3 rounded-full shrink-0"></span>
                                    <span>{{ project.name }}</span>
                                </span>
                                <span
                                    v-if="isProjectSelected(project)"
                                    class="absolute right-2 flex h-3.5 w-3.5 items-center justify-center">
                                    <Check class="h-4 w-4" />
                                </span>
                            </ComboboxItem>
                        </ComboboxViewport>
                        <div
                            v-if="canCreateProjects()"
                            class="flex items-center gap-2 px-3 py-2 text-sm cursor-pointer hover:bg-accent hover:text-accent-foreground border-t border-popover-border"
                            @click="
                                open = false;
                                showCreateProject = true;
                            ">
                            <Plus class="h-4 w-4 shrink-0" />
                            <span>Create new Project</span>
                        </div>
                    </ComboboxContent>
                </ComboboxRoot>
            </UseFocusTrap>
        </template>
    </Dropdown>
    <ProjectCreateModal
        v-model:show="showCreateProject"
        :create-project="handleCreateProject"
        :create-client="handleCreateClient"
        :clients="activeClients"
        :currency="getOrganizationCurrencyString()"
        :enable-estimated-time="isAllowedToPerformPremiumAction()" />
</template>

<style scoped></style>
