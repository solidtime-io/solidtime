<script setup lang="ts">
import { ChevronRightIcon, ChevronDownIcon } from '@heroicons/vue/16/solid';
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import { computed, nextTick, ref, watch, watchEffect } from 'vue';
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
import {
    PlusIcon,
    PlusCircleIcon,
    MinusIcon,
    XMarkIcon,
} from '@heroicons/vue/16/solid';
import ProjectCreateModal from '@/packages/ui/src/Project/ProjectCreateModal.vue';
import { twMerge } from 'tailwind-merge';

const task = defineModel<string | null>('task', {
    default: null,
});

const project = defineModel<string | null>('project', {
    default: null,
});

const searchInput = ref<HTMLInputElement | null>(null);
const open = ref(false);
const dropdownViewport = ref<HTMLElement | null>(null);

const searchValue = ref('');

watch(open, (isOpen) => {
    if (isOpen) {
        nextTick(() => {
            initializeHighlightedItem();
            searchInput.value?.focus({ preventScroll: true });
        });
    }
});

type ProjectWithTasks = Project & { expanded: boolean; tasks: Task[] };

type ClientWithProjectsWithTasks = Client & { projects: ProjectWithTasks[] };

type ClientsWithProjectsWithTasks = ClientWithProjectsWithTasks[];

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
        emptyPlaceholder: string;
        allowReset: boolean;
        enableEstimatedTime: boolean;
        canCreateProject: boolean;
        class?: string;
    }>(),
    {
        showBadgeBorder: true,
        size: 'large',
        emptyPlaceholder: 'No Project',
        allowReset: false,
    }
);

const filteredResults = ref([] as ClientsWithProjectsWithTasks);

// computed filterProjects that flattens the first layer of filteredResults and combines all the projects
const filteredProjects = computed(() => {
    return filteredResults.value.map((client) => client.projects).flat();
});

function addProjectToFilterObject(
    tempFilteredClients: ClientsWithProjectsWithTasks,
    project: Project,
    filteredTasks: Task[],
    expanded = false
) {
    // check if client already exists in filter array
    const projectClientIndex = tempFilteredClients.findIndex(
        (client) => client.id === project.client_id
    );

    const client = props.clients.find(
        (client) => client.id === project.client_id
    );

    if (projectClientIndex !== -1) {
        // client already exists in filter array
        tempFilteredClients[projectClientIndex].projects.push({
            ...project,
            expanded: expanded,
            tasks: filteredTasks,
        });
    } else if (client) {
        // project has client but is not already in filter array
        // client is not yet in filter array
        tempFilteredClients.push({
            ...client,
            projects: [
                {
                    ...project,
                    expanded: expanded,
                    tasks: filteredTasks,
                },
            ],
        });
    } else {
        // project has no client
        const customNoClientId = 'no_client';
        const noClientIndex = tempFilteredClients.findIndex(
            (client) => client.id === customNoClientId
        );

        if (noClientIndex !== -1) {
            // no client group already exists in filter array
            tempFilteredClients[noClientIndex].projects.push({
                ...project,
                expanded: expanded,
                tasks: filteredTasks,
            });
        } else {
            // no client group is not yet in filter array
            tempFilteredClients.push({
                id: customNoClientId,
                name: 'No Client',
                color: 'var(--theme-color-icon-default)',
                created_at: '',
                updated_at: '',
                value: '',
                is_archived: false,
                projects: [
                    {
                        ...project,
                        expanded: expanded,
                        tasks: filteredTasks,
                    },
                ],
            });
        }
    }
}

