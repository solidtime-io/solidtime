<script setup lang="ts">
import {
    PaginationEllipsis,
    PaginationFirst,
    PaginationLast,
    PaginationList,
    PaginationListItem,
    PaginationNext,
    PaginationPrev,
    PaginationRoot,
} from 'radix-vue';
import {
    ChevronDoubleLeftIcon,
    ChevronDoubleRightIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
    EllipsisHorizontalIcon,
} from '@heroicons/vue/20/solid';
import { buttonVariants } from '@/packages/ui/src';
import { cn } from '@/lib/utils';
import { computed, watch } from 'vue';

const page = defineModel<number>('page', { default: 1 });

const props = withDefaults(
    defineProps<{
        total: number;
        itemsPerPage?: number;
        siblingCount?: number;
        showEdges?: boolean;
    }>(),
    {
        itemsPerPage: 15,
        siblingCount: 1,
        showEdges: true,
    }
);

const pageCount = computed(() => Math.max(1, Math.ceil(props.total / props.itemsPerPage)));

watch(page, (value) => {
    if (value > pageCount.value) {
        page.value = pageCount.value;
    }
});

watch(pageCount, (value) => {
    if (page.value > value) {
        page.value = value;
    }
});

// The shared buttonVariants ghost/outline hover is `bg-white/5`, which is invisible in light
// mode. Override it with a theme-aware hover that shows in both light and dark mode.
const hoverClass = 'hover:bg-black/5 dark:hover:bg-white/5';
const navButtonClass = cn(buttonVariants({ variant: 'ghost', size: 'icon' }), hoverClass);

function pageButtonClass(isActive: boolean): string {
    return cn(
        buttonVariants({ variant: isActive ? 'outline' : 'ghost', size: 'icon' }),
        hoverClass
    );
}
</script>

<template>
    <PaginationRoot
        v-if="pageCount > 1"
        v-model:page="page"
        :total="props.total"
        :items-per-page="props.itemsPerPage"
        :sibling-count="props.siblingCount"
        :show-edges="props.showEdges"
        class="mx-auto flex w-full justify-center py-8">
        <PaginationList v-slot="{ items }" class="flex items-center gap-1">
            <PaginationFirst :class="navButtonClass">
                <ChevronDoubleLeftIcon class="size-4" />
            </PaginationFirst>
            <PaginationPrev :class="navButtonClass">
                <ChevronLeftIcon class="size-4" />
            </PaginationPrev>
            <template v-for="(item, index) in items" :key="index">
                <PaginationListItem
                    v-if="item.type === 'page'"
                    :value="item.value"
                    :class="pageButtonClass(item.value === page)">
                    {{ item.value }}
                </PaginationListItem>
                <PaginationEllipsis
                    v-else
                    :index="index"
                    class="flex size-9 items-center justify-center text-text-tertiary">
                    <EllipsisHorizontalIcon class="size-4" />
                </PaginationEllipsis>
            </template>
            <PaginationNext :class="navButtonClass">
                <ChevronRightIcon class="size-4" />
            </PaginationNext>
            <PaginationLast :class="navButtonClass">
                <ChevronDoubleRightIcon class="size-4" />
            </PaginationLast>
        </PaginationList>
    </PaginationRoot>
</template>
