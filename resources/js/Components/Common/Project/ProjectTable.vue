<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { FolderPlusIcon } from '@heroicons/vue/24/solid';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { ref } from 'vue';
import ProjectCreateModal from '@/packages/ui/src/Project/ProjectCreateModal.vue';
import ProjectTableHeading from '@/Components/Common/Project/ProjectTableHeading.vue';
import ProjectTableRow from '@/Components/Common/Project/ProjectTableRow.vue';
import { canCreateProjects } from '@/utils/permissions';
import type {
    CreateProjectBody,
    Project,
    Client,
    CreateClientBody,
} from '@/packages/api/src';
import { useProjectsStore } from '@/utils/useProjects';
import { useClientsStore } from '@/utils/useClients';
import { storeToRefs } from 'pinia';
import { getOrganizationCurrencyString } from '@/utils/money';

defineProps<{
    projects: Project[];
}>();

const showCreateProjectModal = ref(false);
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
const { clients } = storeToRefs(useClientsStore());
</script>

<template>
    <ProjectCreateModal
        :createProject
        :createClient
        :currency="getOrganizationCurrencyString()"
        :clients="clients"
        v-model:show="showCreateProjectModal"></ProjectCreateModal>
    <div class="flow-root max-w-[100vw] overflow-x-auto">
        <div class="inline-block min-w-full align-middle">
            <div
                data-testid="project_table"
                class="grid min-w-full"
                style="grid-template-columns: 1fr 150px 150px 150px 80px">
                <ProjectTableHeading></ProjectTableHeading>
                <div
                    class="col-span-5 py-24 text-center"
                    v-if="projects.length === 0">
                    <FolderPlusIcon
                        class="w-8 text-icon-default inline pb-2"></FolderPlusIcon>
                    <h3 class="text-white font-semibold">No projects found</h3>
                    <p class="pb-5" v-if="canCreateProjects()">
                        Create your first project now!
                    </p>
                    <SecondaryButton
                        v-if="canCreateProjects()"
                        @click="showCreateProjectModal = true"
                        :icon="PlusIcon"
                        >Create your First Project
                    </SecondaryButton>
                </div>
                <template v-for="project in projects" :key="project.id">
                    <ProjectTableRow :project="project"></ProjectTableRow>
                </template>
            </div>
        </div>
    </div>
</template>
