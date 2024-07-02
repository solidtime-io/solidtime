<script setup lang="ts">
import MainContainer from '@/Pages/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FolderIcon, PlusIcon } from '@heroicons/vue/16/solid';
import SecondaryButton from '@/Components/SecondaryButton.vue';
import ProjectTable from '@/Components/Common/Project/ProjectTable.vue';
import { computed, onMounted, ref } from 'vue';
import { useProjectsStore } from '@/utils/useProjects';
import ProjectCreateModal from '@/Components/Common/Project/ProjectCreateModal.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import { canCreateProjects } from '@/utils/permissions';
import TabBarItem from '@/Components/Common/TabBar/TabBarItem.vue';
import TabBar from '@/Components/Common/TabBar/TabBar.vue';
import { storeToRefs } from 'pinia';

onMounted(() => {
    useProjectsStore().fetchProjects();
});

const createProject = ref(false);

const activeTab = ref<'active' | 'archived'>('active');

function isActiveTab(tab: string) {
    return activeTab.value === tab;
}

const { projects } = storeToRefs(useProjectsStore());

const shownProjects = computed(() => {
    return projects.value.filter((project) => {
        if (activeTab.value === 'active') {
            return !project.is_archived;
        }
        return project.is_archived;
    });
});
</script>

<template>
    <AppLayout title="Projects" data-testid="projects_view">
        <MainContainer
            class="py-3 sm:py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-3 sm:space-x-6">
                <PageTitle :icon="FolderIcon" title="Projects"></PageTitle>
                <TabBar>
                    <TabBarItem
                        :active="isActiveTab('active')"
                        @click="activeTab = 'active'"
                        >Active</TabBarItem
                    >
                    <TabBarItem
                        :active="isActiveTab('archived')"
                        @click="activeTab = 'archived'">
                        Archived
                    </TabBarItem>
                </TabBar>
            </div>
            <SecondaryButton
                v-if="canCreateProjects()"
                :icon="PlusIcon"
                @click="createProject = true"
                >Create Project
            </SecondaryButton>
            <ProjectCreateModal
                v-model:show="createProject"></ProjectCreateModal>
        </MainContainer>
        <ProjectTable :projects="shownProjects"></ProjectTable>
    </AppLayout>
</template>
