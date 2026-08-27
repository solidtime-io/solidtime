<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { FolderPlusIcon } from '@heroicons/vue/24/solid';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { computed, ref, watch } from 'vue';
import ProjectCreateModal from '@/packages/ui/src/Project/ProjectCreateModal.vue';
import ProjectTableHeading from '@/Components/Common/Project/ProjectTableHeading.vue';
import ProjectTableRow from '@/Components/Common/Project/ProjectTableRow.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import LoadingSpinner from '@/packages/ui/src/LoadingSpinner.vue';

export type SortColumn =
    | 'name'
    | 'client_name'
    | 'spent_time'
    | 'progress'
    | 'billable_rate'
    | 'status'
    | 'visibility';
export type { SortDirection } from '@/utils/useSortableTable';
import { canCreateProjects } from '@/utils/permissions';
import type { CreateProjectBody, Project, Client, CreateClientBody } from '@/packages/api/src';
import { useProjectsStore } from '@/utils/useProjects';
import { useClientsStore } from '@/utils/useClients';
import { useClientsQuery } from '@/utils/useClientsQuery';
import { getOrganizationCurrencyString } from '@/utils/money';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import { useOrganizationQuery } from '@/utils/useOrganizationQuery';
import { getCurrentOrganizationId } from '@/utils/useUser';
import {
    useSortableTable,
    type SortableColumnDef,
    type SortDirection,
} from '@/utils/useSortableTable';

const { organization } = useOrganizationQuery(getCurrentOrganizationId()!);

const props = withDefaults(
    defineProps<{
        projects: Project[];
        showBillableRate: boolean;
        sortColumn: SortColumn;
        sortDirection: SortDirection;
        isFiltered?: boolean;
        isLoading?: boolean;
    }>(),
    { isFiltered: false, isLoading: false }
);

const emit = defineEmits<{
    sort: [column: SortColumn, direction: SortDirection];
}>();

const { clients } = useClientsQuery();

// Create a map of client names for sorting
const clientNameMap = computed(() => {
    const map = new Map<string, string>();
    clients.value.forEach((client) => {
        map.set(client.id, client.name);
    });
    return map;
});

// Define column accessors for sorting.
// Numeric columns use sortDescFirst so that the first click (chevron down) sorts highest-first,
// while text columns default to ascending (A-Z) on first click (chevron down).
const columns = computed<SortableColumnDef<Project, SortColumn>[]>(() => [
    {
        id: 'name',
        accessorFn: (row: Project) => row.name.toLowerCase(),
    },
    {
        id: 'client_name',
        accessorFn: (row: Project) => {
            if (!row.client_id) return undefined;
            return (clientNameMap.value.get(row.client_id) ?? '').toLowerCase();
        },
    },
    {
        id: 'spent_time',
        sortDescFirst: true,
        accessorFn: (row: Project) => row.spent_time,
    },
    {
        id: 'progress',
        sortDescFirst: true,
        accessorFn: (row: Project) => {
            if (!row.estimated_time) return undefined;
            return (row.spent_time / row.estimated_time) * 100;
        },
    },
    {
        id: 'billable_rate',
        sortDescFirst: true,
        accessorFn: (row: Project) => row.billable_rate,
    },
    {
        id: 'status',
        accessorFn: (row: Project) => (row.is_archived ? 1 : 0),
    },
    {
        id: 'visibility',
        accessorFn: (row: Project) => (row.is_public ? 1 : 0),
    },
]);

const {
    sortedRows: sortedProjects,
    descFirstColumns,
    nextDirection,
} = useSortableTable({
    data: () => props.projects,
    columns: () => columns.value,
    sortColumn: () => props.sortColumn,
    sortDirection: () => props.sortDirection,
    tieBreakColumn: 'name',
});

function handleSort(column: SortColumn) {
    emit('sort', column, nextDirection(column));
}

// Client-side pagination: the full list is in memory, only one page is mounted at a time.
const PAGE_SIZE = 15;
const currentPage = ref(1);

watch([() => props.sortColumn, () => props.sortDirection, () => props.projects], () => {
    currentPage.value = 1;
});

const paginatedProjects = computed(() => {
    const start = (currentPage.value - 1) * PAGE_SIZE;
    return sortedProjects.value.slice(start, start + PAGE_SIZE);
});

const emptyState = computed(() => {
    if (props.isFiltered) {
        return {
            title: 'No matching projects',
            description: 'Try a different search term or adjust your filters.',
            showCreateButton: false,
        };
    }
    if (!canCreateProjects()) {
        return {
            title: 'You are not a member of any projects',
            description: 'Ask your manager to add you to a project as a team member.',
            showCreateButton: false,
        };
    }
    return {
        title: 'No projects found',
        description: 'Create your first project now!',
        showCreateButton: true,
    };
});

const showCreateProjectModal = ref(false);

async function createProject(project: CreateProjectBody): Promise<Project | undefined> {
    return await useProjectsStore().createProject(project);
}

async function createClient(client: CreateClientBody): Promise<Client | undefined> {
    return await useClientsStore().createClient(client);
}

const gridTemplate = computed(() => {
    return `grid-template-columns: minmax(300px, 1fr) minmax(150px, auto) minmax(140px, auto) minmax(130px, auto) ${props.showBillableRate ? 'minmax(130px, auto)' : ''} minmax(120px, auto) minmax(120px, auto) 80px;`;
});
</script>

<template>
    <ProjectCreateModal
        v-model:show="showCreateProjectModal"
        :create-project
        :create-client
        :currency="getOrganizationCurrencyString()"
        :organization-billable-rate="organization?.billable_rate ?? null"
        :clients="clients"
        :enable-estimated-time="isAllowedToPerformPremiumAction()"></ProjectCreateModal>
    <div class="flow-root max-w-[100vw] overflow-x-auto">
        <div class="inline-block min-w-full align-middle">
            <div data-testid="project_table" class="grid min-w-full" :style="gridTemplate">
                <ProjectTableHeading
                    :show-billable-rate="props.showBillableRate"
                    :sort-column="props.sortColumn"
                    :sort-direction="props.sortDirection"
                    :desc-first-columns="descFirstColumns"
                    @sort="handleSort"></ProjectTableHeading>
                <div
                    v-if="props.isLoading"
                    class="col-span-full flex justify-center items-center py-24">
                    <LoadingSpinner></LoadingSpinner>
                </div>
                <div
                    v-else-if="sortedProjects.length === 0"
                    class="col-span-full py-24 text-center">
                    <FolderPlusIcon class="w-8 text-icon-default inline pb-2"></FolderPlusIcon>
                    <h3 class="text-text-primary font-semibold">{{ emptyState.title }}</h3>
                    <p class="pb-5 max-w-md mx-auto text-sm pt-1">
                        {{ emptyState.description }}
                    </p>
                    <SecondaryButton
                        v-if="emptyState.showCreateButton"
                        :icon="PlusIcon"
                        @click="showCreateProjectModal = true"
                        >Create your First Project
                    </SecondaryButton>
                </div>
                <template v-for="project in paginatedProjects" :key="project.id">
                    <ProjectTableRow
                        :show-billable-rate="props.showBillableRate"
                        :project="project"></ProjectTableRow>
                </template>
            </div>
        </div>
    </div>
    <Pagination
        v-model:page="currentPage"
        :total="sortedProjects.length"
        :items-per-page="PAGE_SIZE"></Pagination>
</template>
