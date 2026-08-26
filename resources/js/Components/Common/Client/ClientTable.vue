<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { UserCircleIcon } from '@heroicons/vue/24/solid';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { type Component, computed, ref, watch } from 'vue';
import { type Client } from '@/packages/api/src';
import ClientTableRow from '@/Components/Common/Client/ClientTableRow.vue';
import ClientCreateModal from '@/Components/Common/Client/ClientCreateModal.vue';
import ClientTableHeading from '@/Components/Common/Client/ClientTableHeading.vue';
import Pagination from '@/Components/Common/Pagination.vue';
import { canCreateClients } from '@/utils/permissions';
import { useProjectsQuery } from '@/utils/useProjectsQuery';
import {
    useSortableTable,
    type SortableColumnDef,
    type SortDirection,
} from '@/utils/useSortableTable';

export type SortColumn = 'name' | 'projects_count' | 'status';
export type { SortDirection } from '@/utils/useSortableTable';

const props = defineProps<{
    clients: Client[];
    sortColumn: SortColumn;
    sortDirection: SortDirection;
}>();

const emit = defineEmits<{
    sort: [column: SortColumn, direction: SortDirection];
}>();

const createClient = ref(false);

const { projects } = useProjectsQuery();

const projectCountMap = computed(() => {
    const map = new Map<string, number>();
    projects.value.forEach((project) => {
        if (project.client_id) {
            map.set(project.client_id, (map.get(project.client_id) ?? 0) + 1);
        }
    });
    return map;
});

const columns = computed<SortableColumnDef<Client, SortColumn>[]>(() => [
    {
        id: 'name',
        accessorFn: (row: Client) => row.name.toLowerCase(),
    },
    {
        id: 'projects_count',
        sortDescFirst: true,
        accessorFn: (row: Client) => projectCountMap.value.get(row.id) ?? 0,
    },
    {
        id: 'status',
        accessorFn: (row: Client) => (row.is_archived ? 1 : 0),
    },
]);

const {
    sortedRows: sortedClients,
    descFirstColumns,
    nextDirection,
} = useSortableTable({
    data: () => props.clients,
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

watch([() => props.sortColumn, () => props.sortDirection, () => props.clients], () => {
    currentPage.value = 1;
});

const paginatedClients = computed(() => {
    const start = (currentPage.value - 1) * PAGE_SIZE;
    return sortedClients.value.slice(start, start + PAGE_SIZE);
});
</script>

<template>
    <ClientCreateModal v-model:show="createClient"></ClientCreateModal>
    <div class="flow-root max-w-[100vw] overflow-x-auto">
        <div class="inline-block min-w-full align-middle">
            <div
                data-testid="client_table"
                class="grid min-w-full"
                style="grid-template-columns: 1fr 150px 200px 80px">
                <ClientTableHeading
                    :sort-column="props.sortColumn"
                    :sort-direction="props.sortDirection"
                    :desc-first-columns="descFirstColumns"
                    @sort="handleSort"></ClientTableHeading>
                <div v-if="sortedClients.length === 0" class="col-span-3 py-24 text-center">
                    <UserCircleIcon class="w-8 text-icon-default inline pb-2"></UserCircleIcon>
                    <h3 class="text-text-primary font-semibold">No clients found</h3>
                    <p v-if="canCreateClients()" class="pb-5">Create your first client now!</p>
                    <SecondaryButton
                        v-if="canCreateClients()"
                        :icon="PlusIcon as Component"
                        @click="createClient = true"
                        >Create your First Client
                    </SecondaryButton>
                </div>
                <template v-for="client in paginatedClients" :key="client.id">
                    <ClientTableRow :client="client"></ClientTableRow>
                </template>
            </div>
        </div>
    </div>
    <Pagination
        v-model:page="currentPage"
        :total="sortedClients.length"
        :items-per-page="PAGE_SIZE"></Pagination>
</template>