watchEffect(() => {
    const tempFilteredClients: ClientsWithProjectsWithTasks = [];

    if (searchValue.value.length === 0) {
        tempFilteredClients.push({
            id: 'no_project_no_client',
            name: 'No Client',
            color: 'var(--theme-color-icon-default)',
            created_at: '',
            updated_at: '',
            value: '',
            is_archived: false,
            projects: [
                {
                    id: '',
                    name: 'No Project',
                    color: 'var(--theme-color-icon-default)',
                    value: '',
                    client_id: null,
                    billable_rate: null,
                    is_archived: false,
                    is_billable: false,
                    expanded: false,
                    tasks: [],
                    estimated_time: null,
                    spent_time: 0,
                },
            ],
        });
    }

    for (const filterProject of props.projects) {
        const projectNameIncludesSearchTerm = filterProject.name
            .toLowerCase()
            .includes(searchValue.value?.toLowerCase()?.trim() || '');

        const clientNameIncludesSearchTerm = props.clients
            .find((client) => client.id === filterProject.client_id)
            ?.name.toLowerCase()
            .includes(searchValue.value?.toLowerCase()?.trim() || '');

        // check if one of the project tasks
        const projectTasks = props.tasks.filter((task) => {
            return task.project_id === filterProject.id;
        });

        const filteredTasks = projectTasks.filter((filterTask) => {
            return (
                filterTask.name
                    .toLowerCase()
                    .includes(searchValue.value?.toLowerCase()?.trim() || '') &&
                (!filterTask.is_done || filterTask.id === task.value)
            );
        });

        if (
            (projectNameIncludesSearchTerm || clientNameIncludesSearchTerm) &&
            (!filterProject.is_archived || project.value === filterProject.id)
        ) {
            // search term matches project name
            addProjectToFilterObject(
                tempFilteredClients,
                filterProject,
                filteredTasks,
                false
            );
        } else if (filteredTasks.length > 0 && !filterProject.is_archived) {
            // search term matches task name
            addProjectToFilterObject(
                tempFilteredClients,
                filterProject,
                filteredTasks,
                true
            );
        }
    }

    // sort tempFilteredClients by client name
    tempFilteredClients.sort((a, b) => {
        // Make sure No Project entry is always on top
        if (a.id === 'no_project_no_client') {
            return -1;
        }
        if (b.id === 'no_project_no_client') {
            return 1;
        }
        // Make sure that No client group is above all regular clients
        if (a.id === 'no_client') {
            return -1;
        }
        if (b.id === 'no_client') {
            return 1;
        }

        if (a.name < b.name) {
            return -1;
        }
        if (a.name > b.name) {
            return 1;
        }
        return 0;
    });

    filteredResults.value = tempFilteredClients;
});

async function addClientIfNoneExists() {
    setProjectAndClientBasedOnHighlightedItem();
}

function isProjectSelected(project: Project) {
    return project.value === project.id;
}

function initializeHighlightedItem() {
    if (filteredProjects.value.length > 0) {
        highlightedItemId.value = filteredProjects.value[0].id;
    }
}

watch(filteredProjects, () => {
    initializeHighlightedItem();
});

function setProjectAndClientBasedOnHighlightedItem() {
    const highlightedProject = filteredProjects.value.find(
        (project) => project.id === highlightedItemId.value
    );
    const highlightedTask = filteredProjects.value
        .map((project) => project.tasks)
        .flat()
        .find((task) => task.id === highlightedItemId.value);
    if (highlightedProject) {
        selectProject(highlightedProject.id);
    }
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
    mouseEnterHighlightActivated.value = false;
    const currentHighlightedIndex = filteredProjects.value.findIndex(
        (projectWithTasks) => projectWithTasks.id === highlightedItemId.value
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
                highlightedItemId.value = currentProjectWithTasks.id;
                return;
            }
            highlightedItemId.value =
                currentProjectWithTasks.tasks[taskIndex - 1].id;
        }
    }
    if (currentHighlightedIndex === 0) {
        // selected project is the first project in the list
        // highlight the last project or the last task of the last project
        const lastProject =
            filteredProjects.value[filteredProjects.value.length - 1];
        if (lastProject.tasks.length > 0 && lastProject.expanded) {
            // highlight last task of last project
            highlightedItemId.value =
                lastProject.tasks[lastProject.tasks.length - 1].id;
        } else {
            highlightedItemId.value =
                filteredProjects.value[filteredProjects.value.length - 1].id;
        }
    } else {
        // selected item is a project that is not the first project in the list
        const previousProject =
            filteredProjects.value[currentHighlightedIndex - 1];
        if (previousProject.tasks.length > 0 && previousProject.expanded) {
            // highlight last task of previous project
            highlightedItemId.value =
                previousProject.tasks[previousProject.tasks.length - 1].id;
        } else {
            highlightedItemId.value =
                filteredProjects.value[currentHighlightedIndex - 1].id;
        }
    }
}

