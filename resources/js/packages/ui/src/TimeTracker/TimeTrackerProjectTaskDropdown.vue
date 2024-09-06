<script setup lang="ts">
import { ChevronRightIcon } from '@heroicons/vue/16/solid';
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import { type Component, computed, nextTick, ref, watch } from 'vue';
import ProjectDropdownItem from '@/packages/ui/src/Project/ProjectDropdownItem.vue';
import type {
    CreateClientBody,
    CreateProjectBody,
    Project,
    Task,
    Client,
} from '@/packages/api/src';
import ProjectBadge from '@/packages/ui/src/Project/ProjectBadge.vue';
import Badge from '@/packages/ui/src/Badge.vue';
import { PlusIcon, PlusCircleIcon } from '@heroicons/vue/16/solid';
import ProjectCreateModal from '@/packages/ui/src/Project/ProjectCreateModal.vue';

const task = defineModel<string | null>('task', {
    default: null,
});

const project = defineModel<string | null>('project', {
    default: null,
});

const searchInput = ref<HTMLInputElement | null>(null);
const open = ref(false);
const dropdownViewport = ref<Component | null>(null);

const searchValue = ref('');

watch(open, (isOpen) => {
    if (isOpen) {
        nextTick(() => {
            initializeHighlightedItem();
            searchInput.value?.focus({ preventScroll: true });
        });
    }
});

type ProjectWithTasks = {
    project: Project;
    tasks: Task[];
};

const props = withDefaults(
    defineProps<{
        showBadgeBorder: boolean;
        size: 'base' | 'large' | 'xlarge';
        projects: Project[];
        tasks: Task[];
        clients: Client[];
        createProject: (
            project: CreateProjectBody
        ) => Promise<Project | undefined>;
        createClient: (client: CreateClientBody) => Promise<Client | undefined>;
        currency: string;
    }>(),
    {
        showBadgeBorder: true,
        size: 'large',
    }
);

const filteredProjects = computed(() => {
    return props.projects.reduce(
        (filtered: ProjectWithTasks[], filterProject) => {
            const projectNameIncludesSearchTerm = filterProject.name
                .toLowerCase()
                .includes(searchValue.value?.toLowerCase()?.trim() || '');

            // check if one of the project tasks
            const projectTasks = props.tasks.filter((task) => {
                return task.project_id === filterProject.id;
            });

            const filteredTasks = projectTasks.filter((filterTask) => {
                return (
                    filterTask.name
                        .toLowerCase()
                        .includes(
                            searchValue.value?.toLowerCase()?.trim() || ''
                        ) &&
                    (!filterTask.is_done || filterTask.id === task.value)
                );
            });

            if (
                (projectNameIncludesSearchTerm || filteredTasks.length > 0) &&
                (!filterProject.is_archived ||
                    project.value === filterProject.id)
            ) {
                filtered.push({ project: filterProject, tasks: filteredTasks });
            }

            return filtered;
        },
        [
            {
                project: {
                    id: '',
                    name: 'No Project',
                    color: 'var(--theme-color-icon-default)',
                    value: '',
                    client_id: null,
                    billable_rate: null,
                    is_archived: false,
                    is_billable: false,
                },
                tasks: [],
            },
        ]
    );
});

async function addClientIfNoneExists() {
    setProjectAndClientBasedOnHighlightedItem();
}

function isProjectSelected(project: Project) {
    return project.value === project.id;
}

function initializeHighlightedItem() {
    if (filteredProjects.value.length > 0) {
        highlightedItemId.value = filteredProjects.value[0].project.id;
    }
}

watch(filteredProjects, () => {
    initializeHighlightedItem();
});

function setProjectAndClientBasedOnHighlightedItem() {
    const highlightedProject = filteredProjects.value.find(
        (project) => project.project.id === highlightedItemId.value
    );
    if (highlightedProject) {
        selectProject(highlightedProject.project.id);
    }
    const highlightedTask = filteredProjects.value
        .map((project) => project.tasks)
        .flat()
        .find((task) => task.id === highlightedItemId.value);
    if (highlightedTask) {
        selectTask(highlightedTask.id);
    }
}

function updateSearchValue(event: Event) {
    const newInput = (event.target as HTMLInputElement).value;
    if (newInput === ' ') {
        searchValue.value = '';
        setProjectAndClientBasedOnHighlightedItem();
    } else {
        searchValue.value = newInput;
    }
}

const emit = defineEmits(['update:modelValue', 'changed']);

