<script setup lang="ts">
import { TrashIcon, UserCircleIcon, PencilSquareIcon, ArrowDownOnSquareStackIcon } from '@heroicons/vue/20/solid';
import type { Member } from '@/packages/api/src';
import {canDeleteMembers, canMakeMembersPlaceholders, canMergeMembers, canUpdateMembers} from '@/utils/permissions';
import MoreOptionsDropdown from '@/packages/ui/src/MoreOptionsDropdown.vue';

const emit = defineEmits<{
    delete: [];
    edit: [];
    merge: [];
    makePlaceholder: [];
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
                :aria-label="'Edit Member ' + props.member.name"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out"
                @click="emit('edit')">
                <PencilSquareIcon
                    class="w-5 text-icon-active"></PencilSquareIcon>
                <span>Edit</span>
            </button>
            <button
                v-if="canDeleteMembers()"
                :aria-label="'Delete Member ' + props.member.name"
                data-testid="member_delete"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out"
                @click="emit('delete')">
                <TrashIcon class="w-5 text-icon-active"></TrashIcon>
                <span>Delete</span>
            </button>
            <button
                v-if="props.member.role === 'placeholder' && canMergeMembers()"
                :aria-label="'Merge Member ' + props.member.name"
                data-testid="member_merge"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out"
                @click="emit('merge')">
                <ArrowDownOnSquareStackIcon class="w-5 text-icon-active"></ArrowDownOnSquareStackIcon>
                <span>Merge</span>
            </button>
            <button
                v-if="props.member.role !== 'placeholder' && canMakeMembersPlaceholders()"
                :aria-label="'Make Member ' + props.member.name + ' a placeholder'"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out"
                @click="emit('makePlaceholder')">
                <UserCircleIcon class="w-5 text-icon-active"></UserCircleIcon>
                <span>Deactivate</span>
            </button>
        </div>
    </MoreOptionsDropdown>
</template>

<style scoped></style>
