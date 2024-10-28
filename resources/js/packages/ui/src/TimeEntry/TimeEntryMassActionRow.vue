<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import { PencilSquareIcon, TrashIcon } from '@heroicons/vue/20/solid';
import TimeEntryMassUpdateModal from '@/packages/ui/src/TimeEntry/TimeEntryMassUpdateModal.vue';
import type {
    Client,
    CreateClientBody,
    CreateProjectBody,
    Project,
    Tag,
    Task,
    TimeEntry,
    UpdateMultipleTimeEntriesChangeset,
} from '@/packages/api/src';
import { ref } from 'vue';
import { twMerge } from 'tailwind-merge';
import { Checkbox, InputLabel } from '@/packages/ui/src';

const props = defineProps<{
    selectedTimeEntries: TimeEntry[];
    deleteSelected: () => void;
    class?: string;
    allSelected: boolean;
    projects: Project[];
    tasks: Task[];
    tags: Tag[];
    clients: Client[];
    createTag: (name: string) => Promise<Tag | undefined>;
    createProject: (project: CreateProjectBody) => Promise<Project | undefined>;
    createClient: (client: CreateClientBody) => Promise<Client | undefined>;
    updateTimeEntries: (
        changeset: UpdateMultipleTimeEntriesChangeset
    ) => Promise<void>;
    currency: string;
    enableEstimatedTime: boolean;
    canCreateProject: boolean;
}>();

const emit = defineEmits<{
    submit: [];
    selectAll: [];
    unselectAll: [];
}>();

const showMassUpdateModal = ref(false);
</script>

<template>
    <TimeEntryMassUpdateModal
        :projects
        :tasks
        :tags
        :clients
        :createTag
        :createProject
        :createClient
        :updateTimeEntries
        :enableEstimatedTime
        :canCreateProject
        :currency
        :time-entries="selectedTimeEntries"
        @submit="emit('submit')"
        v-model:show="showMassUpdateModal"></TimeEntryMassUpdateModal>
    <MainContainer
        :class="
            twMerge(
                props.class,
                'text-sm py-1.5 font-medium border-b border-t border-border-secondary flex items-center space-x-3'
            )
        ">
        <Checkbox
            :checked="allSelected"
            id="selectAll"
            @update:checked="
                allSelected ? emit('unselectAll') : emit('selectAll')
            ">
        </Checkbox>
        <InputLabel
            for="selectAll"
            class="select-none text-text-secondary"
            v-if="selectedTimeEntries.length > 0">
            {{ selectedTimeEntries.length }} selected
        </InputLabel>
        <InputLabel
            for="selectAll"
            class="text-text-secondary select-none"
            v-else
            >Select All</InputLabel
        >
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
