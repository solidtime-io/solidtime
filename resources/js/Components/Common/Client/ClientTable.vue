<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { UserCircleIcon } from '@heroicons/vue/24/solid';
import {
    ChevronUpDownIcon,
    ChevronDownIcon,
    ChevronUpIcon,
    PlusIcon,
} from '@heroicons/vue/16/solid';
import { type Component, computed, h, ref, watchEffect } from 'vue';
import { type Client } from '@/packages/api/src';
import ClientCreateModal from '@/Components/Common/Client/ClientCreateModal.vue';
import { canCreateClients } from '@/utils/permissions';

const props = defineProps<{
    clients: Client[];
}>();
const createClient = ref(false);

import {
    FlexRender,
    getCoreRowModel,
    useVueTable,
    createColumnHelper,
    type SortingState,
    getSortedRowModel,
} from '@tanstack/vue-table';
import { storeToRefs } from 'pinia';
import { useProjectsStore } from '@/utils/useProjects';
import { CheckCircleIcon } from '@heroicons/vue/20/solid';
import TableHeading from '@/Components/Common/TableHeading.vue';
import ClientMoreOptionsDropdown from '@/Components/Common/Client/ClientMoreOptionsDropdown.vue';
import { useClientsStore } from '@/utils/useClients';
import ClientEditModal from '@/Components/Common/Client/ClientEditModal.vue';
import TableRow from '@/Components/TableRow.vue';
import TableCell from '@/Components/TableCell.vue';

const columnHelper = createColumnHelper<Client>();
const { projects } = storeToRefs(useProjectsStore());

const columns = computed(() => [
    columnHelper.accessor((row) => row.name, {
        id: 'name',
        cell: (info) => info.getValue(),
        header: () => 'Name',
    }),
    columnHelper.accessor((row) => row, {
        id: 'projects',
        sortingFn: (a, b) => {
            return (
                projects.value.filter(
                    (projects) => projects.client_id === a.original.id
                ).length -
                projects.value.filter(
                    (projects) => projects.client_id === b.original.id
                ).length
            );
        },
        cell: (info) =>
            h('div', {
                innerHTML:
                    projects.value.filter(
                        (projects) => projects.client_id === info.getValue().id
                    ).length + ' Projects',
            }),
        header: () => 'Projects',
    }),
    columnHelper.accessor((row) => row, {
        id: 'status',
        enableSorting: false,
        cell: (info) =>
            h(
                'div',
                {
                    class: 'flex space-x-1 items-center',
                },
                [
                    h(CheckCircleIcon, {
                        class: 'w-5',
                    }),
                    h('span', {
                        innerHTML: info.getValue().is_archived
                            ? 'Archived'
                            : 'Active',
                    }),
                ]
            ),
        header: () => 'Status',
    }),
    columnHelper.display({
        id: 'actions',
        cell: (info) => {
            const showEditModal = ref(false);
            return h(
                'div',
                {
                    class: 'flex space-x-1 items-center',
                },
                [
                    h(ClientEditModal, {
                        client: info.row.original,
                        show: showEditModal.value,
                    }),
                    h(ClientMoreOptionsDropdown, {
                        class: 'w-5',
                        client: info.row.original,
                        onEdit: () => (showEditModal.value = true),
                        onArchive: () => {
                            useClientsStore().updateClient(
                                info.row.original.id,
                                {
                                    ...info.row.original,
                                    is_archived: !info.row.original.is_archived,
                                }
                            );
                        },
                        onDelete: () => {
                            useClientsStore().deleteClient(
                                info.row.original.id
                            );
                        },
                    }),
                ]
            );
        },
    }),
]);

const data = ref(props.clients);

watchEffect(() => {
    data.value = props.clients;
});

const table = useVueTable({
    get data() {
        return data.value;
    },
    onSortingChange: (updaterOrValue) => {
        sorting.value =
            typeof updaterOrValue === 'function'
                ? updaterOrValue(sorting.value)
                : updaterOrValue;
    },
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    state: {
        get sorting() {
            return sorting.value;
        },
    },
    columns: columns.value,
});
const sorting = ref<SortingState>([]);
</script>

<template>
    <ClientCreateModal v-model:show="createClient"></ClientCreateModal>
    <div class="flow-root max-w-[100vw] overflow-x-auto">
        <div class="inline-block min-w-full align-middle">
            <div
                data-testid="client_table"
                class="grid min-w-full"
                style="grid-template-columns: 1fr 150px 200px 80px">
                <TableHeading>
                    <TableCell
                        v-for="header in table.getHeaderGroups()[0].headers"
                        :key="header.id"
                        :class="
                            header.column.getCanSort()
                                ? 'cursor-pointer select-none'
                                : ''
                        "
                        @click="
                            header.column.getToggleSortingHandler()?.($event)
                        "
                        :cell="header">
                        <FlexRender
                            v-if="!header.isPlaceholder"
                            :render="header.column.columnDef.header"
                            :props="header.getContext()" />
                        <div class="px-1" v-if="header.column.getCanSort()">
                            <ChevronUpDownIcon
                                class="h-4 text-text-tertiary"
                                v-if="
                                    header.column.getIsSorted() === false
                                "></ChevronUpDownIcon>
                            <ChevronDownIcon
                                class="h-4 text-accent-300"
                                v-if="
                                    header.column.getIsSorted() === 'desc'
                                "></ChevronDownIcon>
                            <ChevronUpIcon
                                class="h-4 text-accent-300"
                                v-if="
                                    header.column.getIsSorted() === 'asc'
                                "></ChevronUpIcon>
                        </div>
                    </TableCell>
                </TableHeading>

                <div
                    class="col-span-2 py-24 text-center"
                    v-if="clients.length === 0">
                    <UserCircleIcon
                        class="w-8 text-icon-default inline pb-2"></UserCircleIcon>
                    <h3 class="text-white font-semibold">No clients found</h3>
                    <p class="pb-5" v-if="canCreateClients()">
                        Create your first client now!
                    </p>
                    <SecondaryButton
                        v-if="canCreateClients()"
                        @click="createClient = true"
                        :icon="PlusIcon as Component"
                        >Create your First Client
                    </SecondaryButton>
                </div>
                <TableRow v-for="row in table.getRowModel().rows" :key="row.id">
                    <TableCell
                        v-for="cell in row.getVisibleCells()"
                        :key="cell.id"
                        :cell="cell">
                        <FlexRender
                            :render="cell.column.columnDef.cell"
                            :props="cell.getContext()" />
                    </TableCell>
                </TableRow>
            </div>
        </div>
    </div>
</template>
