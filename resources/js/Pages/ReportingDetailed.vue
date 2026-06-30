<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import {
    ChartBarIcon,
    ClockIcon,
    EllipsisVerticalIcon,
    ArrowDownTrayIcon,
    LockClosedIcon,
} from '@heroicons/vue/20/solid';
import Pagination from '@/Components/Common/Pagination.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/packages/ui/src';
import { SecondaryButton } from '@/packages/ui/src';
import { computed, onMounted, ref, watch } from 'vue';
import { getDayJsInstance, getLocalizedDayJs } from '@/packages/ui/src/utils/time';
import { storeToRefs } from 'pinia';
import {
    api,
    type Client,
    type CreateClientBody,
    type CreateProjectBody,
    type Project,
    type TimeEntry,
} from '@/packages/api/src';
import { useTagsQuery } from '@/utils/useTagsQuery';
import { useTagsStore } from '@/utils/useTags';
import { useSessionStorage } from '@vueuse/core';
import TimeEntryRow from '@/packages/ui/src/TimeEntry/TimeEntryRow.vue';
import { useCurrentTimeEntryStore } from '@/utils/useCurrentTimeEntry';
import { useProjectsQuery } from '@/utils/useProjectsQuery';
import { useProjectsStore } from '@/utils/useProjects';
import { useTasksQuery } from '@/utils/useTasksQuery';
import { useClientsQuery } from '@/utils/useClientsQuery';
import { useClientsStore } from '@/utils/useClients';
import { getOrganizationCurrencyString } from '@/utils/money';
import { useMembersQuery } from '@/utils/useMembersQuery';
import { useQueryClient } from '@tanstack/vue-query';
import { getCurrentOrganizationId, getCurrentMembershipId } from '@/utils/useUser';
import ReportingTabNavbar from '@/Components/Common/Reporting/ReportingTabNavbar.vue';
import UpgradeModal from '@/Components/Common/UpgradeModal.vue';
import type { ExportFormat } from '@/types/reporting';
import { useNotificationsStore } from '@/utils/notification';
import TimeEntryMassActionRow from '@/packages/ui/src/TimeEntry/TimeEntryMassActionRow.vue';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import { canCreateProjects, canViewAllTimeEntries } from '@/utils/permissions';
import ReportingExportModal from '@/Components/Common/Reporting/ReportingExportModal.vue';
import ReportingFilterBar from '@/Components/Common/Reporting/ReportingFilterBar.vue';
import { useTimeEntriesReportQuery } from '@/utils/useTimeEntriesReportQuery';
import { useTimeEntriesMutations } from '@/utils/useTimeEntriesMutations';
import { useOrganizationQuery } from '@/utils/useOrganizationQuery';
import type { TagMatchType } from '@/types/reporting';

// TimeEntryRoundingType is now defined in ReportingRoundingControls component
type TimeEntryRoundingType = 'up' | 'down' | 'nearest';

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
const tagMatchType = ref<TagMatchType>('contains');
const billable = ref<'true' | 'false' | null>(null);
const roundingEnabled = ref<boolean>(false);
const roundingType = ref<TimeEntryRoundingType>('nearest');
const roundingMinutes = ref<number>(15);

const { members } = useMembersQuery();
const { organization } = useOrganizationQuery(getCurrentOrganizationId()!);
const pageLimit = 15;

// Watch rounding enabled state to trigger updates
watch(roundingEnabled, () => {
    updateFilteredTimeEntries();
});
const currentPage = ref(1);

function getFilterAttributes() {
    const defaultParams = {
        start: getLocalizedDayJs(startDate.value).startOf('day').utc().format(),
        end: getLocalizedDayJs(endDate.value).endOf('day').utc().format(),
        active: 'false' as 'true' | 'false',
        limit: pageLimit,
        offset: currentPage.value * pageLimit - pageLimit,
    };
    const params = {
        ...defaultParams,
        member_id: !canViewAllTimeEntries() ? getCurrentMembershipId() : undefined,
        member_ids: selectedMembers.value.length > 0 ? selectedMembers.value : undefined,
        project_ids: selectedProjects.value.length > 0 ? selectedProjects.value : undefined,
        task_ids: selectedTasks.value.length > 0 ? selectedTasks.value : undefined,
        client_ids: selectedClients.value.length > 0 ? selectedClients.value : undefined,
        tag_ids: selectedTags.value.length > 0 ? selectedTags.value : undefined,
        tag_match_type: selectedTags.value.length > 0 ? tagMatchType.value : undefined,
        billable: billable.value !== null ? billable.value : undefined,
        rounding_type: roundingEnabled.value ? roundingType.value : undefined,
        rounding_minutes: roundingEnabled.value ? roundingMinutes.value : undefined,
    };
    return params;
}

