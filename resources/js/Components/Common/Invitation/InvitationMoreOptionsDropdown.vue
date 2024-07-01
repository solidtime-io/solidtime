<script setup lang="ts">
import Dropdown from '@/Components/Dropdown.vue';
import { TrashIcon, ArrowPathIcon } from '@heroicons/vue/20/solid';
const emit = defineEmits<{
    delete: [];
    resend: [];
}>();
</script>

<template>
    <Dropdown align="bottom-end">
        <template #trigger>
            <svg
                data-testid="invitation_actions"
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
                @click="emit('resend')"
                data-testid="invitation_delete"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
                <ArrowPathIcon class="w-5 text-icon-active"></ArrowPathIcon>
                <span>Resend Invitation</span>
            </button>
            <button
                @click="emit('delete')"
                data-testid="invitation_delete"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
                <TrashIcon class="w-5 text-icon-active"></TrashIcon>
                <span>Delete</span>
            </button>
        </template>
    </Dropdown>
</template>

<style scoped></style>
