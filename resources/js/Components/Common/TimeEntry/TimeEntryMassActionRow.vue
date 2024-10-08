<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/20/solid';
import TimeEntryMassUpdateModal from '@/Components/Common/TimeEntry/TimeEntryMassUpdateModal.vue';
import type { TimeEntry } from '@/packages/api/src';
import { ref } from 'vue';
import { twMerge } from 'tailwind-merge';

const props = defineProps<{
    selectedTimeEntries: TimeEntry[];
    deleteSelected: () => void;
    class?: string;
}>();

const emit = defineEmits<{
    submit: [];
}>();

const showMassUpdateModal = ref(false);
</script>

<template>
    <TimeEntryMassUpdateModal
        :time-entries="selectedTimeEntries"
        @submit="emit('submit')"
        v-model:show="showMassUpdateModal"></TimeEntryMassUpdateModal>
    <MainContainer
        v-if="selectedTimeEntries.length > 0"
        :class="
            twMerge(
                props.class,
                'text-sm py-1.5 font-medium border-b border-t border-border-primary flex items-center space-x-3'
            )
        ">
        <div>{{ selectedTimeEntries.length }} selected</div>
        <button
            class="text-text-tertiary flex space-x-1 items-center hover:text-text-secondary transition focus-visible:ring-2 outline-0 focus-visible:text-text-primary focus-visible:ring-white/80 rounded h-full px-2"
            @click="showMassUpdateModal = true"
            v-if="selectedTimeEntries.length">
            <PencilSquareIcon class="w-4"></PencilSquareIcon>
            <span> Edit </span>
        </button>
        <button
            class="text-red-400 h-full px-2 space-x-1 items-center flex hover:text-red-500 transition focus-visible:ring-2 outline-0 focus-visible:text-red-500 focus-visible:ring-white/80 rounded"
            @click="deleteSelected"
            v-if="selectedTimeEntries.length">
            <TrashIcon class="w-3.5"></TrashIcon>
            <span> Delete </span>
        </button>
    </MainContainer>
</template>

<style scoped></style>
