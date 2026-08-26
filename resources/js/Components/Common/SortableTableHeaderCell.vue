<script setup lang="ts" generic="TColumn extends string">
import { computed, useAttrs } from 'vue';
import type { ClassValue } from 'clsx';
import { cn } from '@/lib/utils';
import { ChevronUpIcon, ChevronDownIcon } from '@heroicons/vue/16/solid';
import type { SortDirection } from '@/utils/useSortableTable';

defineOptions({ inheritAttrs: false });

const props = defineProps<{
    column: TColumn;
    sortColumn: TColumn;
    sortDirection: SortDirection;
    descFirstColumns: ReadonlySet<TColumn>;
}>();

const emit = defineEmits<{
    sort: [column: TColumn];
}>();

const attrs = useAttrs();

const isSorted = computed(() => props.sortColumn === props.column);

const isChevronDown = computed(() => {
    if (!isSorted.value) return false;
    return props.descFirstColumns.has(props.column)
        ? props.sortDirection === 'desc'
        : props.sortDirection === 'asc';
});

const cellClass = computed(() =>
    cn(
        'px-3 py-1.5 text-left text-text-tertiary cursor-pointer hover:bg-secondary hover:text-text-primary transition-colors select-none flex items-center gap-1',
        attrs.class as ClassValue
    )
);
</script>

<template>
    <button type="button" :class="cellClass" @click="emit('sort', column)">
        <slot></slot>
        <span class="sr-only">
            {{
                isSorted
                    ? `sorted ${sortDirection === 'asc' ? 'ascending' : 'descending'}`
                    : 'not sorted'
            }}
        </span>
        <ChevronDownIcon v-if="isChevronDown" aria-hidden="true" class="w-4 h-4" />
        <ChevronUpIcon v-else-if="isSorted" aria-hidden="true" class="w-4 h-4" />
        <span v-else aria-hidden="true" class="w-4 h-4"></span>
    </button>
</template>
