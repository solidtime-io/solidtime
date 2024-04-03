<script setup lang="ts">
import MainContainer from '@/Pages/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FolderIcon, PlusIcon } from '@heroicons/vue/16/solid';
import TabBarItem from '@/Components/Common/TabBar/TabBarItem.vue';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import TabBar from '@/Components/Common/TabBar/TabBar.vue';
import ProjectTable from '@/Components/Common/Project/ProjectTable.vue';
import { onMounted, ref } from 'vue';
import { useProjectsStore } from '@/utils/useProjects';
import ProjectCreateModal from '@/Components/Common/Project/ProjectCreateModal.vue';

onMounted(() => {
    useProjectsStore().fetchProjects();
});

const createProject = ref(false);
</script>

<template>
    <AppLayout title="Projects" data-testid="projects_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-6">
                <h3
                    class="text-white font-bold text-base flex items-center space-x-2.5">
                    <FolderIcon class="w-6 text-icon-default"></FolderIcon>
                    <span> Projects </span>
                </h3>
                <TabBar>
                    <TabBarItem>All</TabBarItem>
                    <TabBarItem active>Active</TabBarItem>
                    <TabBarItem>Archived</TabBarItem>
                </TabBar>
            </div>
            <SecondaryButton :icon="PlusIcon" @click="createProject = true"
                >Create Project</SecondaryButton
            >
            <ProjectCreateModal
                v-model:show="createProject"></ProjectCreateModal>
        </MainContainer>
        <ProjectTable></ProjectTable>
    </AppLayout>
</template>
