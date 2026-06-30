<script setup lang="ts">
import { ChevronRightIcon, ChevronDownIcon } from '@heroicons/vue/16/solid';
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import { computed, nextTick, ref, watch } from 'vue';
import { useVirtualizer } from '@tanstack/vue-virtual';
import ProjectDropdownItem from '@/packages/ui/src/Project/ProjectDropdownItem.vue';
import type {
    CreateClientBody,
    CreateProjectBody,
    Project,
    Task,
    Client,
} from '@/packages/api/src';

import { PlusCircleIcon, MinusIcon, XMarkIcon } from '@heroicons/vue/16/solid';
import ProjectCreateModal from '@/packages/ui/src/Project/ProjectCreateModal.vue';
import { twMerge } from 'tailwind-merge';
import { Button } from '@/packages/ui/src/Buttons';

const NO_PROJECT_ID = '';

const task = defineModel<string | null>('task', {
    default: null,
});

const project = defineModel<string | null>('project', {
    default: null,
});

const searchInput = ref<HTMLInputElement | null>(null);
const open = ref(false);
const dropdownViewport = ref<HTMLElement | null>(null);
import { UseFocusTrap } from '@vueuse/integrations/useFocusTrap/component';

const searchValue = ref('');

watch(open, (isOpen) => {
    if (isOpen) {
        updateFilteredResults();
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
        projects: Project[];
        tasks: Task[];
        clients: Client[];
        createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
        createClient: (client: CreateClientBody) => Promise<Client | undefined>;
        currency: string;
        emptyPlaceholder?: string;
        allowReset?: boolean;
        noProjectValue?: string | null;
        enableEstimatedTime: boolean;
        organizationBillableRate: number | null;
        canCreateProject: boolean;
        class?: string;
        variant?: 'input' | 'ghost' | 'outline';
        align?: 'center' | 'end' | 'start';
        size?: 'default' | 'xs' | 'sm' | 'lg' | 'icon';
    }>(),
    {
        emptyPlaceholder: 'No Project',
        allowReset: false,
        noProjectValue: NO_PROJECT_ID,
        variant: 'ghost',
        align: 'center',
        size: 'sm',
    }
);

const filteredResults = ref<ClientsWithProjectsWithTasks>([]);

// computed filterProjects that flattens the first layer of filteredResults and combines all the projects
const filteredProjects = computed<ProjectWithTasks[]>(() => {
    return filteredResults.value.map((client) => client.projects).flat();
});

type FlatRow =
    | { kind: 'client'; key: string; name: string }
    | { kind: 'project'; key: string; project: ProjectWithTasks }
    | { kind: 'task'; key: string; task: Task };

// Flatten the grouped client → project → task tree into a single ordered list so it can be
// virtualized: only the rows currently inside the viewport are mounted, which keeps the
// dropdown responsive even with thousands of projects/tasks.
const flatRows = computed<FlatRow[]>(() => {
    const rows: FlatRow[] = [];
    for (const client of filteredResults.value) {
        // The "No Project" group renders its project inline without a client header.
        if (client.id !== 'no_project_no_client') {
            rows.push({ kind: 'client', key: 'client-' + client.id, name: client.name });
        }
        for (const projectWithTasks of client.projects) {
            rows.push({
                kind: 'project',
                key: 'project-' + projectWithTasks.id,
                project: projectWithTasks,
            });
            if (projectWithTasks.expanded) {
                for (const taskItem of projectWithTasks.tasks) {
                    rows.push({ kind: 'task', key: 'task-' + taskItem.id, task: taskItem });
                }
            }
        }
    }
    return rows;
});

const rowVirtualizer = useVirtualizer(
    computed(() => ({
        count: flatRows.value.length,
        getScrollElement: () => dropdownViewport.value,
        estimateSize: (index: number) => {
            const row = flatRows.value[index];
            if (row?.kind === 'client') return 28;
            if (row?.kind === 'task') return 32;
            return 38;
        },
        getItemKey: (index: number) => flatRows.value[index]?.key ?? index,
        overscan: 12,
    }))
);

const totalSize = computed(() => rowVirtualizer.value.getTotalSize());

const visibleRows = computed(() =>
    rowVirtualizer.value.getVirtualItems().map((virtualRow) => ({
        virtualRow,
        row: flatRows.value[virtualRow.index]!,
    }))
);

function measureRow(el: unknown): void {
    if (el instanceof HTMLElement) {
        rowVirtualizer.value.measureElement(el);
    }
}

// Lookup maps so filtering is O(projects + tasks + clients) instead of
// O(projects × (tasks + clients)). They are rebuilt only when the underlying task/client
// props change, not on every keystroke.
const tasksByProject = computed(() => {
    const map = new Map<string, Task[]>();
    for (const taskItem of props.tasks) {
        const list = map.get(taskItem.project_id);
        if (list) {
            list.push(taskItem);
        } else {
            map.set(taskItem.project_id, [taskItem]);
        }
    }
    return map;
});

const clientsById = computed(() => {
    const map = new Map<string, Client>();
    for (const clientItem of props.clients) {
        map.set(clientItem.id, clientItem);
    }
    return map;
});

function addProjectToFilterObject(
    tempFilteredClients: ClientsWithProjectsWithTasks,
    groupIndexByKey: Map<string, number>,
    project: Project,
    filteredTasks: Task[],
    expanded = false
) {
    const client = project.client_id ? clientsById.value.get(project.client_id) : undefined;
    const groupKey = client ? client.id : 'no_client';
    const newProject: ProjectWithTasks = { ...project, expanded, tasks: filteredTasks };

    // O(1) group lookup instead of scanning the accumulating array for every project.
    const existingIndex = groupIndexByKey.get(groupKey);
    if (existingIndex !== undefined) {
        tempFilteredClients[existingIndex]!.projects.push(newProject);
        return;
    }

    groupIndexByKey.set(groupKey, tempFilteredClients.length);
    if (client) {
        tempFilteredClients.push({ ...client, projects: [newProject] });
    } else {
        tempFilteredClients.push({
            id: 'no_client',
            name: 'No Client',
            color: 'var(--theme-color-icon-default)',
            created_at: '',
            updated_at: '',
            value: '',
            is_archived: false,
            projects: [newProject],
        });
    }
}

function updateFilteredResults() {
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
                    id: NO_PROJECT_ID,
                    name: 'No Project',
                    color: 'var(--theme-color-icon-default)',
                    value: NO_PROJECT_ID,
                    client_id: null,
                    billable_rate: null,
                    is_archived: false,
                    is_billable: false,
                    expanded: false,
                    tasks: [],
                    estimated_time: null,
                    spent_time: 0,
                    is_public: false,
                },
            ],
        });
    }

    const searchTerm = searchValue.value?.toLowerCase()?.trim() || '';
    const groupIndexByKey = new Map<string, number>();

    for (const filterProject of props.projects) {
        const projectNameIncludesSearchTerm = filterProject.name.toLowerCase().includes(searchTerm);

        const clientName = filterProject.client_id
            ? clientsById.value.get(filterProject.client_id)?.name
            : undefined;
        const clientNameIncludesSearchTerm = clientName?.toLowerCase().includes(searchTerm);

        const projectTasks = tasksByProject.value.get(filterProject.id) ?? [];

        // tasks that should be selectable regardless of the search term
        // (open tasks, plus the currently selected one even if it's done)
        const availableTasks = projectTasks.filter((filterTask) => {
            return !filterTask.is_done || filterTask.id === task.value;
        });

        const filteredTasks = availableTasks.filter((filterTask) => {
            return filterTask.name.toLowerCase().includes(searchTerm);
        });

        if (
            (projectNameIncludesSearchTerm || clientNameIncludesSearchTerm) &&
            (!filterProject.is_archived || project.value === filterProject.id)
        ) {
            // search term matches project (or client) name: show all the tasks
            addProjectToFilterObject(
                tempFilteredClients,
                groupIndexByKey,
                filterProject,
                availableTasks,
                false
            );
        } else if (filteredTasks.length > 0 && !filterProject.is_archived) {
            // search term matches task name
            addProjectToFilterObject(
                tempFilteredClients,
                groupIndexByKey,
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
}

// Recompute filtered results when search value changes while open
watch(searchValue, () => {
    if (open.value) {
        updateFilteredResults();
    }
});

async function addClientIfNoneExists() {
    setProjectAndClientBasedOnHighlightedItem();
}

function isProjectSelected(project: Project) {
    return project.value === project.id;
}

function initializeHighlightedItem() {
    if (filteredProjects.value.length > 0) {
        highlightedItemId.value = filteredProjects.value[0]!.id;
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
        const currentProjectWithTasks = filteredProjects.value.find((projectWithTasks) =>
            projectWithTasks.tasks.some((task) => task.id === highlightedItemId.value)
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
            highlightedItemId.value = currentProjectWithTasks.tasks[taskIndex - 1]!.id;
        }
    }
    if (currentHighlightedIndex === 0) {
        // selected project is the first project in the list
        // highlight the last project or the last task of the last project
        const lastProject = filteredProjects.value[filteredProjects.value.length - 1]!;
        if (lastProject.tasks.length > 0 && lastProject.expanded) {
            // highlight last task of last project
            highlightedItemId.value = lastProject.tasks[lastProject.tasks.length - 1]!.id;
        } else {
            highlightedItemId.value = filteredProjects.value[filteredProjects.value.length - 1]!.id;
        }
    } else {
        // selected item is a project that is not the first project in the list
        const previousProject = filteredProjects.value[currentHighlightedIndex - 1]!;
        if (previousProject.tasks.length > 0 && previousProject.expanded) {
            // highlight last task of previous project
            highlightedItemId.value = previousProject.tasks[previousProject.tasks.length - 1]!.id;
        } else {
            highlightedItemId.value = filteredProjects.value[currentHighlightedIndex - 1]!.id;
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
        const currentProjectWithTasks = filteredProjects.value.find((projectWithTasks) =>
            projectWithTasks.tasks.some((task) => task.id === highlightedItemId.value)
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
                const projectIndex = filteredProjects.value.indexOf(currentProjectWithTasks);
                if (projectIndex === filteredProjects.value.length - 1) {
                    // highlight the first project if it was the last project
                    highlightedItemId.value = filteredProjects.value[0]!.id;
                } else {
                    highlightedItemId.value = filteredProjects.value[projectIndex + 1]!.id;
                }
                return;
            }
            highlightedItemId.value = currentProjectWithTasks.tasks[taskIndex + 1]!.id;
        }
    }
    if (currentHighlightedIndex === filteredProjects.value.length - 1) {
        // selected project is the last project in the list
        // highlight the first project or the last project of the last project
        const lastProject = filteredProjects.value[filteredProjects.value.length - 1]!;
        if (lastProject.tasks.length > 0 && lastProject.expanded) {
            // highlight last task of last project
            highlightedItemId.value = lastProject.tasks[0]!.id;
        } else {
            highlightedItemId.value = filteredProjects.value[0]!.id;
        }
    } else {
        // selected item is a project that is not the last project in the list
        const currentProjectWithTasks = filteredProjects.value[currentHighlightedIndex]!;
        if (currentProjectWithTasks.tasks.length > 0 && currentProjectWithTasks.expanded) {
            // highlight last task of previous project
            highlightedItemId.value = currentProjectWithTasks.tasks[0]!.id;
        } else {
            highlightedItemId.value = filteredProjects.value[currentHighlightedIndex + 1]!.id;
        }
    }
}

const highlightedItemId = ref<string | null>(null);

watch(highlightedItemId, () => {
    if (highlightedItemId.value === null) {
        return;
    }
    // The highlighted row may be virtualized out of the DOM, so scroll by index
    // through the virtualizer instead of querying for the element.
    const index = flatRows.value.findIndex(
        (row) =>
            (row.kind === 'project' && row.project.id === highlightedItemId.value) ||
            (row.kind === 'task' && row.task.id === highlightedItemId.value)
    );
    if (index !== -1) {
        rowVirtualizer.value.scrollToIndex(index, { align: 'auto' });
    }
});

function expandProject() {
    const currentHighlightedIndex = filteredProjects.value.findIndex(
        (projectWithTasks) => projectWithTasks.id === highlightedItemId.value
    );
    if (currentHighlightedIndex === -1) {
        return;
    }
    const currentProject = filteredProjects.value[currentHighlightedIndex]!;
    currentProject.expanded = true;
}

function collapseProject() {
    const currentHighlightedIndex = filteredProjects.value.findIndex(
        (projectWithTasks) => projectWithTasks.id === highlightedItemId.value
    );
    if (currentHighlightedIndex === -1) {
        return;
    }
    const currentProject = filteredProjects.value[currentHighlightedIndex]!;
    currentProject.expanded = false;
}

const currentProject = computed(() => {
    return props.projects.find((iteratingProject) => iteratingProject.id === project.value);
});

const currentTask = computed(() => {
    return props.tasks.find((iteratingTasks) => iteratingTasks.id === task.value);
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
    project.value = props.tasks.find((task) => task.id === taskId)?.project_id || null;
    open.value = false;
    searchValue.value = '';
    emit('changed', project.value, task.value);
}

function selectProject(projectId: string) {
    project.value = projectId === NO_PROJECT_ID ? props.noProjectValue : projectId;
    task.value = null;
    open.value = false;
    searchValue.value = '';
    emit('changed', project.value, task.value);
}

function resetProject() {
    project.value = null;
    task.value = null;
    emit('changed', project.value, task.value);
}

const showCreateProject = ref(false);
</script>

<template>
    <Dropdown v-model="open" :close-on-content-click="false" :align="props.align">
        <template #trigger>
            <slot name="trigger">
                <div class="flex items-center gap-1">
                    <Button
                        :variant="props.variant"
                        :size="props.size"
                        :class="twMerge('w-full justify-start overflow-hidden', props.class)">
                        <div
                            class="w-3 h-3 rounded-full shrink-0"
                            :style="{ backgroundColor: selectedProjectColor }"></div>
                        <span class="truncate shrink-[1] text-text-primary pr-1">{{
                            selectedProjectName
                        }}</span>
                        <template v-if="currentTask">
                            <ChevronRightIcon class="w-4 h-4 text-text-tertiary shrink-0" />
                            <span class="truncate shrink-[100]">{{ currentTask.name }}</span>
                        </template>
                    </Button>
                    <button
                        v-if="allowReset && project !== null"
                        type="button"
                        data-testid="project_reset_button"
                        class="p-1 rounded hover:bg-quaternary text-text-tertiary hover:text-text-primary"
                        @click.stop="resetProject">
                        <XMarkIcon class="w-4 h-4" />
                    </button>
                </div>
            </slot>
        </template>
        <template #content>
            <UseFocusTrap v-if="open" :options="{ immediate: true, allowOutsideClick: true }">
                <input
                    ref="searchInput"
                    :value="searchValue"
                    data-testid="client_dropdown_search"
                    class="bg-card-background border-0 placeholder-text-tertiary text-sm text-text-primary py-2.5 focus:ring-0 border-b border-card-background-separator focus:border-card-background-separator w-full"
                    placeholder="Search for a project or task..."
                    @input="updateSearchValue"
                    @keydown.enter.prevent="addClientIfNoneExists"
                    @keydown.esc.prevent="open = false"
                    @keydown.up.prevent="moveHighlightUp"
                    @keydown.down.prevent="moveHighlightDown"
                    @keydown.right.prevent="expandProject"
                    @keydown.left.prevent="collapseProject" />
                <div
                    ref="dropdownViewport"
                    class="w-[400px] max-w-[calc(100vw-2rem)] max-h-[350px] overflow-y-scroll relative"
                    @mousemove="mouseEnterHighlightActivated = true">
                    <div :style="{ height: `${totalSize}px`, width: '100%', position: 'relative' }">
                        <div
                            v-for="{ virtualRow, row } in visibleRows"
                            :key="row.key"
                            :ref="measureRow"
                            :data-index="virtualRow.index"
                            class="absolute left-0 top-0 w-full"
                            :style="{ transform: `translateY(${virtualRow.start}px)` }">
                            <div
                                v-if="row.kind === 'client'"
                                class="w-full pb-1 pt-2 px-2 text-text-tertiary text-xs font-semibold flex space-x-1 items-center">
                                <span class="truncate">{{ row.name }}</span>
                            </div>
                            <div
                                v-else-if="row.kind === 'project'"
                                role="option"
                                class="px-1 py-0.5 cursor-default"
                                :value="row.project.id"
                                :data-project-id="row.project.id"
                                @click="selectProject(row.project.id)">
                                <div
                                    class="rounded-lg"
                                    :class="{
                                        'bg-card-background-active':
                                            row.project.id === highlightedItemId,
                                    }">
                                    <ProjectDropdownItem
                                        class="hover:!bg-transparent"
                                        :selected="isProjectSelected(row.project)"
                                        :name="row.project.name"
                                        :color="row.project.color"
                                        @mouseenter="setHighlightItemId(row.project.id)">
                                        <template #actions>
                                            <button
                                                v-if="row.project.tasks.length > 0"
                                                tabindex="-1"
                                                class="px-2 py-0.5 mr-2 relative transition items-center rounded flex space-x-0.5 text-xs shrink-0"
                                                :class="{
                                                    'bg-white/5 text-text-secondary':
                                                        row.project.expanded,
                                                    'hover:bg-white/5 hover:text-text-secondary text-text-tertiary':
                                                        !row.project.expanded,
                                                }"
                                                @click.prevent.stop="
                                                    () => {
                                                        row.project.expanded =
                                                            !row.project.expanded;
                                                        searchInput?.focus();
                                                    }
                                                ">
                                                <span class="whitespace-nowrap"
                                                    >{{ row.project.tasks.length }} Tasks</span
                                                >
                                                <ChevronDownIcon
                                                    :class="{
                                                        'transform rotate-180':
                                                            row.project.expanded,
                                                    }"
                                                    class="w-4"></ChevronDownIcon>
                                            </button>
                                        </template>
                                    </ProjectDropdownItem>
                                </div>
                            </div>
                            <div
                                v-else-if="row.kind === 'task'"
                                :data-task-id="row.task.id"
                                class="flex items-center space-x-2 w-full px-5 py-1.5 text-start text-xs font-semibold leading-5 text-text-primary focus:outline-none transition duration-150 ease-in-out"
                                :class="
                                    row.task.id === highlightedItemId
                                        ? 'bg-card-background-active'
                                        : 'bg-quaternary'
                                "
                                @click="selectTask(row.task.id)"
                                @mouseenter="setHighlightItemId(row.task.id)">
                                <MinusIcon
                                    class="w-3 h-3 text-text-quaternary shrink-0"></MinusIcon>
                                <span class="min-w-0 truncate">{{ row.task.name }}</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div v-if="canCreateProject" class="hover:bg-card-background-active rounded-b-lg">
                    <button
                        class="text-text-primary w-full flex space-x-3 items-center px-4 py-3 text-xs font-semibold border-t border-card-background-separator"
                        @click="
                            open = false;
                            showCreateProject = true;
                        ">
                        <PlusCircleIcon
                            class="w-5 flex-shrink-0 text-icon-default"></PlusCircleIcon>
                        <span>Create new Project</span>
                    </button>
                </div>
            </UseFocusTrap>
        </template>
    </Dropdown>
    <ProjectCreateModal
        v-if="showCreateProject"
        v-model:show="showCreateProject"
        :create-client
        :enable-estimated-time="enableEstimatedTime"
        :organization-billable-rate="organizationBillableRate"
        :currency="currency"
        :clients="clients"
        :create-project
        :initial-project-name="searchValue"></ProjectCreateModal>
</template>

<style scoped></style>
