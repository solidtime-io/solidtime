<script setup lang="ts">
import SecondaryButton from '@/Components/SecondaryButton.vue';
import { FolderPlusIcon } from '@heroicons/vue/24/solid';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { ref } from 'vue';
import ProjectCreateModal from '@/Components/Common/Project/ProjectCreateModal.vue';
import ProjectTableHeading from '@/Components/Common/Project/ProjectTableHeading.vue';
import ProjectTableRow from '@/Components/Common/Project/ProjectTableRow.vue';
import { canCreateProjects } from '@/utils/permissions';
import type { Project } from '@/utils/api';

defineProps<{
    projects: Project[];
}>();

const createProject = ref(false);
</script>

<template>
    <ProjectCreateModal v-model:show="createProject"></ProjectCreateModal>
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
                        @click="createProject = true"
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
