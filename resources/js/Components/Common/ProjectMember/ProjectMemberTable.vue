<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { PlusIcon } from '@heroicons/vue/16/solid';
import { ref } from 'vue';
import ProjectMemberTableRow from '@/Components/Common/ProjectMember/ProjectMemberTableRow.vue';
import { UserGroupIcon } from '@heroicons/vue/24/solid';
import ProjectMemberTableHeading from '@/Components/Common/ProjectMember/ProjectMemberTableHeading.vue';
import ProjectMemberCreateModal from '@/Components/Common/ProjectMember/ProjectMemberCreateModal.vue';
import type { ProjectMember } from '@/packages/api/src';

defineProps<{
    projectId: string;
    projectMembers: ProjectMember[];
}>();

const createProjectMember = ref(false);
</script>

<template>
    <ProjectMemberCreateModal
        v-model:show="createProjectMember"
        :existing-members="projectMembers"
        :project-id="projectId"></ProjectMemberCreateModal>
    <div class="flow-root">
        <div class="inline-block min-w-full align-middle">
            <div
                data-testid="project_member_table"
                class="grid min-w-full"
                style="grid-template-columns: 1fr 150px 150px 80px">
                <ProjectMemberTableHeading></ProjectMemberTableHeading>
                <div
                    v-if="projectMembers.length === 0"
                    class="col-span-5 py-24 text-center">
                    <UserGroupIcon
                        class="w-8 text-icon-default inline pb-2"></UserGroupIcon>
                    <h3 class="text-white font-semibold">No project members</h3>
                    <p class="pb-5">Add the first project member!</p>
                    <SecondaryButton
                        :icon="PlusIcon"
                        @click="createProjectMember = true"
                        >Add a new Project Member
                    </SecondaryButton>
                </div>
                <template
                    v-for="projectMember in projectMembers"
                    :key="projectMember.id">
                    <ProjectMemberTableRow
                        :project-member="projectMember"></ProjectMemberTableRow>
                </template>
            </div>
        </div>
    </div>
</template>
