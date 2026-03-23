<script setup lang="ts">
import type { Tag } from '@/packages/api/src';
import { useTagsStore } from '@/utils/useTags';
import TagMoreOptionsDropdown from '@/Components/Common/Tag/TagMoreOptionsDropdown.vue';
import TagEditModal from '@/Components/Common/Tag/TagEditModal.vue';
import TableRow from '@/Components/TableRow.vue';
import { canDeleteTags, canUpdateTags } from '@/utils/permissions';
import { ref } from 'vue';
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/20/solid';
import {
    ContextMenu,
    ContextMenuContent,
    ContextMenuItem,
    ContextMenuSeparator,
    ContextMenuTrigger,
} from '@/packages/ui/src';

const props = defineProps<{
    tag: Tag;
}>();

const showTagEditModal = ref(false);

function deleteTag() {
    useTagsStore().deleteTag(props.tag.id);
}
</script>

<template>
    <ContextMenu>
        <ContextMenuTrigger as-child>
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
        </ContextMenuTrigger>
        <ContextMenuContent class="min-w-[160px]">
            <ContextMenuItem
                v-if="canUpdateTags()"
                class="space-x-3"
                @select="showTagEditModal = true">
                <PencilSquareIcon class="w-4 h-4 text-icon-default" />
                <span>Edit</span>
            </ContextMenuItem>
            <ContextMenuSeparator v-if="canDeleteTags()" />
            <ContextMenuItem
                v-if="canDeleteTags()"
                class="space-x-3 text-destructive"
                @select="deleteTag()">
                <TrashIcon class="w-4 h-4 text-icon-default" />
                <span>Delete</span>
            </ContextMenuItem>
        </ContextMenuContent>
    </ContextMenu>
</template>

<style scoped></style>
