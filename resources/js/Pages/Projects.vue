<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FolderIcon, PlusIcon } from '@heroicons/vue/16/solid';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import ProjectTable from '@/Components/Common/Project/ProjectTable.vue';
import { computed, onMounted, ref } from 'vue';
import { useProjectsStore } from '@/utils/useProjects';
import ProjectCreateModal from '@/packages/ui/src/Project/ProjectCreateModal.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import { canCreateProjects } from '@/utils/permissions';
import TabBarItem from '@/Components/Common/TabBar/TabBarItem.vue';
import TabBar from '@/Components/Common/TabBar/TabBar.vue';
import { storeToRefs } from 'pinia';
import { useClientsStore } from '@/utils/useClients';
import type {
    CreateClientBody,
    Client,
    CreateProjectBody,
    Project,
} from '@/packages/api/src';
import { getOrganizationCurrencyString } from '@/utils/money';
import { getCurrentRole } from '@/utils/useUser';
import { useOrganizationStore } from '@/utils/useOrganization';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';

onMounted(() => {
    useProjectsStore().fetchProjects();
    useOrganizationStore().fetchOrganization();
});
const { clients } = storeToRefs(useClientsStore());
const showCreateProjectModal = ref(false);

const { organization } = storeToRefs(useOrganizationStore());

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
async function createProject(
    project: CreateProjectBody
): Promise<Project | undefined> {
    return await useProjectsStore().createProject(project);
}
async function createClient(
    client: CreateClientBody
): Promise<Client | undefined> {
    return await useClientsStore().createClient(client);
}

const showBillableRate = computed(() => {
    return !!(
        getCurrentRole() !== 'employee' ||
        organization.value?.employees_can_see_billable_rates
    );
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
                @click="showCreateProjectModal = true"
                >Create Project
            </SecondaryButton>
            <ProjectCreateModal
                :createProject
                :enableEstimatedTime="isAllowedToPerformPremiumAction"
                :createClient
                :currency="getOrganizationCurrencyString()"
                :clients="clients"
                @submit="createProject"
                v-model:show="showCreateProjectModal"></ProjectCreateModal>
        </MainContainer>
        <ProjectTable
            :show-billable-rate="showBillableRate"
            :projects="shownProjects"></ProjectTable>
    </AppLayout>
</template>
