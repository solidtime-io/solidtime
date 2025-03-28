<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { FolderPlusIcon } from '@heroicons/vue/24/solid';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useTagsStore } from '@/utils/useTags';
import TagTableRow from '@/Components/Common/Tag/TagTableRow.vue';
import TagCreateModal from '@/packages/ui/src/Tag/TagCreateModal.vue';
import TagTableHeading from '@/Components/Common/Tag/TagTableHeading.vue';
import { canCreateTags } from '@/utils/permissions';
import type { Tag } from '@/packages/api/src';
defineProps<{
    createTag: (name: string) => Promise<Tag | undefined>;
}>();
const { tags } = storeToRefs(useTagsStore());
const showCreateTagModal = ref(false);
</script>

<template>
    <TagCreateModal
        v-model:show="showCreateTagModal"
        :create-tag></TagCreateModal>
    <div class="flow-root">
        <div class="inline-block min-w-full align-middle">
            <div
                data-testid="tag_table"
                class="grid min-w-full"
                style="grid-template-columns: 1fr 80px">
                <TagTableHeading></TagTableHeading>
                <div
                    v-if="tags.length === 0"
                    class="col-span-5 py-24 text-center">
                    <FolderPlusIcon
                        class="w-8 text-icon-default inline pb-2"></FolderPlusIcon>
                    <h3 class="text-text-primary font-semibold">No tags found</h3>
                    <p v-if="canCreateTags()" class="pb-5">
                        Create your first tag now!
                    </p>
                    <SecondaryButton
                        v-if="canCreateTags()"
                        :icon="PlusIcon"
                        @click="showCreateTagModal = true"
                        >Create your First Tag</SecondaryButton
                    >
                </div>
                <template v-for="tag in tags" :key="tag.id">
                    <TagTableRow :tag="tag"></TagTableRow>
                </template>
            </div>
        </div>
    </div>
</template>
