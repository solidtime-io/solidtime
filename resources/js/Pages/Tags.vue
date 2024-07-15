<script setup lang="ts">
import MainContainer from '@/Pages/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { TagIcon, PlusIcon } from '@heroicons/vue/16/solid';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ref } from 'vue';
import TagTable from '@/Components/Common/Tag/TagTable.vue';
import TagCreateModal from '@/Components/Common/Tag/TagCreateModal.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import { canCreateTags } from '@/utils/permissions';
import { useTagsStore } from '@/utils/useTags';
import type { Tag } from '@/utils/api';
const showCreateTagModal = ref(false);
async function createTag(tag: string, callback: (tag: Tag) => void) {
    const newTag = await useTagsStore().createTag(tag);
    if (newTag !== undefined) {
        callback(newTag);
    }
}
</script>

<template>
    <AppLayout title="Tags" data-testid="tags_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-6">
                <PageTitle :icon="TagIcon" title="Tags"> </PageTitle>
            </div>
            <SecondaryButton
                v-if="canCreateTags()"
                :icon="PlusIcon"
                @click="showCreateTagModal = true"
                >Create Tag</SecondaryButton
            >
            <TagCreateModal
                @createTag="createTag"
                v-model:show="showCreateTagModal"></TagCreateModal>
        </MainContainer>
        <TagTable></TagTable>
    </AppLayout>
</template>
