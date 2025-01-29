<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { FolderPlusIcon } from '@heroicons/vue/24/solid';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { computed, ref } from 'vue';
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

const props = defineProps<{
    projects: Project[];
    showBillableRate: boolean;
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
const gridTemplate = computed(() => {
    return `grid-template-columns: minmax(300px, 1fr) minmax(150px, auto) minmax(140px, auto) minmax(130px, auto) ${props.showBillableRate ? 'minmax(130px, auto)' : ''} minmax(120px, auto) 80px;`;
});
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
</script>

<template>
    <ProjectCreateModal
        v-model:show="showCreateProjectModal"
        :create-project
        :create-client
        :currency="getOrganizationCurrencyString()"
        :clients="clients"
        :enable-estimated-time="isAllowedToPerformPremiumAction"></ProjectCreateModal>
    <div class="flow-root max-w-[100vw] overflow-x-auto">
        <div class="inline-block min-w-full align-middle">
            <div
                data-testid="project_table"
                class="grid min-w-full"
                :style="gridTemplate">
                <ProjectTableHeading
                    :show-billable-rate="
                        props.showBillableRate
                    "></ProjectTableHeading>
                <div
                    v-if="projects.length === 0"
                    class="col-span-5 py-24 text-center">
                    <FolderPlusIcon
                        class="w-8 text-icon-default inline pb-2"></FolderPlusIcon>
                    <h3 class="text-white font-semibold">
                        {{
                            canCreateProjects()
                                ? 'No projects found'
                                : 'You are not a member of any projects'
                        }}
                    </h3>
                    <p class="pb-5 max-w-md mx-auto text-sm pt-1">
                        {{
                            canCreateProjects()
                                ? 'Create your first project now!'
                                : 'Ask your manager to add you to a project as a team member.'
                        }}
                    </p>
                    <SecondaryButton
                        v-if="canCreateProjects()"
                        :icon="PlusIcon"
                        @click="showCreateProjectModal = true"
                        >Create your First Project
                    </SecondaryButton>
                </div>
                <template v-for="project in projects" :key="project.id">
                    <ProjectTableRow
                        :show-billable-rate="props.showBillableRate"
                        :project="project"></ProjectTableRow>
                </template>
            </div>
        </div>
    </div>
</template>
