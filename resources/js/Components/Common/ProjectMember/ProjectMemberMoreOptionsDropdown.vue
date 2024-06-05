<script setup lang="ts">
import Dropdown from '@/Components/Dropdown.vue';
import { TrashIcon, PencilSquareIcon } from '@heroicons/vue/20/solid';
import type { ProjectMember } from '@/utils/api';
import { useMembersStore } from '@/utils/useMembers';
import { storeToRefs } from 'pinia';
import { computed } from 'vue';
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
    <Dropdown>
        <template #trigger>
            <svg
                data-testid="project_actions"
                :aria-label="
                    'Actions for Project Member ' + currentMember?.name
                "
                class="h-10 w-10 p-2 rounded-full hover:bg-card-background opacity-20 group-hover:opacity-100 transition"
                viewBox="0 0 24 24"
                xmlns="http://www.w3.org/2000/svg">
                <path
                    fill="none"
                    stroke="currentColor"
                    stroke-linecap="round"
                    stroke-linejoin="round"
                    stroke-width="1.5"
                    d="M12 5.92A.96.96 0 1 0 12 4a.96.96 0 0 0 0 1.92m0 7.04a.96.96 0 1 0 0-1.92a.96.96 0 0 0 0 1.92M12 20a.96.96 0 1 0 0-1.92a.96.96 0 0 0 0 1.92" />
            </svg>
        </template>
        <template #content>
            <button
                @click.prevent="emit('edit')"
                :aria-label="'Edit Project Member ' + currentMember?.name"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
                <PencilSquareIcon
                    class="w-5 text-icon-active"></PencilSquareIcon>
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
        </template>
    </Dropdown>
</template>

<style scoped></style>
