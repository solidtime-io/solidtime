<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import TimeTracker from '@/Components/TimeTracker.vue';
import { onMounted, ref, watch } from 'vue';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import { useTimeEntriesStore } from '@/utils/useTimeEntries';
import { storeToRefs } from 'pinia';
import type {
    CreateClientBody,
    CreateProjectBody,
    CreateTimeEntryBody,
    Project,
    TimeEntry,
    Client,
} from '@/packages/api/src';
import { useElementVisibility } from '@vueuse/core';
import { ClockIcon } from '@heroicons/vue/20/solid';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { PlusIcon } from '@heroicons/vue/16/solid';
import LoadingSpinner from '@/packages/ui/src/LoadingSpinner.vue';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { useTasksStore } from '@/utils/useTasks';
import { useProjectsStore } from '@/utils/useProjects';
import TimeEntryGroupedTable from '@/packages/ui/src/TimeEntry/TimeEntryGroupedTable.vue';
import { useTagsStore } from '@/utils/useTags';
import { useClientsStore } from '@/utils/useClients';
import TimeEntryCreateModal from '@/Components/Common/TimeEntry/TimeEntryCreateModal.vue';
import { getOrganizationCurrencyString } from '@/utils/money';
import TimeEntryMassActionRow from '@/packages/ui/src/TimeEntry/TimeEntryMassActionRow.vue';
import type { UpdateMultipleTimeEntriesChangeset } from '@/packages/api/src';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import { canCreateProjects } from '@/utils/permissions';

const timeEntriesStore = useTimeEntriesStore();
const { timeEntries, allTimeEntriesLoaded } = storeToRefs(timeEntriesStore);
const { updateTimeEntry, fetchTimeEntries, createTimeEntry } =
    useTimeEntriesStore();

async function updateTimeEntries(
    ids: string[],
    changes: UpdateMultipleTimeEntriesChangeset
) {
    await useTimeEntriesStore().updateTimeEntries(ids, changes);
    fetchTimeEntries();
}

const loading = ref(false);
const loadMoreContainer = ref<HTMLDivElement | null>(null);
const isLoadMoreVisible = useElementVisibility(loadMoreContainer);
const currentTimeEntryStore = useCurrentTimeEntryStore();
const { currentTimeEntry } = storeToRefs(currentTimeEntryStore);
const { setActiveState } = currentTimeEntryStore;
const { tags } = storeToRefs(useTagsStore());

async function startTimeEntry(
    timeEntry: Omit<CreateTimeEntryBody, 'member_id'>
) {
    if (currentTimeEntry.value.id) {
        await setActiveState(false);
    }
    await createTimeEntry(timeEntry);
    fetchTimeEntries();
    useCurrentTimeEntryStore().fetchCurrentTimeEntry();
}

function deleteTimeEntries(timeEntries: TimeEntry[]) {
    useTimeEntriesStore().deleteTimeEntries(timeEntries);
    fetchTimeEntries();
}

watch(isLoadMoreVisible, async (isVisible) => {
    if (
        isVisible &&
        timeEntries.value.length > 0 &&
        !allTimeEntriesLoaded.value
    ) {
        loading.value = true;
        await timeEntriesStore.fetchMoreTimeEntries();
    }
});

onMounted(async () => {
    await timeEntriesStore.fetchTimeEntries();
});

const showManualTimeEntryModal = ref(false);
const projectStore = useProjectsStore();
const { projects } = storeToRefs(projectStore);
const taskStore = useTasksStore();
const { tasks } = storeToRefs(taskStore);
const clientStore = useClientsStore();
const { clients } = storeToRefs(clientStore);

async function createTag(name: string) {
    return await useTagsStore().createTag(name);
}
async function createProject(
    project: CreateProjectBody
): Promise<Project | undefined> {
    return await useProjectsStore().createProject(project);
}
async function createClient(
    body: CreateClientBody
): Promise<Client | undefined> {
    return await useClientsStore().createClient(body);
}

const selectedTimeEntries = ref([] as TimeEntry[]);

async function clearSelectionAndState() {
    selectedTimeEntries.value = [];
    await fetchTimeEntries();
}

function deleteSelected() {
    deleteTimeEntries(selectedTimeEntries.value);
    selectedTimeEntries.value = [];
}
</script>

<template>
    <TimeEntryCreateModal
        :enableEstimatedTime="isAllowedToPerformPremiumAction()"
        :createProject="createProject"
        :createClient="createClient"
        :createTimeEntry="createTimeEntry"
        v-model:show="showManualTimeEntryModal"></TimeEntryCreateModal>
    <AppLayout title="Dashboard" data-testid="time_view">
        <MainContainer
            class="pt-5 lg:pt-8 pb-4 lg:pb-6 border-b border-default-background-separator">
            <div
                class="lg:flex items-end lg:divide-x divide-default-background-separator divide-y lg:divide-y-0 space-y-2 lg:space-y-0 lg:space-x-2">
                <div class="flex-1">
                    <TimeTracker></TimeTracker>
                </div>
                <div class="pb-2 pt-2 lg:pt-0 lg:pl-4 flex justify-center">
                    <SecondaryButton
                        class="w-full text-center flex justify-center"
                        @click="showManualTimeEntryModal = true"
                        :icon="PlusIcon"
                        >Manual time entry
                    </SecondaryButton>
                </div>
            </div>
        </MainContainer>
        <TimeEntryMassActionRow
            :selected-time-entries="selectedTimeEntries"
            :enableEstimatedTime="isAllowedToPerformPremiumAction()"
            :canCreateProject="canCreateProjects()"
            @submit="clearSelectionAndState"
            :all-selected="selectedTimeEntries.length === timeEntries.length"
            @select-all="selectedTimeEntries = [...timeEntries]"
            @unselect-all="selectedTimeEntries = []"
            :delete-selected="deleteSelected"
            :projects="projects"
            :tasks="tasks"
            :tags="tags"
            :currency="getOrganizationCurrencyString()"
            :clients="clients"
            :update-time-entries="
                (args) =>
                    updateTimeEntries(
                        selectedTimeEntries.map((timeEntry) => timeEntry.id),
                        args
                    )
            "
            :create-project="createProject"
            :create-client="createClient"
            :createTag="createTag"></TimeEntryMassActionRow>
        <TimeEntryGroupedTable
            v-model:selected="selectedTimeEntries"
            :createProject
            :enableEstimatedTime="isAllowedToPerformPremiumAction()"
            :canCreateProject="canCreateProjects()"
            :clients
            :createClient
            :updateTimeEntry
            :updateTimeEntries
            :deleteTimeEntries
            :createTimeEntry="startTimeEntry"
            :createTag
            :projects="projects"
            :tasks="tasks"
            :currency="getOrganizationCurrencyString()"
            :timeEntries="timeEntries"
            :tags="tags"></TimeEntryGroupedTable>
        <div v-if="timeEntries.length === 0" class="text-center pt-12">
            <ClockIcon class="w-8 text-icon-default inline pb-2"></ClockIcon>
            <h3 class="text-white font-semibold">No time entries found</h3>
            <p class="pb-5">Create your first time entry now!</p>
        </div>
        <div ref="loadMoreContainer">
            <div
                v-if="loading && !allTimeEntriesLoaded"
                class="flex justify-center items-center py-5 text-white font-medium">
                <LoadingSpinner></LoadingSpinner>
                <span> Loading more time entries... </span>
            </div>
            <div
                v-else-if="allTimeEntriesLoaded"
                class="flex justify-center items-center py-5 text-muted font-medium">
                All time entries are loaded!
            </div>
        </div>
    </AppLayout>
</template>
