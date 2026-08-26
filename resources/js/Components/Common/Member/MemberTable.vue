<script setup lang="ts">
import MemberTableHeading from '@/Components/Common/Member/MemberTableHeading.vue';
import MemberTableRow from '@/Components/Common/Member/MemberTableRow.vue';
import { useMembersQuery } from '@/utils/useMembersQuery';
import type { Member } from '@/packages/api/src';
import {
    useSortableTable,
    type SortableColumnDef,
    type SortDirection,
} from '@/utils/useSortableTable';

export type SortColumn = 'name' | 'email' | 'role' | 'billable_rate' | 'status';
export type { SortDirection } from '@/utils/useSortableTable';

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

const columns: SortableColumnDef<Member, SortColumn>[] = [
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

const {
    sortedRows: sortedMembers,
    descFirstColumns,
    nextDirection,
} = useSortableTable({
    data: () => members.value,
    columns: () => columns,
    sortColumn: () => props.sortColumn,
    sortDirection: () => props.sortDirection,
    tieBreakColumn: 'name',
});

function handleSort(column: SortColumn) {
    emit('sort', column, nextDirection(column));
}
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
