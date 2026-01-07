<script setup lang="ts">
import TableHeading from '@/Components/Common/TableHeading.vue';
import { ChevronUpIcon, ChevronDownIcon } from '@heroicons/vue/16/solid';

export type SortColumn = 'name' | 'client_name' | 'spent_time' | 'billable_rate' | 'status';
export type SortDirection = 'asc' | 'desc';

const props = defineProps<{
    showBillableRate: boolean;
    sortColumn: SortColumn;
    sortDirection: SortDirection;
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
</script>

<template>
    <TableHeading>
        <div
            class="py-1.5 pr-3 text-left text-text-tertiary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12 cursor-pointer hover:bg-secondary hover:text-text-primary transition-colors select-none flex items-center gap-1"
            @click="handleSort('name')">
            Name
            <ChevronDownIcon v-if="isSorted('name') && sortDirection === 'asc'" class="w-4 h-4" />
            <ChevronUpIcon
                v-else-if="isSorted('name') && sortDirection === 'desc'"
                class="w-4 h-4" />
            <span v-else class="w-4 h-4"></span>
        </div>
        <div
            class="px-3 py-1.5 text-left text-text-tertiary cursor-pointer hover:bg-secondary hover:text-text-primary transition-colors select-none flex items-center gap-1"
            @click="handleSort('client_name')">
            Client
            <ChevronDownIcon
                v-if="isSorted('client_name') && sortDirection === 'asc'"
                class="w-4 h-4" />
            <ChevronUpIcon
                v-else-if="isSorted('client_name') && sortDirection === 'desc'"
                class="w-4 h-4" />
            <span v-else class="w-4 h-4"></span>
        </div>
        <div
            class="px-3 py-1.5 text-left text-text-tertiary cursor-pointer hover:bg-secondary hover:text-text-primary transition-colors select-none flex items-center gap-1"
            @click="handleSort('spent_time')">
            Total Time
            <ChevronDownIcon
                v-if="isSorted('spent_time') && sortDirection === 'asc'"
                class="w-4 h-4" />
            <ChevronUpIcon
                v-else-if="isSorted('spent_time') && sortDirection === 'desc'"
                class="w-4 h-4" />
            <span v-else class="w-4 h-4"></span>
        </div>
        <div class="px-3 py-1.5 text-left text-text-tertiary">Progress</div>
        <div
            v-if="showBillableRate"
            class="px-3 py-1.5 text-left text-text-tertiary cursor-pointer hover:bg-secondary hover:text-text-primary transition-colors select-none flex items-center gap-1"
            @click="handleSort('billable_rate')">
            Billable Rate
            <ChevronDownIcon
                v-if="isSorted('billable_rate') && sortDirection === 'asc'"
                class="w-4 h-4" />
            <ChevronUpIcon
                v-else-if="isSorted('billable_rate') && sortDirection === 'desc'"
                class="w-4 h-4" />
            <span v-else class="w-4 h-4"></span>
        </div>
        <div
            class="px-3 py-1.5 text-left text-text-tertiary cursor-pointer hover:bg-secondary hover:text-text-primary transition-colors select-none flex items-center gap-1"
            @click="handleSort('status')">
            Status
            <ChevronDownIcon v-if="isSorted('status') && sortDirection === 'asc'" class="w-4 h-4" />
            <ChevronUpIcon
                v-else-if="isSorted('status') && sortDirection === 'desc'"
                class="w-4 h-4" />
            <span v-else class="w-4 h-4"></span>
        </div>
        <div class="relative py-1.5 pl-3 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
            <span class="sr-only">Edit</span>
        </div>
    </TableHeading>
</template>

<style scoped></style>
