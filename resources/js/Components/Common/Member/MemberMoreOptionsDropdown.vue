<script setup lang="ts">
import Dropdown from '@/Components/Dropdown.vue';
import { TrashIcon, PencilSquareIcon } from '@heroicons/vue/20/solid';
import type { Member } from '@/utils/api';
import { canDeleteMembers, canUpdateMembers } from '@/utils/permissions';

const emit = defineEmits<{
    delete: [];
    edit: [];
}>();
const props = defineProps<{
    member: Member;
}>();
</script>

<template>
    <Dropdown align="bottom-end">
        <template #trigger>
            <button
                class="focus-visible:outline-none focus-visible:bg-card-background rounded-full focus-visible:ring-1 focus-visible:ring-input-border-active focus-visible:opacity-100 hover:bg-card-background group-hover:opacity-100 opacity-20 transition-opacity"
                :aria-label="'Actions for Member ' + props.member.name">
                <svg
                    class="h-10 w-10 p-2 rounded-full"
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
            </button>
        </template>
        <template #content>
            <div class="min-w-[150px]">
                <button
                    v-if="canUpdateMembers()"
                    @click="emit('edit')"
                    :aria-label="'Edit Member ' + props.member.name"
                    class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
                    <PencilSquareIcon
                        class="w-5 text-icon-active"></PencilSquareIcon>
                    <span>Edit</span>
                </button>
                <button
                    v-if="canDeleteMembers()"
                    @click="emit('delete')"
                    :aria-label="'Delete Member ' + props.member.name"
                    data-testid="member_delete"
                    class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
                    <TrashIcon class="w-5 text-icon-active"></TrashIcon>
                    <span>Delete</span>
                </button>
            </div>
        </template>
    </Dropdown>
</template>

<style scoped></style>