const currentTimeEntryStore = useCurrentTimeEntryStore();
const { currentTimeEntry } = storeToRefs(currentTimeEntryStore);
const { setActiveState, startLiveTimer } = currentTimeEntryStore;
const { handleApiRequestNotifications } = useNotificationsStore();

const {
    createTimeEntry,
    updateTimeEntry,
    updateTimeEntries: updateTimeEntriesMutation,
    deleteTimeEntries: deleteTimeEntriesMutation,
} = useTimeEntriesMutations();

async function updateTimeEntries(
    ids: string[],
    changes: Parameters<typeof updateTimeEntriesMutation>[0]['changes']
) {
    await updateTimeEntriesMutation({ ids, changes });
}

const { tags } = useTagsQuery();

const filterParams = computed(() => getFilterAttributes());
const { data: timeEntryResponse } = useTimeEntriesReportQuery(filterParams);

const totalPages = computed(() => {
    return timeEntryResponse?.value?.meta?.total ?? 1;
});

async function deleteTimeEntries(timeEntries: TimeEntry[]) {
    await deleteTimeEntriesMutation(timeEntries);
    selectedTimeEntries.value = [];
    await updateFilteredTimeEntries();
}

const timeEntries = computed(() => {
    return timeEntryResponse?.value?.data || [];
});

onMounted(async () => {
    await updateFilteredTimeEntries();
});

const { projects } = useProjectsQuery();
const { tasks } = useTasksQuery();
const { clients } = useClientsQuery();

const selectedTimeEntries = ref<TimeEntry[]>([]);

const showExportModal = ref(false);
const exportUrl = ref<string | null>(null);
const showPremiumModal = ref(false);
const exportLoading = ref(false);

function triggerExport(format: ExportFormat) {
    if (format === 'pdf' && !isAllowedToPerformPremiumAction()) {
        showPremiumModal.value = true;
        return;
    }
    exportLoading.value = true;
    downloadExport(format).finally(() => {
        exportLoading.value = false;
    });
}

async function createTag(name: string) {
    return await useTagsStore().createTag(name);
}

async function createProject(project: CreateProjectBody): Promise<Project | undefined> {
    return await useProjectsStore().createProject(project);
}

async function createClient(body: CreateClientBody): Promise<Client | undefined> {
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
        queryKey: ['timeEntries', 'detailed-report'],
    });
}
watch(currentPage, () => {
    updateFilteredTimeEntries();
});
function deleteSelected() {
    deleteTimeEntries(selectedTimeEntries.value);
}

async function clearSelectionAndState() {
    selectedTimeEntries.value = [];
    await updateFilteredTimeEntries();
}
async function downloadExport(format: ExportFormat) {
    const organizationId = getCurrentOrganizationId();
    if (organizationId) {
        const response = await handleApiRequestNotifications(
            () =>
                api.exportTimeEntries({
                    params: {
                        organization: organizationId,
                    },
                    queries: {
                        ...getFilterAttributes(),
                        format: format,
                    },
                }),
            'Export successful',
            'Export failed'
        );
        if (response?.download_url) {
            showExportModal.value = true;
            exportUrl.value = response.download_url as string;
        }
    }
}
</script>

