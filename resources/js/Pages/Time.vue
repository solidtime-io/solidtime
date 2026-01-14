<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import TimeTracker from '@/Components/TimeTracker.vue';
import { computed, ref, watch } from 'vue';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
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
import LoadingSpinner from '@/packages/ui/src/LoadingSpinner.vue';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { useTasksQuery } from '@/utils/useTasksQuery';
import { useProjectsQuery } from '@/utils/useProjectsQuery';
import TimeEntryGroupedTable from '@/packages/ui/src/TimeEntry/TimeEntryGroupedTable.vue';
import { useTagsQuery } from '@/utils/useTagsQuery';
import { useClientsQuery } from '@/utils/useClientsQuery';
import { getOrganizationCurrencyString } from '@/utils/money';
import TimeEntryMassActionRow from '@/packages/ui/src/TimeEntry/TimeEntryMassActionRow.vue';
import type { UpdateMultipleTimeEntriesChangeset } from '@/packages/api/src';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import { canCreateProjects } from '@/utils/permissions';
import { useTagsStore } from '@/utils/useTags';
import { useProjectsStore } from '@/utils/useProjects';
import { useClientsStore } from '@/utils/useClients';
import { useTimeEntriesInfiniteQuery } from '@/utils/useTimeEntriesInfiniteQuery';
import { useTimeEntriesMutations } from '@/utils/useTimeEntriesMutations';

const { data, fetchNextPage, hasNextPage, isFetchingNextPage } = useTimeEntriesInfiniteQuery();
const {
    createTimeEntry: createTimeEntryMutation,
    updateTimeEntry,
    updateTimeEntries: updateTimeEntriesMutation,
    deleteTimeEntries: deleteTimeEntriesMutation,
} = useTimeEntriesMutations();

const timeEntries = computed(() => data.value?.pages.flatMap((page) => page.data) || []);

async function updateTimeEntries(ids: string[], changes: UpdateMultipleTimeEntriesChangeset) {
    await updateTimeEntriesMutation({ ids, changes });
}

const loadMoreContainer = ref<HTMLDivElement | null>(null);
const isLoadMoreVisible = useElementVisibility(loadMoreContainer);
const currentTimeEntryStore = useCurrentTimeEntryStore();
const { currentTimeEntry } = storeToRefs(currentTimeEntryStore);
const { setActiveState } = currentTimeEntryStore;

async function startTimeEntry(timeEntry: Omit<CreateTimeEntryBody, 'member_id'>) {
    if (currentTimeEntry.value.id) {
        await setActiveState(false);
    }
    await createTimeEntryMutation(timeEntry);
    useCurrentTimeEntryStore().fetchCurrentTimeEntry();
}

async function deleteTimeEntries(timeEntries: TimeEntry[]) {
    await deleteTimeEntriesMutation(timeEntries);
}

watch(isLoadMoreVisible, async (isVisible) => {
    if (isVisible && hasNextPage.value) {
        await fetchNextPage();
    }
});

const { projects } = useProjectsQuery();
const { tasks } = useTasksQuery();
const { clients } = useClientsQuery();

const { tags } = useTagsQuery();

async function createTag(name: string) {
    return await useTagsStore().createTag(name);
}
async function createProject(project: CreateProjectBody): Promise<Project | undefined> {
    return await useProjectsStore().createProject(project);
}
async function createClient(body: CreateClientBody): Promise<Client | undefined> {
    return await useClientsStore().createClient(body);
}

const selectedTimeEntries = ref([] as TimeEntry[]);

async function clearSelectionAndState() {
    selectedTimeEntries.value = [];
}

function deleteSelected() {
    deleteTimeEntries(selectedTimeEntries.value);
    selectedTimeEntries.value = [];
}
</script>

<template>
    <AppLayout title="Dashboard" data-testid="time_view">
        <MainContainer class="pt-5 lg:pt-8 pb-4 lg:pb-6">
            <TimeTracker></TimeTracker>
        </MainContainer>
        <TimeEntryMassActionRow
            :selected-time-entries="selectedTimeEntries"
            :enable-estimated-time="isAllowedToPerformPremiumAction()"
            :can-create-project="canCreateProjects()"
            :all-selected="selectedTimeEntries.length === timeEntries.length"
            :delete-selected="deleteSelected"
            :projects="projects"
            :tasks="tasks"
            :tags="tags"
            :currency="getOrganizationCurrencyString()"
            :clients="clients"
            class="border-t border-default-background-separator"
            :update-time-entries="
                (args) =>
                    updateTimeEntries(
                        selectedTimeEntries.map((timeEntry) => timeEntry.id),
                        args
                    )
            "
            :create-project="createProject"
            :create-client="createClient"
            :create-tag="createTag"
            @submit="clearSelectionAndState"
            @select-all="selectedTimeEntries = [...timeEntries]"
            @unselect-all="selectedTimeEntries = []"></TimeEntryMassActionRow>
        <TimeEntryGroupedTable
            v-model:selected="selectedTimeEntries"
            :create-project
            :enable-estimated-time="isAllowedToPerformPremiumAction()"
            :can-create-project="canCreateProjects()"
            :clients
            :create-client
            :update-time-entry
            :update-time-entries
            :delete-time-entries
            :create-time-entry="startTimeEntry"
            :create-tag
            :projects="projects"
            :tasks="tasks"
            :currency="getOrganizationCurrencyString()"
            :time-entries="timeEntries"
            :tags="tags"></TimeEntryGroupedTable>
        <div v-if="timeEntries.length === 0" class="text-center pt-12">
            <ClockIcon class="w-8 text-icon-default inline pb-2"></ClockIcon>
            <h3 class="text-text-primary font-semibold">No time entries found</h3>
            <p class="pb-5">Create your first time entry now!</p>
        </div>
        <div ref="loadMoreContainer">
            <div
                v-if="isFetchingNextPage"
                class="flex justify-center items-center py-5 text-text-primary font-medium">
                <LoadingSpinner></LoadingSpinner>
                <span> Loading more time entries... </span>
            </div>
            <div
                v-else-if="!hasNextPage"
                class="flex justify-center items-center py-5 text-text-secondary font-medium">
                All time entries are loaded!
            </div>
        </div>
    </AppLayout>
</template>
