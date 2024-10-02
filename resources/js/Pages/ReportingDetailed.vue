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
    ChevronDoubleLeftIcon,
    ChevronRightIcon,
    ChevronDoubleRightIcon,
    PencilSquareIcon,
    TrashIcon,
    ClockIcon,
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
import {
    api,
    type Client,
    type CreateClientBody,
    type CreateProjectBody,
    type Project,
    type TimeEntriesQueryParams,
    type TimeEntry,
    type TimeEntryResponse,
} from '@/packages/api/src';
import ReportingFilterBadge from '@/Components/Common/Reporting/ReportingFilterBadge.vue';
import ProjectMultiselectDropdown from '@/Components/Common/Project/ProjectMultiselectDropdown.vue';
import MemberMultiselectDropdown from '@/Components/Common/Member/MemberMultiselectDropdown.vue';
import TaskMultiselectDropdown from '@/Components/Common/Task/TaskMultiselectDropdown.vue';
import SelectDropdown from '@/packages/ui/src/Input/SelectDropdown.vue';
import ClientMultiselectDropdown from '@/Components/Common/Client/ClientMultiselectDropdown.vue';
import { useTagsStore } from '@/utils/useTags';
import { useSessionStorage } from '@vueuse/core';
import { router } from '@inertiajs/vue3';
import TabBar from '@/Components/Common/TabBar/TabBar.vue';
import TabBarItem from '@/Components/Common/TabBar/TabBarItem.vue';
import TimeEntryRow from '@/packages/ui/src/TimeEntry/TimeEntryRow.vue';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { useProjectsStore } from '@/utils/useProjects';
import { useTasksStore } from '@/utils/useTasks';
import { useClientsStore } from '@/utils/useClients';
import { getOrganizationCurrencyString } from '@/utils/money';
import { useMembersStore } from '@/utils/useMembers';
import {
    PaginationEllipsis,
    PaginationFirst,
    PaginationLast,
    PaginationList,
    PaginationListItem,
    PaginationNext,
    PaginationPrev,
    PaginationRoot,
} from 'radix-vue';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useTimeEntriesStore } from '@/utils/useTimeEntries';
import TimeEntryMassUpdateModal from '@/Components/Common/TimeEntry/TimeEntryMassUpdateModal.vue';

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
const pageLimit = 15;
const currentPage = ref(1);