<template>
    <AppLayout title="Reporting" data-testid="reporting_view" class="overflow-hidden">
        <ReportingExportModal
            v-model:show="showExportModal"
            :export-url="exportUrl"></ReportingExportModal>
        <UpgradeModal v-model:show="showPremiumModal">
            <strong>PDF Reports</strong> are only available in solidtime Professional.
        </UpgradeModal>
        <MainContainer
            class="h-14 sm:h-16 border-b border-default-background-separator flex flex-wrap gap-y-3 justify-between items-center">
            <div class="flex items-center space-x-3 sm:space-x-6">
                <PageTitle :icon="ChartBarIcon" title="Reporting"></PageTitle>
                <ReportingTabNavbar active="detailed" class="hidden sm:flex"></ReportingTabNavbar>
            </div>
            <div class="hidden sm:block">
                <DropdownMenu>
                    <DropdownMenuTrigger as-child>
                        <SecondaryButton :icon="ArrowDownTrayIcon" :loading="exportLoading">
                            Export
                        </SecondaryButton>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                        <DropdownMenuItem @click="triggerExport('pdf')">
                            <div class="flex items-center space-x-2">
                                <span>Export as PDF</span>
                                <LockClosedIcon
                                    v-if="!isAllowedToPerformPremiumAction()"
                                    class="w-3.5 text-text-tertiary" />
                            </div>
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="triggerExport('xlsx')">
                            Export as Excel
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="triggerExport('csv')">
                            Export as CSV
                        </DropdownMenuItem>
                        <DropdownMenuItem @click="triggerExport('ods')">
                            Export as ODS
                        </DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>
            <DropdownMenu>
                <DropdownMenuTrigger as-child class="sm:hidden">
                    <button
                        class="p-1.5 rounded-lg border border-border-tertiary text-text-secondary hover:text-text-primary hover:bg-secondary transition"
                        aria-label="More options">
                        <EllipsisVerticalIcon class="w-5 h-5" />
                    </button>
                </DropdownMenuTrigger>
                <DropdownMenuContent align="end">
                    <DropdownMenuItem @click="triggerExport('pdf')">
                        <div class="flex items-center space-x-2">
                            <span>Export as PDF</span>
                            <LockClosedIcon
                                v-if="!isAllowedToPerformPremiumAction()"
                                class="w-3.5 text-text-tertiary" />
                        </div>
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="triggerExport('xlsx')">
                        Export as Excel
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="triggerExport('csv')">
                        Export as CSV
                    </DropdownMenuItem>
                    <DropdownMenuItem @click="triggerExport('ods')">
                        Export as ODS
                    </DropdownMenuItem>
                </DropdownMenuContent>
            </DropdownMenu>
        </MainContainer>
        <MainContainer class="sm:hidden py-2 border-b border-default-background-separator">
            <ReportingTabNavbar active="detailed"></ReportingTabNavbar>
        </MainContainer>

        <ReportingFilterBar
            v-model:selected-members="selectedMembers"
            v-model:selected-projects="selectedProjects"
            v-model:selected-tasks="selectedTasks"
            v-model:selected-clients="selectedClients"
            v-model:selected-tags="selectedTags"
            v-model:tag-match-type="tagMatchType"
            v-model:billable="billable"
            v-model:rounding-enabled="roundingEnabled"
            v-model:rounding-type="roundingType"
            v-model:rounding-minutes="roundingMinutes"
            v-model:start-date="startDate"
            v-model:end-date="endDate"
            @submit="updateFilteredTimeEntries" />
        <TimeEntryMassActionRow
            :selected-time-entries="selectedTimeEntries"
            :can-create-project="canCreateProjects()"
            :enable-estimated-time="isAllowedToPerformPremiumAction()"
            :delete-selected="deleteSelected"
            :all-selected="selectedTimeEntries.length === timeEntries.length"
            :projects="projects"
            :tasks="tasks"
            :tags="tags"
            :currency="getOrganizationCurrencyString()"
            :clients="clients"
            :organization-billable-rate="organization?.billable_rate ?? null"
            class="border-b border-default-background-separator"
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
        <div class="w-full relative @container">
            <div v-for="entry in timeEntries" :key="entry.id">
                <TimeEntryRow
                    :selected="selectedTimeEntries.some((item) => item.id === entry.id)"
                    :can-create-project="canCreateProjects()"
                    :create-client
                    :create-project
                    :enable-estimated-time="isAllowedToPerformPremiumAction()"
                    :projects="projects"
                    :tasks="tasks"
                    :tags="tags"
                    :clients
                    :create-tag
                    :update-time-entry
                    :on-start-stop-click="() => startTimeEntryFromExisting(entry)"
                    :delete-time-entry="() => deleteTimeEntries([entry])"
                    :currency="getOrganizationCurrencyString()"
                    :organization-billable-rate="organization?.billable_rate ?? null"
                    :duplicate-time-entry="() => createTimeEntry(entry)"
                    :members="members"
                    is-report
                    show-date
                    show-member
                    :time-entry="entry"
                    @selected="selectedTimeEntries.push(entry)"
                    @unselected="
                        selectedTimeEntries = selectedTimeEntries.filter(
                            (item) => item.id !== entry.id
                        )
                    "></TimeEntryRow>
            </div>
            <div v-if="timeEntries.length === 0">
                <div class="text-center pt-12">
                    <ClockIcon class="w-8 text-icon-default inline pb-2"></ClockIcon>
                    <h3 class="text-text-primary font-semibold">No time entries found</h3>
                    <p class="pb-5">Adjust the filters to see more time entries!</p>
                </div>
            </div>
        </div>

        <Pagination v-model:page="currentPage" :total="totalPages" :items-per-page="pageLimit" />
    </AppLayout>
</template>
