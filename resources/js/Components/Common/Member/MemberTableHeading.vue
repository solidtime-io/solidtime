<script setup lang="ts">
import TableHeading from '@/Components/Common/TableHeading.vue';
import SortableTableHeaderCell from '@/Components/Common/SortableTableHeaderCell.vue';
import type { SortColumn, SortDirection } from '@/Components/Common/Member/MemberTable.vue';

const props = defineProps<{
    sortColumn: SortColumn;
    sortDirection: SortDirection;
    descFirstColumns: ReadonlySet<SortColumn>;
}>();

defineEmits<{
    sort: [column: SortColumn];
}>();
</script>

<template>
    <TableHeading>
        <SortableTableHeaderCell
            class="pr-3 pl-4 sm:pl-6 lg:pl-8 3xl:pl-12"
            column="name"
            v-bind="props"
            @sort="$emit('sort', $event)">
            Name
        </SortableTableHeaderCell>
        <SortableTableHeaderCell column="email" v-bind="props" @sort="$emit('sort', $event)">
            Email
        </SortableTableHeaderCell>
        <SortableTableHeaderCell column="role" v-bind="props" @sort="$emit('sort', $event)">
            Role
        </SortableTableHeaderCell>
        <SortableTableHeaderCell
            column="billable_rate"
            v-bind="props"
            @sort="$emit('sort', $event)">
            Billable Rate
        </SortableTableHeaderCell>
        <SortableTableHeaderCell column="status" v-bind="props" @sort="$emit('sort', $event)">
            Status
        </SortableTableHeaderCell>
        <div class="relative py-1.5 pl-3 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12 bg-row-heading-background">
            <span class="sr-only">Edit</span>
        </div>
    </TableHeading>
</template>
