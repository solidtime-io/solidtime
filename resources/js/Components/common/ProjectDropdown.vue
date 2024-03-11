<script setup lang="ts">
import ProjectBadge from '@/Components/common/ProjectBadge.vue';
import { computed, nextTick, ref, watch } from 'vue';
import { type Project, useProjectsStore } from '@/utils/useProjects';
import Dropdown from '@/Components/Dropdown.vue';
import {
    ComboboxAnchor,
    ComboboxContent,
    ComboboxInput,
    ComboboxItem,
    ComboboxRoot,
    ComboboxViewport,
} from 'radix-vue';
import { PlusCircleIcon } from '@heroicons/vue/20/solid';
import ProjectDropdownItem from '@/Components/common/ProjectDropdownItem.vue';
import { storeToRefs } from 'pinia';
import { api } from '../../../../openapi.json.client';
import { usePage } from '@inertiajs/vue3';
import { getRandomColor } from '@/utils/color';

const searchValue = ref('');
const searchInput = ref<HTMLElement | null>(null);
const model = defineModel<Project | null>({
    default: null,
});
const open = ref(false);
const projectsStore = useProjectsStore();

const { projects } = storeToRefs(projectsStore);
const projectDropdownTrigger = ref<HTMLElement | null>(null);
const shownProjects = computed(() => {
    return projects.value.filter((project) => {
        return project.name
            .toLowerCase()
            .includes(searchValue.value?.toLowerCase()?.trim() || '');
    });
});

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
            },
            { params: { organization: page.props.auth.user.current_team_id } }
        );
        projects.value.unshift(response.data);
        model.value = response.data;
        searchValue.value = '';
        open.value = false;
    }
}

watch(open, (isOpen) => {
    if (isOpen) {
        nextTick(() => {
            searchInput.value?.$el?.focus();
        });

        projects.value.sort((a) => {
            return model.value === a ? -1 : 1;
        });
    }
});

function isProjectSelected(project: Project) {
    return model.value?.id === project.id;
}

const selectedProjectName = computed(() => {
    return model.value?.name || 'No Project';
});

const selectedProjectColor = computed(() => {
    return model.value?.color || 'var(--theme-color-icon-default)';
});
</script>

<template>
    <Dropdown v-model="open" align="right" width="60">
        <template #trigger>
            <ProjectBadge
                ref="projectDropdownTrigger"
                :color="selectedProjectColor"
                size="large"
                tag="button"
                :name="selectedProjectName"
                class="focus:border-input-border-active focus:outline-0 focus:bg-card-background-seperator hover:bg-card-background-seperator"></ProjectBadge>
        </template>

        <template #content>
            <ComboboxRoot
                :open="open"
                v-model="model"
                v-model:searchTerm="searchValue"
                class="relative">
                <ComboboxAnchor>
                    <ComboboxInput
                        @keydown.enter="addProjectIfNoneExists"
                        ref="searchInput"
                        class="bg-card-background border-0 placeholder-muted text-white py-2.5 focus:ring-0 border-b border-card-background-seperator focus:border-card-background-seperator w-full"
                        placeholder="Search for a project..." />
                </ComboboxAnchor>
                <ComboboxContent>
                    <ComboboxViewport ref="dropdownViewport" class="w-60">
                        <ComboboxItem
                            v-if="searchValue === ''"
                            class="data-[highlighted]:bg-card-background-active"
                            :data-project-id="null"
                            :value="{
                                id: null,
                                name: '',
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
                                class="flex space-x-3 items-center px-4 py-3 text-sm font-medium border-t rounded-b-lg border-card-background-seperator">
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
