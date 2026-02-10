<script setup lang="ts">
import type { Tag } from '@/packages/api/src';
import { useTagsStore } from '@/utils/useTags';
import TagMoreOptionsDropdown from '@/Components/Common/Tag/TagMoreOptionsDropdown.vue';
import TagEditModal from '@/Components/Common/Tag/TagEditModal.vue';
import TableRow from '@/Components/TableRow.vue';
import { canDeleteTags, canUpdateTags } from '@/utils/permissions';
import { ref } from 'vue';

const props = defineProps<{
    tag: Tag;
}>();

const showTagEditModal = ref(false);

function deleteTag() {
    useTagsStore().deleteTag(props.tag.id);
}
</script>

<template>
    <TableRow>
        <div
            class="whitespace-nowrap flex items-center space-x-5 3xl:pl-12 py-4 pr-3 text-sm font-medium text-text-primary pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
            <span>
                {{ tag.name }}
            </span>
        </div>
        <div
            class="relative whitespace-nowrap flex items-center pl-3 text-right text-sm font-medium sm:pr-0 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
            <TagMoreOptionsDropdown
                v-if="canDeleteTags() || canUpdateTags()"
                :tag="tag"
                @edit="showTagEditModal = true"
                @delete="deleteTag"></TagMoreOptionsDropdown>
        </div>
        <TagEditModal v-model:show="showTagEditModal" :tag="tag"></TagEditModal>
    </TableRow>
</template>

<style scoped></style>
