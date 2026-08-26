<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { FolderPlusIcon } from '@heroicons/vue/24/solid';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { ref } from 'vue';
import { useTagsQuery } from '@/utils/useTagsQuery';
import TagTableRow from '@/Components/Common/Tag/TagTableRow.vue';
import TagCreateModal from '@/packages/ui/src/Tag/TagCreateModal.vue';
import TagTableHeading from '@/Components/Common/Tag/TagTableHeading.vue';
import { canCreateTags } from '@/utils/permissions';
import type { Tag } from '@/packages/api/src';
import {
    useSortableTable,
    type SortableColumnDef,
    type SortDirection,
} from '@/utils/useSortableTable';

export type SortColumn = 'name';
export type { SortDirection } from '@/utils/useSortableTable';

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

const columns: SortableColumnDef<Tag, SortColumn>[] = [
    {
        id: 'name',
        accessorFn: (row: Tag) => row.name.toLowerCase(),
    },
];

const {
    sortedRows: sortedTags,
    descFirstColumns,
    nextDirection,
} = useSortableTable({
    data: () => tags.value,
    columns: () => columns,
    sortColumn: () => props.sortColumn,
    sortDirection: () => props.sortDirection,
});

function handleSort(column: SortColumn) {
    emit('sort', column, nextDirection(column));
}
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
