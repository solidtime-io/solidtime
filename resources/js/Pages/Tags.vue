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

const showCreateTagModal = ref(false);

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
                :createTag="createTag"
                v-model:show="showCreateTagModal"></TagCreateModal>
        </MainContainer>
        <TagTable :createTag="createTag"></TagTable>
    </AppLayout>
</template>
