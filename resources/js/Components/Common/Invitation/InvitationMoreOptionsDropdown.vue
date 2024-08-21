<script setup lang="ts">
import { TrashIcon, ArrowPathIcon } from '@heroicons/vue/20/solid';
import MoreOptionsDropdown from '@/packages/ui/src/MoreOptionsDropdown.vue';
const emit = defineEmits<{
    delete: [];
    resend: [];
}>();
</script>

<template>
    <MoreOptionsDropdown label="Actions for the invitation">
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
    </MoreOptionsDropdown>
</template>

<style scoped></style>
