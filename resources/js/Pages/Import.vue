<script setup lang="ts">
import MainContainer from '@/Pages/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { PlusIcon, ArrowsRightLeftIcon } from '@heroicons/vue/16/solid';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { ref } from 'vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import { canCreateTags } from '@/utils/permissions';
import ImportData from '@/Pages/Teams/Partials/ImportData.vue';
const createTag = ref(false);
</script>

<template>
    <AppLayout title="Import" data-testid="import_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-6">
                <PageTitle :icon="ArrowsRightLeftIcon" title="Import">
                </PageTitle>
            </div>
            <SecondaryButton
                v-if="canCreateTags()"
                :icon="PlusIcon"
                @click="createTag = true"
                >Create Tag</SecondaryButton
            >
        </MainContainer>
        <MainContainer class="py-6">
            <ImportData></ImportData>
        </MainContainer>
    </AppLayout>
</template>
