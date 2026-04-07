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
import { Button, Checkbox } from '@/packages/ui/src';
import { FieldLabel } from '../field';

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
    updateTimeEntries: (changeset: UpdateMultipleTimeEntriesChangeset) => Promise<void>;
    currency: string;
    enableEstimatedTime: boolean;
    canCreateProject: boolean;
    organizationBillableRate: number | null;
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
        v-model:show="showMassUpdateModal"
        :projects
        :tasks
        :tags
        :clients
        :create-tag
        :create-project
        :create-client
        :update-time-entries
        :enable-estimated-time
        :can-create-project
        :currency
        :organization-billable-rate="organizationBillableRate"
        :time-entries="selectedTimeEntries"
        @submit="emit('submit')"></TimeEntryMassUpdateModal>
    <MainContainer
        :class="
            twMerge(
                props.class,
                'text-sm h-8 font-medium hidden sm:flex border-b border-border-primary items-center space-x-3'
            )
        ">
        <Checkbox
            id="selectAll"
            :checked="allSelected && selectedTimeEntries.length > 0"
            @update:checked="allSelected ? emit('unselectAll') : emit('selectAll')">
        </Checkbox>
        <FieldLabel
            v-if="selectedTimeEntries.length > 0"
            for="selectAll"
            class="select-none text-text-secondary">
            {{ selectedTimeEntries.length }} selected
        </FieldLabel>
        <FieldLabel v-else for="selectAll" class="text-text-secondary select-none"
            >Select All</FieldLabel
        >
        <Button
            v-if="selectedTimeEntries.length"
            variant="ghost"
            size="xs"
            class="text-text-tertiary hover:text-text-secondary"
            @click="showMassUpdateModal = true">
            <PencilSquareIcon class="w-4"></PencilSquareIcon>
            <span>Edit</span>
        </Button>
        <Button
            v-if="selectedTimeEntries.length"
            variant="ghost"
            size="xs"
            class="text-red-400 hover:text-red-500 hover:bg-red-500/10"
            @click="deleteSelected">
            <TrashIcon class="w-3.5"></TrashIcon>
            <span>Delete</span>
        </Button>
    </MainContainer>
</template>

<style scoped></style>
