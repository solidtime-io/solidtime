<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { FolderPlusIcon } from '@heroicons/vue/24/solid';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { computed, ref } from 'vue';
import { useTagsQuery } from '@/utils/useTagsQuery';
import TagTableRow from '@/Components/Common/Tag/TagTableRow.vue';
import TagCreateModal from '@/packages/ui/src/Tag/TagCreateModal.vue';
import TagTableHeading from '@/Components/Common/Tag/TagTableHeading.vue';
import { canCreateTags } from '@/utils/permissions';
import type { Tag } from '@/packages/api/src';
import {
    useVueTable,
    getCoreRowModel,
    getSortedRowModel,
    type SortingState,
} from '@tanstack/vue-table';

export type SortColumn = 'name';
export type SortDirection = 'asc' | 'desc';

const props = defineProps<{
    createTag: (name: string) => Promise<Tag | undefined>;
    sortColumn: SortColumn;
    sortDirection: SortDirection;
}>();

const emit = defineEmits<{
    sort: [column: SortColumn, direction: SortDirection];
}>();

const { tags } = useTagsQuery();
const showCreateTagModal = ref(false);

const sorting = computed<SortingState>(() => [
    {
        id: props.sortColumn,
        desc: props.sortDirection === 'desc',
    },
]);

const columns = [
    {
        id: 'name',
        accessorFn: (row: Tag) => row.name.toLowerCase(),
    },
];

const descFirstColumns = new Set<SortColumn>(
    columns.filter((c) => 'sortDescFirst' in c && c.sortDescFirst).map((c) => c.id as SortColumn)
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
        return tags.value;
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

const sortedTags = computed(() => {
    return table.getRowModel().rows.map((row) => row.original);
});
</script>

<template>
    <TagCreateModal v-model:show="showCreateTagModal" :create-tag></TagCreateModal>
    <div class="flow-root">
        <div class="inline-block min-w-full align-middle">
            <div
                data-testid="tag_table"
                class="grid min-w-full"
                style="grid-template-columns: 1fr 80px">
                <TagTableHeading
                    :sort-column="props.sortColumn"
                    :sort-direction="props.sortDirection"
                    :desc-first-columns="descFirstColumns"
                    @sort="handleSort"></TagTableHeading>
                <div v-if="sortedTags.length === 0" class="col-span-5 py-24 text-center">
                    <FolderPlusIcon class="w-8 text-icon-default inline pb-2"></FolderPlusIcon>
                    <h3 class="text-text-primary font-semibold">No tags found</h3>
                    <p v-if="canCreateTags()" class="pb-5">Create your first tag now!</p>
                    <SecondaryButton
                        v-if="canCreateTags()"
                        :icon="PlusIcon"
                        @click="showCreateTagModal = true"
                        >Create your First Tag</SecondaryButton
                    >
                </div>
                <template v-for="tag in sortedTags" :key="tag.id">
                    <TagTableRow :tag="tag"></TagTableRow>
                </template>
            </div>
        </div>
    </div>
</template>
