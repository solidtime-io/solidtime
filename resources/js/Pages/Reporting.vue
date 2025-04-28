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
} from '@heroicons/vue/20/solid';
import DateRangePicker from '@/packages/ui/src/Input/DateRangePicker.vue';
import ReportingChart from '@/Components/Common/Reporting/ReportingChart.vue';
import BillableIcon from '@/packages/ui/src/Icons/BillableIcon.vue';
import { computed, onMounted, ref } from 'vue';
import {
    formatHumanReadableDuration,
    getDayJsInstance,
    getLocalizedDayJs,
} from '@/packages/ui/src/utils/time';
import { type GroupingOption, useReportingStore } from '@/utils/useReporting';
import { storeToRefs } from 'pinia';
import TagDropdown from '@/packages/ui/src/Tag/TagDropdown.vue';
import {
    type AggregatedTimeEntriesQueryParams,
    type CreateReportBodyProperties,
    api,
} from '@/packages/api/src';
import ReportingFilterBadge from '@/Components/Common/Reporting/ReportingFilterBadge.vue';
import ProjectMultiselectDropdown from '@/Components/Common/Project/ProjectMultiselectDropdown.vue';
import MemberMultiselectDropdown from '@/Components/Common/Member/MemberMultiselectDropdown.vue';
import TaskMultiselectDropdown from '@/Components/Common/Task/TaskMultiselectDropdown.vue';
import SelectDropdown from '@/packages/ui/src/Input/SelectDropdown.vue';
import ReportingGroupBySelect from '@/Components/Common/Reporting/ReportingGroupBySelect.vue';
import ReportingRow from '@/Components/Common/Reporting/ReportingRow.vue';
import { getOrganizationCurrencyString } from '@/utils/money';
import ReportingPieChart from '@/Components/Common/Reporting/ReportingPieChart.vue';
import {
    getCurrentMembershipId,
    getCurrentOrganizationId,
    getCurrentRole,
} from '@/utils/useUser';
import ClientMultiselectDropdown from '@/Components/Common/Client/ClientMultiselectDropdown.vue';
import { useTagsStore } from '@/utils/useTags';
import { formatCents } from '@/packages/ui/src/utils/money';
import { useSessionStorage, useStorage } from '@vueuse/core';
import ReportingTabNavbar from '@/Components/Common/Reporting/ReportingTabNavbar.vue';
import { useNotificationsStore } from '@/utils/notification';
import ReportingExportButton from '@/Components/Common/Reporting/ReportingExportButton.vue';
import type { ExportFormat } from '@/types/reporting';
import ReportSaveButton from '@/Components/Common/Report/ReportSaveButton.vue';
import { getRandomColorWithSeed } from '@/packages/ui/src/utils/color';
const { handleApiRequestNotifications } = useNotificationsStore();

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

const group = useStorage<GroupingOption>('reporting-group', 'project');
const subGroup = useStorage<GroupingOption>('reporting-sub-group', 'task');

const reportingStore = useReportingStore();

const { aggregatedGraphTimeEntries, aggregatedTableTimeEntries } =
    storeToRefs(reportingStore);

const { groupByOptions } = reportingStore;