function moveHighlightDown() {
    mouseEnterHighlightActivated.value = false;

    const currentHighlightedIndex = filteredProjects.value.findIndex(
        (projectWithTasks) => projectWithTasks.id === highlightedItemId.value
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
                    highlightedItemId.value = filteredProjects.value[0].id;
                } else {
                    highlightedItemId.value =
                        filteredProjects.value[projectIndex + 1].id;
                }
                return;
            }
            highlightedItemId.value =
                currentProjectWithTasks.tasks[taskIndex + 1].id;
        }
    }
    if (currentHighlightedIndex === filteredProjects.value.length - 1) {
        // selected project is the last project in the list
        // highlight the first project or the last project of the last project
        const lastProject =
            filteredProjects.value[filteredProjects.value.length - 1];
        if (lastProject.tasks.length > 0 && lastProject.expanded) {
            // highlight last task of last project
            highlightedItemId.value = lastProject.tasks[0].id;
        } else {
            highlightedItemId.value = filteredProjects.value[0].id;
        }
    } else {
        // selected item is a project that is not the last project in the list
        const currentProjectWithTasks =
            filteredProjects.value[currentHighlightedIndex];
        if (
            currentProjectWithTasks.tasks.length > 0 &&
            currentProjectWithTasks.expanded
        ) {
            // highlight last task of previous project
            highlightedItemId.value = currentProjectWithTasks.tasks[0].id;
        } else {
            highlightedItemId.value =
                filteredProjects.value[currentHighlightedIndex + 1].id;
        }
    }
}

const highlightedItemId = ref<string | null>(null);

watch(highlightedItemId, () => {
    const highlightedItem = dropdownViewport.value?.querySelector(
        `[data-project-id="${highlightedItemId.value}"]`
    );
    if (highlightedItem) {
        highlightedItem.scrollIntoView({
            block: 'nearest',
            inline: 'nearest',
        });
    } else {
        const highlightedTask = dropdownViewport.value?.querySelector(
            `[data-task-id="${highlightedItemId.value}"]`
        );
        if (highlightedTask) {
            highlightedTask.scrollIntoView({
                block: 'nearest',
                inline: 'nearest',
            });
        }
    }
});

function expandProject() {
    const currentHighlightedIndex = filteredProjects.value.findIndex(
        (projectWithTasks) => projectWithTasks.id === highlightedItemId.value
    );
    if (currentHighlightedIndex === -1) {
        return;
    }
    const currentProject = filteredProjects.value[currentHighlightedIndex];
    currentProject.expanded = true;
}

function collapseProject() {
    const currentHighlightedIndex = filteredProjects.value.findIndex(
        (projectWithTasks) => projectWithTasks.id === highlightedItemId.value
    );
    if (currentHighlightedIndex === -1) {
        return;
    }
    const currentProject = filteredProjects.value[currentHighlightedIndex];
    currentProject.expanded = false;
}

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
    if (project.value === null) {
        return props.emptyPlaceholder;
    }
    if (project.value === '') {
        return 'No Project';
    }
    return currentProject.value?.name;
});

const selectedProjectColor = computed(() => {
    return currentProject.value?.color || 'var(--theme-color-icon-default)';
});

// This state prevents the selection to jump to random items when the mouse cursor is
// over an item and some Item in the Dropdown is selected by keyboard navigation to scroll into view
const mouseEnterHighlightActivated = ref(true);

function setHighlightItemId(newId: string) {
    if (mouseEnterHighlightActivated.value) {
        highlightedItemId.value = newId;
    }
}

function selectTask(taskId: string) {
    task.value = taskId;
    project.value =
        props.tasks.find((task) => task.id === taskId)?.project_id || null;
    open.value = false;
    searchValue.value = '';
    emit('changed', project.value, task.value);
}

function selectProject(projectId: string) {
    project.value = projectId;
    task.value = null;
    open.value = false;
    searchValue.value = '';
    emit('changed', project.value, task.value);
}

const showCreateProject = ref(false);
</script>

