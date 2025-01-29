<script setup lang="ts">
import { TrashIcon } from '@heroicons/vue/20/solid';
import MoreOptionsDropdown from '@/packages/ui/src/MoreOptionsDropdown.vue';

const emit = defineEmits<{
    delete: [];
}>();
</script>

<template>
    <MoreOptionsDropdown label="Actions for the time entry">
        <button
            data-testid="time_entry_delete"
            class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out"
            @click="emit('delete')">
            <TrashIcon class="w-5 text-icon-active"></TrashIcon>
            <span>Delete</span>
        </button>
    </MoreOptionsDropdown>
</template>

<style scoped></style>
