<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FolderIcon } from '@heroicons/vue/16/solid';
import PageTitle from '@/Components/Common/PageTitle.vue';
import {
    ChartBarIcon,
    UserGroupIcon,
    CheckCircleIcon,
    TagIcon,
    ChevronLeftIcon,
    ChevronRightIcon,
} from '@heroicons/vue/20/solid';
import DateRangePicker from '@/packages/ui/src/Input/DateRangePicker.vue';
import BillableIcon from '@/packages/ui/src/Icons/BillableIcon.vue';
import { computed, onMounted, ref, watch } from 'vue';
import {
    getDayJsInstance,
    getLocalizedDayJs,
} from '@/packages/ui/src/utils/time';
import { storeToRefs } from 'pinia';
import TagDropdown from '@/packages/ui/src/Tag/TagDropdown.vue';
import type {
    Client,
    CreateClientBody,
    CreateProjectBody,
    Project,
    TimeEntriesQueryParams,
    TimeEntry,
} from '@/packages/api/src';
import ReportingFilterBadge from '@/Components/Common/Reporting/ReportingFilterBadge.vue';
import ProjectMultiselectDropdown from '@/Components/Common/Project/ProjectMultiselectDropdown.vue';
import MemberMultiselectDropdown from '@/Components/Common/Member/MemberMultiselectDropdown.vue';
import TaskMultiselectDropdown from '@/Components/Common/Task/TaskMultiselectDropdown.vue';
import SelectDropdown from '@/packages/ui/src/Input/SelectDropdown.vue';
import ClientMultiselectDropdown from '@/Components/Common/Client/ClientMultiselectDropdown.vue';
import { useTagsStore } from '@/utils/useTags';
import { useElementVisibility, useSessionStorage } from '@vueuse/core';
import { router } from '@inertiajs/vue3';
import TabBar from '@/Components/Common/TabBar/TabBar.vue';
import TabBarItem from '@/Components/Common/TabBar/TabBarItem.vue';
import TimeEntryRow from '@/packages/ui/src/TimeEntry/TimeEntryRow.vue';
import { useTimeEntriesStore } from '@/utils/useTimeEntries';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { useProjectsStore } from '@/utils/useProjects';
import { useTasksStore } from '@/utils/useTasks';
import { useClientsStore } from '@/utils/useClients';
import dayjs from 'dayjs';
import { getOrganizationCurrencyString } from '@/utils/money';
import { useMembersStore } from '@/utils/useMembers';
import { SecondaryButton } from '@/packages/ui/src';

const startDate = useSessionStorage<string>(
    'reporting-start-date',
    getLocalizedDayJs(getDayJsInstance()().format()).subtract(14, 'd').format()
);
const endDate = useSessionStorage<string>(
    'reporting-end-date',
    getLocalizedDayJs(getDayJsInstance()().format()).format()
);
const selectedTags = ref<string[]>([]);
const selectedProjects = ref<string[]>([]);
const selectedMembers = ref<string[]>([]);
const selectedTasks = ref<string[]>([]);
const selectedClients = ref<string[]>([]);
const billable = ref<'true' | 'false' | null>(null);

const { members } = storeToRefs(useMembersStore());
const pageLimit = 10;
function getFilterAttributes() {
    let params: TimeEntriesQueryParams = {
        start: getLocalizedDayJs(startDate.value).startOf('day').utc().format(),
        end: getLocalizedDayJs(endDate.value).endOf('day').utc().format(),
        active: 'false',
        limit: pageLimit,
    };
    params = {
        ...params,
        member_ids:
            selectedMembers.value.length > 0
                ? selectedMembers.value
                : undefined,
        project_ids:
            selectedProjects.value.length > 0
                ? selectedProjects.value
                : undefined,
        task_ids:
            selectedTasks.value.length > 0 ? selectedTasks.value : undefined,
        client_ids:
            selectedClients.value.length > 0
                ? selectedClients.value
                : undefined,
        tag_ids: selectedTags.value.length > 0 ? selectedTags.value : undefined,
        billable: billable.value !== null ? billable.value : undefined,
    };
    return params;
}

const timeEntriesStore = useTimeEntriesStore();
const { timeEntries, allTimeEntriesLoaded } = storeToRefs(timeEntriesStore);
const { updateTimeEntry, fetchTimeEntries, createTimeEntry } =
    useTimeEntriesStore();

const loading = ref(false);
const loadMoreContainer = ref<HTMLDivElement | null>(null);
const isLoadMoreVisible = useElementVisibility(loadMoreContainer);
const currentTimeEntryStore = useCurrentTimeEntryStore();
const { currentTimeEntry } = storeToRefs(currentTimeEntryStore);
const { stopTimer } = currentTimeEntryStore;
const { tags } = storeToRefs(useTagsStore());

const currentPage = ref(1);

function deleteTimeEntries(timeEntries: TimeEntry[]) {
    timeEntries.forEach((entry) => {
        timeEntriesStore.deleteTimeEntry(entry.id);
    });
    updateFilteredTimeEntries();
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
    await updateFilteredTimeEntries();
});

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

async function startTimeEntryFromExisting(entry: TimeEntry) {
    if (currentTimeEntry.value.id) {
        await stopTimer();
    }
    await createTimeEntry({
        project_id: entry.project_id,
        task_id: entry.task_id,
        start: dayjs().utc().format(),
        end: null,
        billable: entry.billable,
        description: entry.description,
    });
    updateFilteredTimeEntries();
    useCurrentTimeEntryStore().fetchCurrentTimeEntry();
}

