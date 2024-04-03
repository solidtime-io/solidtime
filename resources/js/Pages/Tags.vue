<script setup lang="ts">
import MainContainer from '@/Pages/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FolderIcon, PlusIcon } from '@heroicons/vue/16/solid';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ref } from 'vue';
import TagTable from '@/Components/Common/Tag/TagTable.vue';
import TagCreateModal from '@/Components/Common/Tag/TagCreateModal.vue';

const createTag = ref(false);
</script>

<template>
    <AppLayout title="Tags" data-testid="tags_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-6">
                <h3
                    class="text-white font-bold text-base flex items-center space-x-2.5">
                    <FolderIcon class="w-6 text-icon-default"></FolderIcon>
                    <span> Tags </span>
                </h3>
            </div>
            <SecondaryButton :icon="PlusIcon" @click="createTag = true"
                >Create Tag</SecondaryButton
            >
            <TagCreateModal v-model:show="createTag"></TagCreateModal>
        </MainContainer>
        <TagTable></TagTable>
    </AppLayout>
</template>
