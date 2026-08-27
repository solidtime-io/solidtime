<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import { FolderIcon, PlusIcon } from '@heroicons/vue/20/solid';
import { Search } from '@lucide/vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import ProjectTable from '@/Components/Common/Project/ProjectTable.vue';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import { computed, ref } from 'vue';
import { useProjectsQuery } from '@/utils/useProjectsQuery';
import { useProjectsStore } from '@/utils/useProjects';
import ProjectCreateModal from '@/packages/ui/src/Project/ProjectCreateModal.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import { canCreateProjects } from '@/utils/permissions';
import { useClientsQuery } from '@/utils/useClientsQuery';
import { useClientsStore } from '@/utils/useClients';
import type { CreateClientBody, Client, CreateProjectBody, Project } from '@/packages/api/src';
import { getOrganizationCurrencyString } from '@/utils/money';
import { getCurrentOrganizationId, getCurrentRole } from '@/utils/useUser';
import { useOrganizationQuery } from '@/utils/useOrganizationQuery';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import { useStorage } from '@vueuse/core';
import { useTableSortState } from '@/utils/useTableSortState';
import ProjectsFilterDropdown from '@/Components/Common/Project/ProjectsFilterDropdown.vue';
import ProjectStatusFilterBadge from '@/Components/Common/Project/ProjectStatusFilterBadge.vue';
import ProjectVisibilityFilterBadge from '@/Components/Common/Project/ProjectVisibilityFilterBadge.vue';
import ProjectClientFilterBadge from '@/Components/Common/Project/ProjectClientFilterBadge.vue';
import { NO_CLIENT_ID } from '@/Components/Common/Project/constants';
import type { SortColumn, SortDirection } from '@/Components/Common/Project/ProjectTable.vue';

// Fetch data using TanStack Query
const { projects, isLoading: projectsLoading } = useProjectsQuery();
const { clients } = useClientsQuery();
const { organization } = useOrganizationQuery(getCurrentOrganizationId()!);

// Table state persisted in localStorage
interface ProjectTableState {
    sortColumn: SortColumn;
    sortDirection: SortDirection;
    filters: {
        clientIds: string[];
        status: 'active' | 'archived' | 'all';
        visibility: 'public' | 'private' | 'all';
    };
}

const { tableState, handleSort } = useTableSortState<SortColumn, ProjectTableState>(
    'project-table-state',
    {
        sortColumn: 'name',
        sortDirection: 'asc',
        filters: {
            clientIds: [],
            status: 'all',
            visibility: 'all',
        },
    },
    // The filters are merged key by key so a stored value missing a newer filter still
    // picks up its default instead of the whole object falling back.
    (storage, defaults) => ({
        ...defaults,
        ...storage,
        filters: { ...defaults.filters, ...storage.filters },
    })
);

// Not persisted, so a reload never starts silently filtered
const search = ref('');

// Filter projects based on current filters
const filteredProjects = computed(() => {
    const searchTerm = search.value.trim().toLowerCase();

    return projects.value.filter((project) => {
        // Name search
        if (searchTerm && !project.name.toLowerCase().includes(searchTerm)) {
            return false;
        }

        // Status filter
        if (tableState.value.filters.status === 'active' && project.is_archived) {
            return false;
        }
        if (tableState.value.filters.status === 'archived' && !project.is_archived) {
            return false;
        }

        // Visibility filter
        if (tableState.value.filters.visibility === 'public' && !project.is_public) {
            return false;
        }
        if (tableState.value.filters.visibility === 'private' && project.is_public) {
            return false;
        }

        // Client filter
        const hasClientFilter = tableState.value.filters.clientIds.length > 0;
        if (hasClientFilter) {
            const matchesNoClient =
                tableState.value.filters.clientIds.includes(NO_CLIENT_ID) && !project.client_id;
            const matchesClientId =
                project.client_id && tableState.value.filters.clientIds.includes(project.client_id);

            if (!matchesNoClient && !matchesClientId) {
                return false;
            }
        }

        return true;
    });
});

