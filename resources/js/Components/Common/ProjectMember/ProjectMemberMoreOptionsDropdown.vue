<script setup lang="ts">
import { TrashIcon, PencilSquareIcon } from '@heroicons/vue/20/solid';
import type { ProjectMember } from '@/packages/api/src';
import { useMembersQuery } from '@/utils/useMembersQuery';
import { computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';

const emit = defineEmits<{
    delete: [];
    edit: [];
}>();
const props = defineProps<{
    projectMember: ProjectMember;
}>();

const { members } = useMembersQuery();

const currentMember = computed(() => {
    return members.value.find((member) => member.id === props.projectMember.user_id);
});
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                class="focus-visible:outline-none focus-visible:bg-card-background rounded-full focus-visible:ring-2 focus-visible:ring-ring focus-visible:opacity-100 hover:bg-card-background group-hover:opacity-100 opacity-20 transition-opacity text-text-secondary"
                :aria-label="'Actions for Project Member ' + currentMember?.name">
                <svg
                    class="h-8 w-8 p-1 rounded-full"
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
        </DropdownMenuTrigger>
        <DropdownMenuContent class="min-w-[150px]" align="end">
            <DropdownMenuItem
                :aria-label="'Edit Project Member ' + currentMember?.name"
                class="flex items-center space-x-3 cursor-pointer"
                @click.prevent="emit('edit')">
                <PencilSquareIcon class="w-5 text-icon-active" />
                <span>Edit</span>
            </DropdownMenuItem>
            <DropdownMenuItem
                :aria-label="'Delete Project Member ' + currentMember?.name"
                data-testid="project_delete"
                class="flex items-center space-x-3 cursor-pointer text-destructive focus:text-destructive"
                @click.prevent="emit('delete')">
                <TrashIcon class="w-5" />
                <span>Remove from Team</span>
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>

<style scoped></style>