function moveHighlightUp() {
    const currentHighlightedIndex = filteredProjects.value.findIndex(
        (projectWithTasks) =>
            projectWithTasks.project.id === highlightedItemId.value
    );
    // check if it is a project id
    if (currentHighlightedIndex === -1) {
        // the ID is a task ID
        const currentProjectWithTasks = filteredProjects.value.find(
            (projectWithTasks) =>
                projectWithTasks.tasks.some(
                    (task) => task.id === highlightedItemId.value
                )
        );
        if (currentProjectWithTasks) {
            const taskIndex = currentProjectWithTasks.tasks.findIndex(
                (task) => task.id === highlightedItemId.value
            );
            if (taskIndex === -1) {
                return;
            }
            if (taskIndex === 0) {
                // highlight the project if it was the first task before
                highlightedItemId.value = currentProjectWithTasks.project.id;
                return;
            }
            highlightedItemId.value =
                currentProjectWithTasks.tasks[taskIndex - 1].id;
        }
    }
    if (currentHighlightedIndex === 0) {
        // highlight the last project or the last project of the last project
        const lastProject =
            filteredProjects.value[filteredProjects.value.length - 1];
        if (lastProject.tasks.length > 0) {
            // highlight last task of last project
            highlightedItemId.value =
                lastProject.tasks[lastProject.tasks.length - 1].id;
        } else {
            highlightedItemId.value =
                filteredProjects.value[
                    filteredProjects.value.length - 1
                ].project.id;
        }
    } else {
        const previousProject =
            filteredProjects.value[currentHighlightedIndex - 1];
        if (previousProject.tasks.length > 0) {
            // highlight last task of previous project
            highlightedItemId.value =
                previousProject.tasks[previousProject.tasks.length - 1].id;
        } else {
            highlightedItemId.value =
                filteredProjects.value[currentHighlightedIndex - 1].project.id;
        }
    }
}

function moveHighlightDown() {
    const currentHighlightedIndex = filteredProjects.value.findIndex(
        (projectWithTasks) =>
            projectWithTasks.project.id === highlightedItemId.value
    );
    // check if it is a project id
    if (currentHighlightedIndex === -1) {
        // the ID is a task ID
        const currentProjectWithTasks = filteredProjects.value.find(
            (projectWithTasks) =>
                projectWithTasks.tasks.some(
                    (task) => task.id === highlightedItemId.value
                )
        );
        if (currentProjectWithTasks) {
            const taskIndex = currentProjectWithTasks.tasks.findIndex(
                (task) => task.id === highlightedItemId.value
            );
            if (taskIndex === -1) {
                return;
            }
            if (taskIndex === currentProjectWithTasks.tasks.length - 1) {
                // highlight the next project if it was the last task in current project
                const projectIndex = filteredProjects.value.indexOf(
                    currentProjectWithTasks
                );
                if (projectIndex === filteredProjects.value.length - 1) {
                    // highlight the first project if it was the last project
                    highlightedItemId.value =
                        filteredProjects.value[0].project.id;
                } else {
                    highlightedItemId.value =
                        filteredProjects.value[projectIndex + 1].project.id;
                }
                return;
            }
            highlightedItemId.value =
                currentProjectWithTasks.tasks[taskIndex + 1].id;
        }
    }
    if (currentHighlightedIndex === filteredProjects.value.length - 1) {
        // highlight the first project or the last project of the last project
        const lastProject =
            filteredProjects.value[filteredProjects.value.length - 1];
        if (lastProject.tasks.length > 0) {
            // highlight last task of last project
            highlightedItemId.value = lastProject.tasks[0].id;
        } else {
            highlightedItemId.value = filteredProjects.value[0].project.id;
        }
    } else {
        const currentProjectWithTasks =
            filteredProjects.value[currentHighlightedIndex];
        if (currentProjectWithTasks.tasks.length > 0) {
            // highlight last task of previous project
            highlightedItemId.value = currentProjectWithTasks.tasks[0].id;
        } else {
            highlightedItemId.value =
                filteredProjects.value[currentHighlightedIndex + 1].project.id;
        }
    }
}

const highlightedItemId = ref<string | null>(null);

const currentProject = computed(() => {
    return props.projects.find(
        (iteratingProject) => iteratingProject.id === project.value
    );
});

const currentTask = computed(() => {
    return props.tasks.find(
        (iteratingTasks) => iteratingTasks.id === task.value
    );
});

const selectedProjectName = computed(() => {
    return currentProject.value?.name || 'No Project';
});

