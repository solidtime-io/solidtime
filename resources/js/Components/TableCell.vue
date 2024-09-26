<script setup lang="ts" generic="T">
import { twMerge } from 'tailwind-merge';
import type { Cell, Header } from '@tanstack/vue-table';

defineProps<{
    cell: Cell<T, unknown> | Header<T, unknown>;
}>();
</script>

<template>
    <div
        :class="
            twMerge(
                'whitespace-nowrap px-3 py-0.5 text-sm text-muted flex space-x-1 items-center font-medium',
                cell.column.getIndex() === 0 &&
                    'pl-4 sm:pl-6 lg:pl-8 3xl:pl-12',
                cell.column.getIndex() ===
                    cell.getContext().table.getAllColumns().length - 1 &&
                    'pr-4 sm:pr-6 lg:pr-8 3xl:pr-12'
            )
        ">
        <slot></slot>
    </div>
</template>

<style scoped></style>
