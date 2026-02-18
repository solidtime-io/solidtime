<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { TagIcon, PlusIcon } from '@heroicons/vue/16/solid';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { ref } from 'vue';
import TagTable from '@/Components/Common/Tag/TagTable.vue';
import TagCreateModal from '@/packages/ui/src/Tag/TagCreateModal.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import { canCreateTags } from '@/utils/permissions';
import { useTagsStore } from '@/utils/useTags';
import { useStorage } from '@vueuse/core';
import type { SortColumn, SortDirection } from '@/Components/Common/Tag/TagTable.vue';

const showCreateTagModal = ref(false);

interface TagTableState {
    sortColumn: SortColumn;
    sortDirection: SortDirection;
}

const tableState = useStorage<TagTableState>(
    'tag-table-state',
    {
        sortColumn: 'name',
        sortDirection: 'asc',
    },
    undefined,
    { mergeDefaults: true }
);

function handleSort(column: SortColumn, direction: SortDirection) {
    tableState.value.sortColumn = column;
    tableState.value.sortDirection = direction;
}

async function createTag(tag: string) {
    return await useTagsStore().createTag(tag);
}
</script>

<template>
    <AppLayout title="Tags" data-testid="tags_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-6">
                <PageTitle :icon="TagIcon" title="Tags"></PageTitle>
            </div>
            <SecondaryButton
                v-if="canCreateTags()"
                :icon="PlusIcon"
                @click="showCreateTagModal = true"
                >Create Tag
            </SecondaryButton>
            <TagCreateModal
                v-model:show="showCreateTagModal"
                :create-tag="createTag"></TagCreateModal>
        </MainContainer>
        <TagTable
            :create-tag="createTag"
            :sort-column="tableState.sortColumn"
            :sort-direction="tableState.sortDirection"
            @sort="handleSort"></TagTable>
    </AppLayout>
</template>
