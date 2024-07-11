<script setup lang="ts">
import { TrashIcon, PencilSquareIcon } from '@heroicons/vue/20/solid';
import type { ProjectMember } from '@/utils/api';
import { useMembersStore } from '@/utils/useMembers';
import { storeToRefs } from 'pinia';
import { computed } from 'vue';
import MoreOptionsDropdown from '@/Components/MoreOptionsDropdown.vue';

const emit = defineEmits<{
    delete: [];
    edit: [];
}>();
const props = defineProps<{
    projectMember: ProjectMember;
}>();

const { members } = storeToRefs(useMembersStore());

const currentMember = computed(() => {
    return members.value.find(
        (member) => member.id === props.projectMember.user_id
    );
});
</script>

<template>
    <MoreOptionsDropdown
        :label="'Actions for Project Member ' + currentMember?.name">
        <button
            @click.prevent="emit('edit')"
            :aria-label="'Edit Project Member ' + currentMember?.name"
            class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
            <PencilSquareIcon class="w-5 text-icon-active"></PencilSquareIcon>
            <span>Edit</span>
        </button>
        <button
            @click.prevent="emit('delete')"
            :aria-label="'Delete Project Member ' + currentMember?.name"
            data-testid="project_delete"
            class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
            <TrashIcon class="w-5 text-icon-active"></TrashIcon>
            <span>Remove from Team</span>
        </button>
    </MoreOptionsDropdown>
</template>

<style scoped></style>
