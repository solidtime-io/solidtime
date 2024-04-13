<script setup lang="ts">
import MainContainer from '@/Pages/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FolderIcon, PlusIcon } from '@heroicons/vue/16/solid';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ref } from 'vue';
import TagTable from '@/Components/Common/Tag/TagTable.vue';
import TagCreateModal from '@/Components/Common/Tag/TagCreateModal.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';

const createTag = ref(false);
</script>

<template>
    <AppLayout title="Tags" data-testid="tags_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-6">
                <PageTitle :icon="FolderIcon" title="Tags"> </PageTitle>
            </div>
            <SecondaryButton :icon="PlusIcon" @click="createTag = true"
                >Create Tag</SecondaryButton
            >
            <TagCreateModal v-model:show="createTag"></TagCreateModal>
        </MainContainer>
        <TagTable></TagTable>
    </AppLayout>
</template>
