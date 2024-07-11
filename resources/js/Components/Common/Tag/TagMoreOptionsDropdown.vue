<script setup lang="ts">
import { TrashIcon } from '@heroicons/vue/20/solid';
import type { Tag } from '@/utils/api';
import MoreOptionsDropdown from '@/Components/MoreOptionsDropdown.vue';

const emit = defineEmits<{
    delete: [];
}>();
const props = defineProps<{
    tag: Tag;
}>();
</script>

<template>
    <MoreOptionsDropdown :label="'Actions for Tag ' + props.tag.name">
        <button
            @click="emit('delete')"
            :aria-label="'Delete Tag ' + props.tag.name"
            data-testid="tag_delete"
            class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out">
            <TrashIcon class="w-5 text-icon-active"></TrashIcon>
            <span>Delete</span>
        </button>
    </MoreOptionsDropdown>
</template>

<style scoped></style>