function getFilterAttributes() {
    let params: TimeEntriesQueryParams = {
        start: getLocalizedDayJs(startDate.value).startOf('day').utc().format(),
        end: getLocalizedDayJs(endDate.value).endOf('day').utc().format(),
        active: 'false',
        limit: pageLimit,
        offset: currentPage.value * pageLimit - pageLimit,
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

const currentTimeEntryStore = useCurrentTimeEntryStore();
const { currentTimeEntry } = storeToRefs(currentTimeEntryStore);
const { setActiveState, startLiveTimer } = currentTimeEntryStore;
const { createTimeEntry, updateTimeEntry } = useTimeEntriesStore();

const { tags } = storeToRefs(useTagsStore());

const { data: timeEntryResponse } = useQuery<TimeEntryResponse>({
    queryKey: ['timeEntry', 'detailed-report'],
    enabled: !!getCurrentOrganizationId(),
    queryFn: () =>
        api.getTimeEntries({
            params: {
                organization: getCurrentOrganizationId() || '',
            },
            queries: getFilterAttributes(),
        }),
});

const totalPages = computed(() => {
    return timeEntryResponse?.value?.meta?.total ?? 1;
});

const timeEntriesStore = useTimeEntriesStore();

async function deleteTimeEntries(timeEntries: TimeEntry[]) {
    for (const timeEntry of timeEntries) {
        await timeEntriesStore.deleteTimeEntry(timeEntry.id);
    }
    selectedTimeEntries.value = [];
    await updateFilteredTimeEntries();
}

const timeEntries = computed(() => {
    return timeEntryResponse?.value?.data || [];
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

const selectedTimeEntries = ref<TimeEntry[]>([]);

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
        await setActiveState(false);
    }
    await createTimeEntry({
        project_id: entry.project_id,
        task_id: entry.task_id,
        start: getDayJsInstance().utc().format(),
        end: null,
        billable: entry.billable,
        description: entry.description,
    });
    startLiveTimer();
    updateFilteredTimeEntries();
    useCurrentTimeEntryStore().fetchCurrentTimeEntry();
}
const queryClient = useQueryClient();
async function updateFilteredTimeEntries() {
    await queryClient.invalidateQueries({
        queryKey: ['timeEntry', 'detailed-report'],
    });
}
watch(currentPage, () => {
    updateFilteredTimeEntries();
});
function deleteSelected() {
    deleteTimeEntries(selectedTimeEntries.value);
}

const showMassUpdateModal = ref(false);
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
        <div class="py-2.5 w-full border-b border-default-background-separator">
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
        <TimeEntryMassUpdateModal
            :time-entries="selectedTimeEntries"
            @submit="updateFilteredTimeEntries"
            v-model:show="showMassUpdateModal"></TimeEntryMassUpdateModal>
        <MainContainer
            class="text-sm py-1.5 font-medium border-b border-t bg-secondary border-border-tertiary flex items-center space-x-3">
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
        <div class="w-full relative">
            <div v-for="entry in timeEntries" :key="entry.id">
                <TimeEntryRow
                    :selected="selectedTimeEntries.includes(entry)"
                    @selected="selectedTimeEntries.push(entry)"
                    @unselected="
                        selectedTimeEntries = selectedTimeEntries.filter(
                            (item) => item.id !== entry.id
                        )
                    "
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
            <div v-if="timeEntries.length === 0">
                <div class="text-center pt-12">
                    <ClockIcon
                        class="w-8 text-icon-default inline pb-2"></ClockIcon>
                    <h3 class="text-white font-semibold">
                        No time entries found
                    </h3>
                    <p class="pb-5">
                        Adjust the filters to see more time entries!
                    </p>
                </div>
            </div>
        </div>

        <PaginationRoot
            :total="totalPages"
            :items-per-page="pageLimit"
            class="flex justify-center items-center py-8"
            v-model:page="currentPage"
            :sibling-count="1"
            show-edges>
            <PaginationList
                v-slot="{ items }"
                class="flex items-center space-x-1 relative">
                <div
                    class="pr-2 flex items-center space-x-1 border-r border-border-primary mr-1">
                    <PaginationFirst class="navigation-item">
                        <ChevronDoubleLeftIcon class="w-4">
                        </ChevronDoubleLeftIcon>
                    </PaginationFirst>
                    <PaginationPrev class="mr-4 navigation-item">
                        <ChevronLeftIcon
                            class="w-4 text-text-tertiary hover:text-text-primary">
                        </ChevronLeftIcon>
                    </PaginationPrev>
                </div>
                <template v-for="(page, index) in items">
                    <PaginationListItem
                        v-if="page.type === 'page'"
                        :key="index"
                        class="pagination-item"
                        :value="page.value">
                        {{ page.value }}
                    </PaginationListItem>
                    <PaginationEllipsis
                        v-else
                        :key="page.type"
                        :index="index"
                        class="PaginationEllipsis">
                        <div class="px-2">&#8230;</div>
                    </PaginationEllipsis>
                </template>
                <div
                    class="!ml-2 pl-2 flex items-center space-x-1 border-l border-border-primary">
                    <PaginationNext class="navigation-item">
                        <ChevronRightIcon
                            class="w-4 text-text-tertiary hover:text-text-primary"></ChevronRightIcon>
                    </PaginationNext>
                    <PaginationLast class="navigation-item">
                        <ChevronDoubleRightIcon
                            class="w-4 text-text-tertiary hover:text-text-primary"></ChevronDoubleRightIcon>
                    </PaginationLast>
                </div>
            </PaginationList>
        </PaginationRoot>
    </AppLayout>
</template>
<style lang="postcss">
.navigation-item {
    @apply bg-quaternary h-8 w-8 flex items-center justify-center rounded border border-border-primary text-text-tertiary hover:text-text-primary transition cursor-pointer hover:border-border-secondary hover:bg-secondary focus-visible:text-text-primary focus-visible:outline-0 focus-visible:ring-2 focus-visible:ring-white/80;
}

.pagination-item {
    @apply bg-secondary h-8 w-8 flex items-center justify-center rounded border border-border-tertiary text-text-secondary hover:text-text-primary transition cursor-pointer hover:border-border-secondary hover:bg-secondary focus-visible:text-text-primary focus-visible:outline-0 focus-visible:ring-2 focus-visible:ring-white/80;
}
.pagination-item[data-selected] {
    @apply text-white bg-accent-300/10 border border-accent-300/20 rounded-md font-medium hover:bg-accent-300/20 active:bg-accent-300/20 outline-0 focus-visible:ring-2 focus:ring-white/80 transition ease-in-out duration-150;
}
</style>