const selectedProjectColor = computed(() => {
    return currentProject.value?.color || 'var(--theme-color-icon-default)';
});

function selectTask(taskId: string) {
    task.value = taskId;
    project.value =
        props.tasks.find((task) => task.id === taskId)?.project_id || null;
    open.value = false;
    emit('changed', project.value, task.value);
}

function selectProject(projectId: string) {
    project.value = projectId;
    task.value = null;
    open.value = false;
    emit('changed', project.value, task.value);
}

const showCreateProject = ref(false);
</script>

<template>
    <div v-if="projects.length === 0">
        <Badge
            @click="showCreateProject = true"
            size="large"
            class="cursor-pointer hover:bg-tertiary">
            <PlusIcon class="-ml-1 w-5"></PlusIcon>
            <span>Add new project</span>
        </Badge>
    </div>
    <Dropdown v-else v-model="open" :closeOnContentClick="false" align="bottom">
        <template #trigger>
            <ProjectBadge
                ref="projectDropdownTrigger"
                :color="selectedProjectColor"
                :size="size"
                :border="showBadgeBorder"
                tag="button"
                :name="selectedProjectName"
                class="focus:border-border-tertiary w-full focus:outline-0 focus:bg-card-background-separator min-w-0 hover:bg-card-background-separator">
                <div class="flex items-center lg:space-x-1 min-w-0">
                    <span class="whitespace-nowrap text-xs lg:text-sm">
                        {{ selectedProjectName }}
                    </span>
                    <ChevronRightIcon
                        v-if="currentTask"
                        class="w-4 lg:w-5 text-muted shrink-0"></ChevronRightIcon>
                    <div
                        class="min-w-0 shrink text-xs lg:text-sm truncate"
                        v-if="currentTask">
                        {{ currentTask.name }}
                    </div>
                </div>
            </ProjectBadge>
        </template>
        <template #content>
            <input
                :value="searchValue"
                @input="updateSearchValue"
                @keydown.enter="addClientIfNoneExists"
                @click.prevent="searchInput?.focus()"
                data-testid="client_dropdown_search"
                @keydown.up.prevent="moveHighlightUp"
                @keydown.down.prevent="moveHighlightDown"
                ref="searchInput"
                class="bg-card-background border-0 placeholder-muted text-sm text-white py-2.5 focus:ring-0 border-b border-card-background-separator focus:border-card-background-separator w-full"
                placeholder="Search for a project or task..." />
            <div
                ref="dropdownViewport"
                class="min-w-[300px] max-h-[250px] overflow-y-scroll relative">
                <template
                    v-for="projectWithTasks in filteredProjects"
                    :key="projectWithTasks.project.id">
                    <div
                        role="option"
                        :value="projectWithTasks.project.id"
                        @click="selectProject(projectWithTasks.project.id)"
                        class="border-t border-card-background-separator"
                        :class="{
                            'bg-card-background-active':
                                projectWithTasks.project.id ===
                                highlightedItemId,
                        }"
                        data-testid="client_dropdown_entries"
                        :data-project-id="projectWithTasks.project.id">
                        <ProjectDropdownItem
                            :selected="
                                isProjectSelected(projectWithTasks.project)
                            "
                            :name="projectWithTasks.project.name"
                            :color="
                                projectWithTasks.project.color
                            "></ProjectDropdownItem>
                    </div>
                    <div
                        v-for="task in projectWithTasks.tasks"
                        :key="task.id"
                        @click="selectTask(task.id)"
                        :class="{
                            'bg-card-background-active':
                                task.id === highlightedItemId,
                        }"
                        class="flex items-center space-x-3 w-full px-3 py-1.5 text-start text-xs font-semibold leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
                        <div class="w-3 h-3 rounded-full"></div>
                        <span>{{ task.name }}</span>
                    </div>
                </template>
            </div>
            <div class="hover:bg-card-background-active rounded-b-lg">
                <button
                    @click="
                        open = false;
                        showCreateProject = true;
                    "
                    class="text-white flex space-x-3 items-center px-4 py-3 text-xs font-semibold border-t border-card-background-separator">
                    <PlusCircleIcon
                        class="w-5 flex-shrink-0 text-icon-default"></PlusCircleIcon>
                    <span>Create new Project</span>
                </button>
            </div>
        </template>
    </Dropdown>
    <ProjectCreateModal
        :createClient
        :currency="currency"
        :clients="clients"
        :createProject
        v-model:show="showCreateProject"></ProjectCreateModal>
</template>

<style scoped></style>
