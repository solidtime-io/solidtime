<script setup lang="ts">
import type { ProjectMember } from '@/utils/api';
import { computed } from 'vue';
import { storeToRefs } from 'pinia';
import TableRow from '@/Components/TableRow.vue';
import { useMembersStore } from '@/utils/useMembers';
import { useProjectMembersStore } from '@/utils/useProjectMembers';
import ProjectMemberMoreOptionsDropdown from '@/Components/Common/ProjectMember/ProjectMemberMoreOptionsDropdown.vue';
import { formatCents } from '@/utils/money';
import { capitalizeFirstLetter } from '@/utils/format';

const props = defineProps<{
    projectMember: ProjectMember;
}>();
function deleteProjectMember() {
    useProjectMembersStore().deleteProjectMember(
        props.projectMember.project_id,
        props.projectMember.id
    );
}

const { members } = storeToRefs(useMembersStore());
const member = computed(() => {
    return members.value.find(
        (member) => member.id === props.projectMember.user_id
    );
});
</script>

<template>
    <TableRow>
        <div
            class="whitespace-nowrap flex items-center space-x-5 3xl:pl-12 py-4 pr-3 text-sm font-medium text-white pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
            <span>
                {{ member?.name }}
            </span>
        </div>
        <div class="whitespace-nowrap px-3 py-4 text-sm text-muted">
            {{
                projectMember.billable_rate
                    ? formatCents(projectMember.billable_rate)
                    : '--'
            }}
        </div>
        <div class="whitespace-nowrap px-3 py-4 text-sm text-muted">
            {{ capitalizeFirstLetter(member?.role ?? '') }}
        </div>
        <div
            class="relative whitespace-nowrap flex items-center pl-3 text-right text-sm font-medium sm:pr-0 pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
            <ProjectMemberMoreOptionsDropdown
                :project-member="projectMember"
                @delete="
                    deleteProjectMember
                "></ProjectMemberMoreOptionsDropdown>
        </div>
    </TableRow>
</template>

<style scoped></style>
