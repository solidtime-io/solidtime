<script setup lang="ts">
import TableHeading from '@/Components/Common/TableHeading.vue';
import { ChevronUpIcon, ChevronDownIcon } from '@heroicons/vue/16/solid';
import type { SortColumn, SortDirection } from '@/Components/Common/Member/MemberTable.vue';

const props = defineProps<{
    sortColumn: SortColumn;
    sortDirection: SortDirection;
    descFirstColumns: ReadonlySet<SortColumn>;
}>();

const emit = defineEmits<{
    sort: [column: SortColumn];
}>();

function handleSort(column: SortColumn) {
    emit('sort', column);
}

function isSorted(column: SortColumn): boolean {
    return props.sortColumn === column;
}

function isChevronDown(column: SortColumn): boolean {
    if (!isSorted(column)) return false;
    return props.descFirstColumns.has(column)
        ? props.sortDirection === 'desc'
        : props.sortDirection === 'asc';
}

function isChevronUp(column: SortColumn): boolean {
    if (!isSorted(column)) return false;
    return !isChevronDown(column);
}
</script>

<template>
    <TableHeading>
        <div
            class="py-1.5 pr-3 text-left text-text-tertiary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12 cursor-pointer hover:bg-secondary hover:text-text-primary transition-colors select-none flex items-center gap-1"
            @click="handleSort('name')">
            Name
            <ChevronDownIcon v-if="isChevronDown('name')" class="w-4 h-4" />
            <ChevronUpIcon v-else-if="isChevronUp('name')" class="w-4 h-4" />
            <span v-else class="w-4 h-4"></span>
        </div>
        <div
            class="px-3 py-1.5 text-left text-text-tertiary cursor-pointer hover:bg-secondary hover:text-text-primary transition-colors select-none flex items-center gap-1"
            @click="handleSort('email')">
            Email
            <ChevronDownIcon v-if="isChevronDown('email')" class="w-4 h-4" />
            <ChevronUpIcon v-else-if="isChevronUp('email')" class="w-4 h-4" />
            <span v-else class="w-4 h-4"></span>
        </div>
        <div
            class="px-3 py-1.5 text-left text-text-tertiary cursor-pointer hover:bg-secondary hover:text-text-primary transition-colors select-none flex items-center gap-1"
            @click="handleSort('role')">
            Role
            <ChevronDownIcon v-if="isChevronDown('role')" class="w-4 h-4" />
            <ChevronUpIcon v-else-if="isChevronUp('role')" class="w-4 h-4" />
            <span v-else class="w-4 h-4"></span>
        </div>
        <div
            class="px-3 py-1.5 text-left text-text-tertiary cursor-pointer hover:bg-secondary hover:text-text-primary transition-colors select-none flex items-center gap-1"
            @click="handleSort('billable_rate')">
            Billable Rate
            <ChevronDownIcon v-if="isChevronDown('billable_rate')" class="w-4 h-4" />
            <ChevronUpIcon v-else-if="isChevronUp('billable_rate')" class="w-4 h-4" />
            <span v-else class="w-4 h-4"></span>
        </div>
        <div
            class="px-3 py-1.5 text-left text-text-tertiary cursor-pointer hover:bg-secondary hover:text-text-primary transition-colors select-none flex items-center gap-1"
            @click="handleSort('status')">
            Status
            <ChevronDownIcon v-if="isChevronDown('status')" class="w-4 h-4" />
            <ChevronUpIcon v-else-if="isChevronUp('status')" class="w-4 h-4" />
            <span v-else class="w-4 h-4"></span>
        </div>
        <div class="relative py-1.5 pl-3 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12 bg-row-heading-background">
            <span class="sr-only">Edit</span>
        </div>
    </TableHeading>
</template>