async function updateFilteredTimeEntries() {
    await fetchTimeEntries(getFilterAttributes());
}

const isNextPageAvailable = computed(() => {
    return timeEntries && timeEntries.value.length === pageLimit;
});

function nextPage() {
    currentPage.value++;
    fetchTimeEntries({
        ...getFilterAttributes(),
        end: timeEntries.value[timeEntries.value.length - 1].start,
    });
}
</script>

<template>
    <AppLayout
        title="Reporting"
        data-testid="reporting_view"
        class="overflow-hidden">
        <MainContainer
            class="py-3 sm:py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-3 sm:space-x-6">
                <PageTitle :icon="ChartBarIcon" title="Reporting"></PageTitle>
                <TabBar>
                    <TabBarItem @click="router.visit(route('reporting'))"
                        >Overview
                    </TabBarItem>
                    <TabBarItem
                        @click="router.visit(route('reporting.detailed'))"
                        active
                        >Detailed
                    </TabBarItem>
                </TabBar>
            </div>
        </MainContainer>
        <div class="py-2 w-full border-b border-default-background-separator">
            <MainContainer
                class="sm:flex space-y-4 sm:space-y-0 justify-between">
                <div
                    class="flex flex-wrap items-center space-y-2 sm:space-y-0 space-x-4">
                    <div class="text-sm font-medium">Filters</div>
                    <MemberMultiselectDropdown
                        @submit="updateFilteredTimeEntries"
                        v-model="selectedMembers">
                        <template v-slot:trigger>
                            <ReportingFilterBadge
                                :count="selectedMembers.length"
                                :active="selectedMembers.length > 0"
                                title="Members"
                                :icon="UserGroupIcon"></ReportingFilterBadge>
                        </template>
                    </MemberMultiselectDropdown>
                    <ProjectMultiselectDropdown
                        @submit="updateFilteredTimeEntries"
                        v-model="selectedProjects">
                        <template v-slot:trigger>
                            <ReportingFilterBadge
                                :count="selectedProjects.length"
                                :active="selectedProjects.length > 0"
                                title="Projects"
                                :icon="FolderIcon"></ReportingFilterBadge>
                        </template>
                    </ProjectMultiselectDropdown>
                    <TaskMultiselectDropdown
                        @submit="updateFilteredTimeEntries"
                        v-model="selectedTasks">
                        <template v-slot:trigger>
                            <ReportingFilterBadge
                                :count="selectedTasks.length"
                                :active="selectedTasks.length > 0"
                                title="Tasks"
                                :icon="CheckCircleIcon"></ReportingFilterBadge>
                        </template>
                    </TaskMultiselectDropdown>
                    <ClientMultiselectDropdown
                        @submit="updateFilteredTimeEntries"
                        v-model="selectedClients">
                        <template v-slot:trigger>
                            <ReportingFilterBadge
                                title="Clients"
                                :icon="FolderIcon"></ReportingFilterBadge>
                        </template>
                    </ClientMultiselectDropdown>
                    <TagDropdown
                        @submit="updateFilteredTimeEntries"
                        :createTag
                        v-model="selectedTags"
                        :tags="tags">
                        <template v-slot:trigger>
                            <ReportingFilterBadge
                                :count="selectedTags.length"
                                :active="selectedTags.length > 0"
                                title="Tags"
                                :icon="TagIcon"></ReportingFilterBadge>
                        </template>
                    </TagDropdown>

                    <SelectDropdown
                        @changed="updateFilteredTimeEntries"
                        v-model="billable"
                        :get-key-from-item="(item) => item.value"
                        :get-name-for-item="(item) => item.label"
                        :items="[
                            {
                                label: 'Both',
                                value: null,
                            },
                            {
                                label: 'Billable',
                                value: 'true',
                            },
                            {
                                label: 'Non Billable',
                                value: 'false',
                            },
                        ]">
                        <template v-slot:trigger>
                            <ReportingFilterBadge
                                :active="billable !== null"
                                :title="
                                    billable === 'false'
                                        ? 'Non Billable'
                                        : 'Billable'
                                "
                                :icon="BillableIcon"></ReportingFilterBadge>
                        </template>
                    </SelectDropdown>
                </div>
                <div>
                    <DateRangePicker
                        v-model:start="startDate"
                        v-model:end="endDate"
                        @submit="updateFilteredTimeEntries"></DateRangePicker>
                </div>
            </MainContainer>
        </div>
        <div class="w-full relative">
            <div v-for="(entry, key) in timeEntries" :key="key">
                <TimeEntryRow
                    :createClient
                    :createProject
                    :projects="projects"
                    :tasks="tasks"
                    :tags="tags"
                    :clients
                    :createTag
                    :updateTimeEntry
                    :onStartStopClick="() => startTimeEntryFromExisting(entry)"
                    :deleteTimeEntry="() => deleteTimeEntries([entry])"
                    :currency="getOrganizationCurrencyString()"
                    :members="members"
                    showDate
                    showMember
                    :time-entry="entry"></TimeEntryRow>
            </div>
        </div>

        <div
            class="flex space-x-5 text-sm font-medium py-8 justify-center items-center">
            <SecondaryButton size="small" disabled>
                <ChevronLeftIcon class="w-4 text-text-tertiary">
                </ChevronLeftIcon>
            </SecondaryButton>
            <span> Page {{ currentPage }} </span>
            <SecondaryButton
                :disabled="!isNextPageAvailable"
                @click="nextPage"
                size="small">
                <ChevronRightIcon
                    class="w-4 text-text-tertiary"></ChevronRightIcon>
            </SecondaryButton>
        </div>
    </AppLayout>
</template>
