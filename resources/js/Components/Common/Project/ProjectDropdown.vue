<script setup lang="ts">
import ProjectBadge from '@/packages/ui/src/Project/ProjectBadge.vue';
import { computed, nextTick, ref, watch } from 'vue';
import { useProjectsStore } from '@/utils/useProjects';
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxInput,
    ComboboxItem,
    ComboboxRoot,
    ComboboxViewport,
} from 'radix-vue';
import { PlusCircleIcon } from '@heroicons/vue/20/solid';
import { storeToRefs } from 'pinia';
import { api } from '@/packages/api/src';
import { usePage } from '@inertiajs/vue3';
import { getRandomColor } from '@/packages/ui/src/utils/color';
import type { Project } from '@/packages/api/src';
import ProjectDropdownItem from '@/packages/ui/src/Project/ProjectDropdownItem.vue';

const searchValue = ref('');
const searchInput = ref<HTMLElement | null>(null);
const model = defineModel<string | null>({
    default: null,
});
const open = ref(false);
const projectsStore = useProjectsStore();
const emit = defineEmits(['update:modelValue', 'changed']);

const { projects } = storeToRefs(projectsStore);
const projectDropdownTrigger = ref<HTMLElement | null>(null);
const shownProjects = computed(() => {
    return projects.value.filter((project) => {
        return project.name
            .toLowerCase()
            .includes(searchValue.value?.toLowerCase()?.trim() || '');
    });
});

withDefaults(
    defineProps<{
        border?: boolean;
    }>(),
    {
        border: true,
    }
);

const page = usePage<{
    auth: {
        user: {
            current_team_id: string;
        };
    };
}>();

async function addProjectIfNoneExists() {
    if (searchValue.value.length > 0 && shownProjects.value.length === 0) {
        const response = await api.createProject(
            {
                name: searchValue.value,
                color: getRandomColor(),
                is_billable: false,
            },
            { params: { organization: page.props.auth.user.current_team_id } }
        );
        projects.value.unshift(response.data);
        model.value = response.data.id;
        searchValue.value = '';
        open.value = false;
    }
}

watch(open, (isOpen) => {
    if (isOpen) {
        nextTick(() => {
            // @ts-expect-error We need to access the actual HTML Element to focus as radix-vue does not support any other way right now
            searchInput.value?.$el?.focus();
        });

        projects.value.sort((iteratingProject) => {
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
    <Dropdown v-model="open" align="bottom-start" width="60">
        <template #trigger>
            <ProjectBadge
                ref="projectDropdownTrigger"
                :color="selectedProjectColor"
                size="large"
                :border
                tag="button"
                :name="selectedProjectName"
                class="focus:border-input-border-active focus:outline-0 focus:bg-card-background-separator hover:bg-card-background-separator"></ProjectBadge>
        </template>

        <template #content>
            <ComboboxRoot
                :open="open"
                :model-value="currentProject"
                :search-term="searchValue"
                class="relative"
                @update:model-value="updateValue"
                @update:search-term="(e) => console.log(e)">
                <ComboboxAnchor>
                    <ComboboxInput
                        ref="searchInput"
                        class="bg-card-background border-0 placeholder-muted text-sm text-white py-2.5 focus:ring-0 border-b border-card-background-separator focus:border-card-background-separator w-full"
                        placeholder="Search for a project..."
                        @keydown.enter="addProjectIfNoneExists" />
                </ComboboxAnchor>
                <ComboboxContent>
                    <ComboboxViewport
                        ref="dropdownViewport"
                        class="w-60 max-h-60 overflow-y-scroll">
                        <ComboboxItem
                            v-if="searchValue === ''"
                            class="data-[highlighted]:bg-card-background-active"
                            :data-project-id="null"
                            :value="{
                                id: null,
                                name: 'No Project',
                                color: 'var(--theme-color-icon-default)',
                            }">
                            <ProjectDropdownItem
                                name="No Project"
                                color="var(--theme-color-icon-default)"
                                selected></ProjectDropdownItem>
                        </ComboboxItem>
                        <ComboboxItem
                            v-for="project in shownProjects"
                            :key="project.id"
                            :value="project"
                            class="data-[highlighted]:bg-card-background-active"
                            :data-project-id="project.id">
                            <ProjectDropdownItem
                                :selected="isProjectSelected(project)"
                                :color="project.color"
                                :name="project.name"></ProjectDropdownItem>
                        </ComboboxItem>
                        <div
                            v-if="
                                searchValue.length > 0 &&
                                shownProjects.length === 0
                            "
                            class="bg-card-background-active">
                            <div
                                class="flex space-x-3 items-center px-4 py-3 text-xs font-medium border-t rounded-b-lg border-card-background-separator">
                                <PlusCircleIcon
                                    class="w-5 flex-shrink-0"></PlusCircleIcon>
                                <span
                                    >Add "{{ searchValue }}" as a new
                                    Project</span
                                >
                            </div>
                        </div>
                    </ComboboxViewport>
                </ComboboxContent>
            </ComboboxRoot>
        </template>
    </Dropdown>
</template>

<style scoped></style>
