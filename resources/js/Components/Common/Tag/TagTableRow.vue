<script setup lang="ts">
import type { Tag } from '@/utils/api';
import { useTagsStore } from '@/utils/useTags';
import TagMoreOptionsDropdown from '@/Components/Common/Tag/TagMoreOptionsDropdown.vue';
import TableRow from '@/Components/TableRow.vue';
import { canDeleteTags } from '@/utils/permissions';

const props = defineProps<{
    tag: Tag;
}>();

function deleteTag() {
    useTagsStore().deleteTag(props.tag.id);
}
</script>

<template>
    <TableRow>
        <div
            class="whitespace-nowrap flex items-center space-x-5 3xl:pl-12 py-4 pr-3 text-sm font-medium text-white pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
            <span>
                {{ tag.name }}
            </span>
        </div>
        <div
            class="relative whitespace-nowrap flex items-center pl-3 text-right text-sm font-medium sm:pr-0 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
            <TagMoreOptionsDropdown
                v-if="canDeleteTags()"
                :tag="tag"
                @delete="deleteTag"></TagMoreOptionsDropdown>
        </div>
    </TableRow>
</template>

<style scoped></style>