function getFilterAttributes(): AggregatedTimeEntriesQueryParams {
    let params: AggregatedTimeEntriesQueryParams = {
        start: getLocalizedDayJs(startDate.value).startOf('day').utc().format(),
        end: getLocalizedDayJs(endDate.value).endOf('day').utc().format(),
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

function updateGraphReporting() {
    const params = getFilterAttributes();
    if (getCurrentRole() === 'employee') {
        params.member_id = getCurrentMembershipId();
    }
    params.fill_gaps_in_time_groups = 'true';
    params.group = getOptimalGroupingOption(startDate.value, endDate.value);
    useReportingStore().fetchGraphReporting(params);
}

function updateTableReporting() {
    const params = getFilterAttributes();
    if (group.value === subGroup.value) {
        const fallbackOption = groupByOptions.find(
            (el) => el.value !== group.value
        );
        if (fallbackOption?.value) {
            subGroup.value = fallbackOption.value;
        }
    }
    if (getCurrentRole() === 'employee') {
        params.member_id = getCurrentMembershipId();
    }
    params.group = group.value;
    params.sub_group = subGroup.value;
    useReportingStore().fetchTableReporting(params);
}

function updateReporting() {
    updateGraphReporting();
    updateTableReporting();
}

function getOptimalGroupingOption(
    startDate: string,
    endDate: string
): 'day' | 'week' | 'month' {
    const diffInDays = getDayJsInstance()(endDate).diff(
        getDayJsInstance()(startDate),
        'd'
    );

    if (diffInDays <= 31) {
        return 'day';
    } else if (diffInDays <= 200) {
        return 'week';
    } else {
        return 'month';
    }
}

onMounted(() => {
    updateGraphReporting();
    updateTableReporting();
});

const { tags } = storeToRefs(useTagsStore());
async function createTag(tag: string) {
    return await useTagsStore().createTag(tag);
}

const reportProperties = computed(() => {
    return {
        ...getFilterAttributes(),
        group: group.value,
        sub_group: subGroup.value,
        history_group: getOptimalGroupingOption(startDate.value, endDate.value),
    } as CreateReportBodyProperties;
});

async function downloadExport(format: ExportFormat) {
    const organizationId = getCurrentOrganizationId();
    if (organizationId) {
        const response = await handleApiRequestNotifications(
            () =>
                api.exportAggregatedTimeEntries({
                    params: {
                        organization: organizationId,
                    },
                    queries: {
                        ...getFilterAttributes(),
                        group: group.value,
                        sub_group: subGroup.value,
                        history_group: getOptimalGroupingOption(
                            startDate.value,
                            endDate.value
                        ),
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
const { getNameForReportingRowEntry, emptyPlaceholder } = useReportingStore();
import { useProjectsStore } from '@/utils/useProjects';
import ReportingExportModal from '@/Components/Common/Reporting/ReportingExportModal.vue';
const projectsStore = useProjectsStore();
const { projects } = storeToRefs(projectsStore);
const showExportModal = ref(false);
const exportUrl = ref<string | null>(null);

const groupedPieChartData = computed(() => {
    return (
        aggregatedTableTimeEntries.value?.grouped_data?.map((entry) => {
            const name = getNameForReportingRowEntry(
                entry.key,
                aggregatedTableTimeEntries.value?.grouped_type
            );
            let color = getRandomColorWithSeed(entry.key ?? 'none');
            if (
                name &&
                aggregatedTableTimeEntries.value?.grouped_type &&
                emptyPlaceholder[
                    aggregatedTableTimeEntries.value?.grouped_type
                ] === name
            ) {
                color = '#CCCCCC';
            } else if (
                aggregatedTableTimeEntries.value?.grouped_type === 'project'
            ) {
                color =
                    projects.value?.find((project) => project.id === entry.key)
                        ?.color ?? '#CCCCCC';
            }
            return {
                value: entry.seconds,
                name:
                    getNameForReportingRowEntry(
                        entry.key,
                        aggregatedTableTimeEntries.value?.grouped_type
                    ) ?? '',
                color: color,
            };
        }) ?? []
    );
});

const tableData = computed(() => {
    return aggregatedTableTimeEntries.value?.grouped_data?.map((entry) => {
        return {
            seconds: entry.seconds,
            cost: entry.cost,
            description: getNameForReportingRowEntry(
                entry.key,
                aggregatedTableTimeEntries.value?.grouped_type
            ),
            grouped_data:
                entry.grouped_data?.map((el) => {
                    return {
                        seconds: el.seconds,
                        cost: el.cost,
                        description: getNameForReportingRowEntry(
                            el.key,
                            entry.grouped_type
                        ),
                    };
                }) ?? [],
        };
    });
});
</script>

<template>
    <AppLayout
        title="Reporting"
        data-testid="reporting_view"
        class="overflow-hidden">
        <ReportingExportModal
            v-model:show="showExportModal"
            :export-url="exportUrl"></ReportingExportModal>
        <MainContainer
            class="py-3 sm:py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-3 sm:space-x-6">
                <PageTitle :icon="ChartBarIcon" title="Reporting"></PageTitle>
                <ReportingTabNavbar active="reporting"></ReportingTabNavbar>
            </div>
            <div class="flex space-x-2">
                <ReportingExportButton
                    :download="downloadExport"></ReportingExportButton>
                <ReportSaveButton
                    :report-properties="reportProperties"></ReportSaveButton>
            </div>
        </MainContainer>
        <div class="py-2.5 w-full border-b border-default-background-separator">
            <MainContainer
                class="sm:flex space-y-4 sm:space-y-0 justify-between">
                <div
                    class="flex flex-wrap items-center space-y-2 sm:space-y-0 space-x-4">
                    <div class="text-sm font-medium">Filters</div>
                    <MemberMultiselectDropdown
                        v-model="selectedMembers"
                        @submit="updateReporting">
                        <template #trigger>
                            <ReportingFilterBadge
                                :count="selectedMembers.length"
                                :active="selectedMembers.length > 0"
                                title="Members"
                                :icon="UserGroupIcon"></ReportingFilterBadge>
                        </template>
                    </MemberMultiselectDropdown>
                    <ProjectMultiselectDropdown
                        v-model="selectedProjects"
                        @submit="updateReporting">
                        <template #trigger>
                            <ReportingFilterBadge
                                :count="selectedProjects.length"
                                :active="selectedProjects.length > 0"
                                title="Projects"
                                :icon="FolderIcon"></ReportingFilterBadge>
                        </template>
                    </ProjectMultiselectDropdown>
                    <TaskMultiselectDropdown
                        v-model="selectedTasks"
                        @submit="updateReporting">
                        <template #trigger>
                            <ReportingFilterBadge
                                :count="selectedTasks.length"
                                :active="selectedTasks.length > 0"
                                title="Tasks"
                                :icon="CheckCircleIcon"></ReportingFilterBadge>
                        </template>
                    </TaskMultiselectDropdown>
                    <ClientMultiselectDropdown
                        v-model="selectedClients"
                        @submit="updateReporting">
                        <template #trigger>
                            <ReportingFilterBadge
                                :count="selectedClients.length"
                                :active="selectedClients.length > 0"
                                title="Clients"
                                :icon="FolderIcon"></ReportingFilterBadge>
                        </template>
                    </ClientMultiselectDropdown>
                    <TagDropdown
                        v-model="selectedTags"
                        :create-tag
                        :tags="tags"
                        @submit="updateReporting">
                        <template #trigger>
                            <ReportingFilterBadge
                                :count="selectedTags.length"
                                :active="selectedTags.length > 0"
                                title="Tags"
                                :icon="TagIcon"></ReportingFilterBadge>
                        </template>
                    </TagDropdown>

                    <SelectDropdown
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
                        ]"
                        @changed="updateReporting">
                        <template #trigger>
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
                        @submit="updateReporting"></DateRangePicker>
                </div>
            </MainContainer>
        </div>
        <MainContainer>
            <div class="pt-10 w-full px-3 relative">
                <ReportingChart
                    :grouped-type="aggregatedGraphTimeEntries?.grouped_type"
                    :grouped-data="
                        aggregatedGraphTimeEntries?.grouped_data
                    "></ReportingChart>
            </div>
        </MainContainer>
        <MainContainer>
            <div class="sm:grid grid-cols-4 pt-6 items-start">
                <div
                    class="col-span-3 bg-card-background rounded-lg border border-card-border pt-3">
                    <div
                        class="text-sm flex text-text-primary items-center space-x-3 font-medium px-6 border-b border-card-background-separator pb-3">
                        <span>Group by</span>
                        <ReportingGroupBySelect
                            v-model="group"
                            :group-by-options="groupByOptions"
                            @changed="updateTableReporting"></ReportingGroupBySelect>
                        <span>and</span>
                        <ReportingGroupBySelect
                            v-model="subGroup"
                            :group-by-options="
                                groupByOptions.filter(
                                    (el) => el.value !== group
                                )
                            "
                            @changed="updateTableReporting"></ReportingGroupBySelect>
                    </div>
                    <div
                        class="grid items-center"
                        style="grid-template-columns: 1fr 100px 150px">
                        <div
                            class="contents [&>*]:border-card-background-separator [&>*]:border-b [&>*]:bg-tertiary [&>*]:pb-1.5 [&>*]:pt-1 text-text-secondary text-sm">
                            <div class="pl-6">Name</div>
                            <div class="text-right">Duration</div>
                            <div class="text-right pr-6">Cost</div>
                        </div>
                        <template
                            v-if="
                                aggregatedTableTimeEntries?.grouped_data &&
                                aggregatedTableTimeEntries.grouped_data
                                    ?.length > 0
                            ">
                            <ReportingRow
                                v-for="entry in tableData"
                                :key="entry.description ?? 'none'"
                                :entry="entry"
                                :type="
                                    aggregatedTableTimeEntries.grouped_type
                                "></ReportingRow>
                            <div
                                class="contents [&>*]:transition text-text-tertiary [&>*]:h-[50px]">
                                <div class="flex items-center pl-6 font-medium">
                                    <span>Total</span>
                                </div>
                                <div
                                    class="justify-end flex items-center font-medium">
                                    {{
                                        formatHumanReadableDuration(
                                            aggregatedTableTimeEntries.seconds
                                        )
                                    }}
                                </div>
                                <div
                                    class="justify-end pr-6 flex items-center font-medium">
                                    {{
                                        aggregatedTableTimeEntries.cost ?
                                        formatCents(
                                            aggregatedTableTimeEntries.cost,
                                            getOrganizationCurrencyString()
                                        ) : '--'
                                    }}
                                </div>
                            </div>
                        </template>
                        <div
                            v-else
                            class="chart flex flex-col items-center justify-center py-12 col-span-3">
                            <p class="text-lg text-text-primary font-semibold">
                                No time entries found
                            </p>
                            <p>Try to change the filters and time range</p>
                        </div>
                    </div>
                </div>
                <div class="px-2 lg:px-4">
                    <ReportingPieChart
                        :data="groupedPieChartData"></ReportingPieChart>
                </div>
            </div>
        </MainContainer>
    </AppLayout>
</template>
