<script setup lang="ts">
import { TrashIcon, PencilSquareIcon } from '@heroicons/vue/20/solid';
import type { Report } from '@/packages/api/src';
import MoreOptionsDropdown from '@/packages/ui/src/MoreOptionsDropdown.vue';
import { canDeleteReport, canUpdateReport } from '@/utils/permissions';
const emit = defineEmits<{
    delete: [];
    edit: [];
    archive: [];
}>();
const props = defineProps<{
    report: Report;
}>();
</script>

<template>
    <MoreOptionsDropdown :label="'Actions for Project ' + props.report.name">
        <div class="min-w-[150px]">
            <button
                v-if="canUpdateReport()"
                :aria-label="'Edit Report ' + props.report.name"
                class="flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out"
                @click.prevent="emit('edit')">
                <PencilSquareIcon
                    class="w-5 text-icon-active"></PencilSquareIcon>
                <span>Edit</span>
            </button>
            <button
                v-if="canDeleteReport()"
                :aria-label="'Delete Report ' + props.report.name"
                class="border-b border-card-background-separator flex items-center space-x-3 w-full px-3 py-2.5 text-start text-sm font-medium leading-5 text-white hover:bg-card-background-active focus:outline-none focus:bg-card-background-active transition duration-150 ease-in-out"
                @click.prevent="emit('delete')">
                <TrashIcon class="w-5 text-icon-active"></TrashIcon>
                <span>Delete</span>
            </button>
        </div>
    </MoreOptionsDropdown>
</template>

<style scoped></style>
