<script setup lang="ts">
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { FolderPlusIcon } from '@heroicons/vue/24/solid';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { ref } from 'vue';
import { storeToRefs } from 'pinia';
import { useTagsStore } from '@/utils/useTags';
import TagTableRow from '@/Components/Common/Tag/TagTableRow.vue';
import TagCreateModal from '@/Components/Common/Tag/TagCreateModal.vue';
import TagTableHeading from '@/Components/Common/Tag/TagTableHeading.vue';
import { canCreateTags } from '@/utils/permissions';

const { tags } = storeToRefs(useTagsStore());
const createTag = ref(false);
</script>

<template>
    <TagCreateModal v-model:show="createTag"></TagCreateModal>
    <div class="flow-root">
        <div class="inline-block min-w-full align-middle">
            <div
                data-testid="tag_table"
                class="grid min-w-full"
                style="grid-template-columns: 1fr 80px">
                <TagTableHeading></TagTableHeading>
                <div
                    class="col-span-5 py-24 text-center"
                    v-if="tags.length === 0">
                    <FolderPlusIcon
                        class="w-8 text-icon-default inline pb-2"></FolderPlusIcon>
                    <h3 class="text-white font-semibold">No tags found</h3>
                    <p class="pb-5" v-if="canCreateTags()">
                        Create your first tag now!
                    </p>
                    <SecondaryButton
                        v-if="canCreateTags()"
                        @click="createTag = true"
                        :icon="PlusIcon"
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
