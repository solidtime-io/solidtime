<script setup lang="ts">
import MemberTableHeading from '@/Components/Common/Member/MemberTableHeading.vue';
import MemberTableRow from '@/Components/Common/Member/MemberTableRow.vue';
import { useMembersQuery } from '@/utils/useMembersQuery';
import type { Member } from '@/packages/api/src';
import { computed } from 'vue';
import {
    useVueTable,
    getCoreRowModel,
    getSortedRowModel,
    type SortingState,
} from '@tanstack/vue-table';

export type SortColumn = 'name' | 'email' | 'role' | 'billable_rate' | 'status';
export type SortDirection = 'asc' | 'desc';

const props = defineProps<{
    sortColumn: SortColumn;
    sortDirection: SortDirection;
}>();

const emit = defineEmits<{
    sort: [column: SortColumn, direction: SortDirection];
}>();

const { members } = useMembersQuery();

const roleOrder: Record<string, number> = {
    owner: 0,
    admin: 1,
    manager: 2,
    employee: 3,
    placeholder: 4,
};

const sorting = computed<SortingState>(() => [
    {
        id: props.sortColumn,
        desc: props.sortDirection === 'desc',
    },
]);

const columns = [
    {
        id: 'name',
        accessorFn: (row: Member) => row.name.toLowerCase(),
    },
    {
        id: 'email',
        accessorFn: (row: Member) => row.email.toLowerCase(),
    },
    {
        id: 'role',
        accessorFn: (row: Member) => roleOrder[row.role] ?? 99,
    },
    {
        id: 'billable_rate',
        sortDescFirst: true,
        sortUndefined: 'last' as const,
        accessorFn: (row: Member) => {
            if (row.billable_rate === null) return undefined;
            return row.billable_rate;
        },
    },
    {
        id: 'status',
        accessorFn: (row: Member) => (row.is_placeholder ? 1 : 0),
    },
];

const descFirstColumns = new Set<SortColumn>(
    columns.filter((c) => c.sortDescFirst).map((c) => c.id as SortColumn)
);

function handleSort(column: SortColumn) {
    if (props.sortColumn === column) {
        emit('sort', column, props.sortDirection === 'asc' ? 'desc' : 'asc');
    } else {
        emit('sort', column, descFirstColumns.has(column) ? 'desc' : 'asc');
    }
}

const table = useVueTable({
    get data() {
        return members.value;
    },
    columns,
    getCoreRowModel: getCoreRowModel(),
    getSortedRowModel: getSortedRowModel(),
    state: {
        get sorting() {
            return sorting.value;
        },
    },
    manualSorting: false,
});

const sortedMembers = computed(() => {
    return table.getRowModel().rows.map((row) => row.original);
});
</script>

<template>
    <div class="flow-root max-w-[100vw] overflow-x-auto">
        <div class="inline-block min-w-full align-middle">
            <div
                data-testid="member_table"
                class="grid min-w-full"
                style="grid-template-columns: 1fr 1fr 180px 180px 150px 130px">
                <MemberTableHeading
                    :sort-column="props.sortColumn"
                    :sort-direction="props.sortDirection"
                    :desc-first-columns="descFirstColumns"
                    @sort="handleSort"></MemberTableHeading>
                <template v-for="member in sortedMembers" :key="member.id">
                    <MemberTableRow :member="member"></MemberTableRow>
                </template>
            </div>
        </div>
    </div>
</template>