<template>
    <div v-if="projects.length === 0 && canCreateProject">
        <Badge
            @click="showCreateProject = true"
            size="large"
            tag="button"
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
                :class="
                    twMerge(
                        'focus:border-border-tertiary w-full focus:outline-0 focus:bg-card-background-separator min-w-0 relative',
                        props.class
                    )
                ">
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
                <button
                    v-if="project !== null && allowReset"
                    @click.stop="
                        project = null;
                        task = null;
                    "
                    class="absolute right-0 top-0 h-full flex items-center pr-3 text-text-quaternary hover:text-text-secondary">
                    <XMarkIcon class="w-5"></XMarkIcon>
                </button>
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
                @keydown.right.prevent="expandProject"
                @keydown.left.prevent="collapseProject"
                ref="searchInput"
                class="bg-card-background border-0 placeholder-muted text-sm text-white py-2.5 focus:ring-0 border-b border-card-background-separator focus:border-card-background-separator w-full"
                placeholder="Search for a project or task..." />
            <div
                ref="dropdownViewport"
                @mousemove="mouseEnterHighlightActivated = true"
                class="min-w-[350px] max-h-[350px] overflow-y-scroll relative">
                <template v-for="client in filteredResults" :key="client.id">
                    <div
                        v-if="client.id !== 'no_project_no_client'"
                        class="w-full pb-1 pt-2 px-2 text-text-tertiary text-xs font-semibold flex space-x-1 items-center">
                        <span>
                            {{ client.name }}
                        </span>
                    </div>
                    <template
                        v-for="projectWithTasks in client.projects"
                        :key="projectWithTasks.id">
                        <div
                            role="option"
                            class="px-1 py-0.5 cursor-default"
                            :value="projectWithTasks.id"
                            @click="selectProject(projectWithTasks.id)"
                            :data-project-id="projectWithTasks.id">
                            <div
                                class="rounded-lg"
                                :class="{
                                    'bg-card-background-active':
                                        projectWithTasks.id ===
                                        highlightedItemId,
                                }">
                                <ProjectDropdownItem
                                    class="hover:!bg-transparent"
                                    :selected="
                                        isProjectSelected(projectWithTasks)
                                    "
                                    @mouseenter="
                                        setHighlightItemId(projectWithTasks.id)
                                    "
                                    :name="projectWithTasks.name"
                                    :color="projectWithTasks.color">
                                    <template #actions>
                                        <button
                                            tabindex="-1"
                                            v-if="
                                                projectWithTasks.tasks.length >
                                                0
                                            "
                                            @click.prevent.stop="
                                                () => {
                                                    projectWithTasks.expanded =
                                                        !projectWithTasks.expanded;
                                                    searchInput?.focus();
                                                }
                                            "
                                            class="px-2 py-0.5 mr-2 relative transition items-center rounded flex space-x-0.5 text-xs"
                                            :class="{
                                                'bg-white/5 text-text-secondary':
                                                    projectWithTasks.expanded,
                                                'hover:bg-white/5 hover:text-text-secondary text-text-tertiary':
                                                    !projectWithTasks.expanded,
                                            }">
                                            <span
                                                >{{
                                                    projectWithTasks.tasks
                                                        .length
                                                }}
                                                Tasks</span
                                            >
                                            <ChevronDownIcon
                                                :class="{
                                                    'transform rotate-180':
                                                        projectWithTasks.expanded,
                                                }"
                                                class="w-4"></ChevronDownIcon>
                                        </button>
                                    </template>
                                </ProjectDropdownItem>
                            </div>
                        </div>
                        <div
                            v-if="projectWithTasks.expanded"
                            class="bg-quaternary">
                            <div
                                v-for="task in projectWithTasks.tasks"
                                :key="task.id"
                                @click="selectTask(task.id)"
                                @mouseenter="setHighlightItemId(task.id)"
                                :data-task-id="task.id"
                                :class="{
                                    'bg-card-background-active':
                                        task.id === highlightedItemId,
                                }"
                                class="flex items-center space-x-2 w-full px-5 py-1.5 text-start text-xs font-semibold leading-5 text-white focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
                                <MinusIcon
                                    class="w-3 h-3 text-text-quaternary"></MinusIcon>
                                <span>{{ task.name }}</span>
                            </div>
                        </div>
                    </template>
                </template>
            </div>
            <div
                v-if="canCreateProject"
                class="hover:bg-card-background-active rounded-b-lg">
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
        :enableEstimatedTime="enableEstimatedTime"
        :currency="currency"
        :clients="clients"
        :createProject
        v-model:show="showCreateProject"></ProjectCreateModal>
</template>

<style scoped></style>