const hasActiveFilters = computed(() => {
    return (
        search.value.trim() !== '' ||
        tableState.value.filters.status !== 'all' ||
        tableState.value.filters.visibility !== 'all' ||
        tableState.value.filters.clientIds.length > 0
    );
});

// Helper functions for active filters
function removeStatusFilter() {
    tableState.value.filters.status = 'all';
}

function removeVisibilityFilter() {
    tableState.value.filters.visibility = 'all';
}

function removeClientFilter() {
    tableState.value.filters.clientIds = [];
}

const showCreateProjectModal = useStorage('project-create-modal-open', false);

async function createProject(project: CreateProjectBody): Promise<Project | undefined> {
    return await useProjectsStore().createProject(project);
}

async function createClient(client: CreateClientBody): Promise<Client | undefined> {
    return await useClientsStore().createClient(client);
}

const showBillableRate = computed(() => {
    return !!(
        getCurrentRole() !== 'employee' || organization.value?.employees_can_see_billable_rates
    );
});
</script>

<template>
    <AppLayout title="Projects" data-testid="projects_view">
        <MainContainer class="py-3 sm:pt-5 flex justify-between items-center">
            <div class="flex items-center space-x-3 sm:space-x-6">
                <PageTitle :icon="FolderIcon" title="Projects"></PageTitle>
            </div>
            <SecondaryButton
                v-if="canCreateProjects()"
                :icon="PlusIcon"
                @click="showCreateProjectModal = true"
                >Create Project
            </SecondaryButton>
            <ProjectCreateModal
                v-model:show="showCreateProjectModal"
                :create-project
                :enable-estimated-time="isAllowedToPerformPremiumAction()"
                :create-client
                :currency="getOrganizationCurrencyString()"
                :organization-billable-rate="organization?.billable_rate ?? null"
                :clients="clients"
                @submit="createProject"></ProjectCreateModal>
        </MainContainer>
        <MainContainer>
            <div class="flex items-center gap-2 py-1">
                <ProjectsFilterDropdown
                    :filters="tableState.filters"
                    :clients="clients"
                    @update:filters="tableState.filters = $event" />

                <!-- Active Filters -->
                <ProjectStatusFilterBadge
                    v-if="tableState.filters.status !== 'all'"
                    data-testid="status-filter-badge"
                    :value="tableState.filters.status"
                    @remove="removeStatusFilter"
                    @update:value="
                        tableState.filters.status = $event as 'active' | 'archived' | 'all'
                    " />

                <ProjectVisibilityFilterBadge
                    v-if="tableState.filters.visibility !== 'all'"
                    data-testid="visibility-filter-badge"
                    :value="tableState.filters.visibility"
                    @remove="removeVisibilityFilter"
                    @update:value="
                        tableState.filters.visibility = $event as 'public' | 'private' | 'all'
                    " />

                <ProjectClientFilterBadge
                    v-if="tableState.filters.clientIds.length > 0"
                    data-testid="client-filter-badge"
                    :value="tableState.filters.clientIds"
                    :clients="clients"
                    @remove="removeClientFilter"
                    @update:value="tableState.filters.clientIds = $event as string[]" />

                <div class="relative">
                    <Search
                        class="pointer-events-none absolute left-2 top-1/2 h-4 w-4 -translate-y-1/2 text-icon-default" />
                    <TextInput
                        v-model="search"
                        size="sm"
                        type="search"
                        aria-label="Search projects"
                        placeholder="Search projects..."
                        class="w-60 border-transparent bg-transparent pl-7 shadow-none placeholder:text-text-tertiary hover:bg-black/5 focus-visible:bg-input-background dark:hover:bg-white/5 [&::-webkit-search-cancel-button]:hidden" />
                </div>
            </div>
        </MainContainer>

        <ProjectTable
            :show-billable-rate="showBillableRate"
            :is-filtered="hasActiveFilters"
            :is-loading="projectsLoading"
            :projects="filteredProjects"
            :sort-column="tableState.sortColumn"
            :sort-direction="tableState.sortDirection"
            @sort="handleSort"></ProjectTable>
    </AppLayout>
</template>
