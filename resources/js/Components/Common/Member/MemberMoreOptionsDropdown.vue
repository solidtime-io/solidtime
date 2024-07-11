<script setup lang="ts">
import { TrashIcon, PencilSquareIcon } from '@heroicons/vue/20/solid';
import type { Member } from '@/utils/api';
import { canDeleteMembers, canUpdateMembers } from '@/utils/permissions';
import MoreOptionsDropdown from '@/Components/MoreOptionsDropdown.vue';

const emit = defineEmits<{
    delete: [];
    edit: [];
}>();
const props = defineProps<{
    member: Member;
}>();
</script>

<template>
    <MoreOptionsDropdown
        v-if="canUpdateMembers() || canDeleteMembers()"
        :label="'Actions for Member ' + props.member.name">
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
    </MoreOptionsDropdown>
</template>

<style scoped></style>
