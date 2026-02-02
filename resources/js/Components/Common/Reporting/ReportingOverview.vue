<script setup lang="ts">
import { ChartBarIcon } from '@heroicons/vue/20/solid';
import { getOrganizationCurrencyString } from '@/utils/money';
import {
    formatHumanReadableDuration,
    getDayJsInstance,
    getLocalizedDayJs,
} from '@/packages/ui/src/utils/time';
import { formatCents } from '@/packages/ui/src/utils/money';
import ReportingTabNavbar from '@/Components/Common/Reporting/ReportingTabNavbar.vue';
import ReportingExportButton from '@/Components/Common/Reporting/ReportingExportButton.vue';
import ReportingRow from '@/Components/Common/Reporting/ReportingRow.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import ReportingChart from '@/Components/Common/Reporting/ReportingChart.vue';
import ReportingGroupBySelect from '@/Components/Common/Reporting/ReportingGroupBySelect.vue';
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import ReportingExportModal from '@/Components/Common/Reporting/ReportingExportModal.vue';
import ReportSaveButton from '@/Components/Common/Report/ReportSaveButton.vue';
import ReportingPieChart from '@/Components/Common/Reporting/ReportingPieChart.vue';
import ReportingFilterBar from '@/Components/Common/Reporting/ReportingFilterBar.vue';

import { computed, type ComputedRef, inject, ref, watch } from 'vue';
import { type GroupingOption, useReportingStore } from '@/utils/useReporting';
import {
    type AggregatedTimeEntries,
    type AggregatedTimeEntriesQueryParams,
    api,
    type CreateReportBodyProperties,
    type Organization,
} from '@/packages/api/src';
import { getCurrentMembershipId, getCurrentOrganizationId, getCurrentRole } from '@/utils/useUser';
import { useSessionStorage, useStorage } from '@vueuse/core';
import { useNotificationsStore } from '@/utils/notification';
import type { ExportFormat } from '@/types/reporting';
import { getRandomColorWithSeed } from '@/packages/ui/src/utils/color';
import { useProjectsQuery } from '@/utils/useProjectsQuery';
import { useAggregatedTimeEntriesQuery } from '@/utils/useAggregatedTimeEntriesQuery';

type TimeEntryRoundingType = 'up' | 'down' | 'nearest';

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
const roundingEnabled = ref<boolean>(false);
const roundingType = ref<TimeEntryRoundingType>('nearest');
const roundingMinutes = ref<number>(15);

const group = useStorage<GroupingOption>('reporting-group', 'project');
const subGroup = useStorage<GroupingOption>('reporting-sub-group', 'task');

const reportingStore = useReportingStore();
const { groupByOptions, getNameForReportingRowEntry, emptyPlaceholder } = reportingStore;

const organization = inject<ComputedRef<Organization>>('organization');

// Ensure sub-group falls back when it collides with group
watch(
    group,
    () => {
        if (group.value === subGroup.value) {
            const fallbackOption = groupByOptions.find((el) => el.value !== group.value);
            if (fallbackOption?.value) {
                subGroup.value = fallbackOption.value;
            }
        }
    },
    { immediate: true }
);

function getOptimalGroupingOption(start: string, end: string): 'day' | 'week' | 'month' {
    const diffInDays = getDayJsInstance()(end).diff(getDayJsInstance()(start), 'd');

    if (diffInDays <= 31) {
        return 'day';
    } else if (diffInDays <= 200) {
        return 'week';
    } else {
        return 'month';
    }
}

const filterParams = computed<AggregatedTimeEntriesQueryParams>(() => {
    return {
        start: getLocalizedDayJs(startDate.value).startOf('day').utc().format(),
        end: getLocalizedDayJs(endDate.value).endOf('day').utc().format(),
        member_ids: selectedMembers.value.length > 0 ? selectedMembers.value : undefined,
        project_ids: selectedProjects.value.length > 0 ? selectedProjects.value : undefined,
        task_ids: selectedTasks.value.length > 0 ? selectedTasks.value : undefined,
        client_ids: selectedClients.value.length > 0 ? selectedClients.value : undefined,
        tag_ids: selectedTags.value.length > 0 ? selectedTags.value : undefined,
        billable: billable.value !== null ? billable.value : undefined,
        member_id: getCurrentRole() === 'employee' ? getCurrentMembershipId() : undefined,
        rounding_type: roundingEnabled.value ? roundingType.value : undefined,
        rounding_minutes: roundingEnabled.value ? roundingMinutes.value : undefined,
    };
});

const graphQueryParams = computed<AggregatedTimeEntriesQueryParams>(() => {
    return {
        ...filterParams.value,
        fill_gaps_in_time_groups: 'true',
        group: getOptimalGroupingOption(startDate.value, endDate.value),
    };
});

const tableQueryParams = computed<AggregatedTimeEntriesQueryParams>(() => {
    return {
        ...filterParams.value,
        group: group.value,
        sub_group: subGroup.value,
    };
});

const { data: graphResponse } = useAggregatedTimeEntriesQuery('graph', graphQueryParams);
const { data: tableResponse } = useAggregatedTimeEntriesQuery('table', tableQueryParams);

const aggregatedGraphTimeEntries = computed<AggregatedTimeEntries | undefined>(() => {
    return graphResponse.value?.data as AggregatedTimeEntries | undefined;
});

const aggregatedTableTimeEntries = computed<AggregatedTimeEntries | undefined>(() => {
    return tableResponse.value?.data as AggregatedTimeEntries | undefined;
});

const reportProperties = computed(() => {
    return {
        ...filterParams.value,
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
                        ...filterParams.value,
                        group: group.value,
                        sub_group: subGroup.value,
                        history_group: getOptimalGroupingOption(startDate.value, endDate.value),
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

const { projects } = useProjectsQuery();
const showExportModal = ref(false);
const exportUrl = ref<string | null>(null);

const groupedPieChartData = computed(() => {
    return (
        aggregatedTableTimeEntries.value?.grouped_data?.map((entry) => {
            const name = getNameForReportingRowEntry(
                entry.key,
                aggregatedTableTimeEntries.value?.grouped_type ?? null
            );
            let color = getRandomColorWithSeed(entry.key ?? 'none');
            if (
                name &&
                aggregatedTableTimeEntries.value?.grouped_type &&
                emptyPlaceholder[aggregatedTableTimeEntries.value.grouped_type] === name
            ) {
                color = '#CCCCCC';
            } else if (aggregatedTableTimeEntries.value?.grouped_type === 'project') {
                color =
                    projects.value?.find((project) => project.id === entry.key)?.color ?? '#CCCCCC';
            }
            return {
                value: entry.seconds,
                name:
                    getNameForReportingRowEntry(
                        entry.key,
                        aggregatedTableTimeEntries.value?.grouped_type ?? null
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
                aggregatedTableTimeEntries.value?.grouped_type ?? null
            ),
            grouped_data:
                entry.grouped_data?.map((el) => {
                    return {
                        seconds: el.seconds,
                        cost: el.cost,
                        description: getNameForReportingRowEntry(el.key, entry.grouped_type),
                    };
                }) ?? [],
        };
    });
});
</script>

<template>
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
            <ReportingExportButton :download="downloadExport"></ReportingExportButton>
            <ReportSaveButton :report-properties="reportProperties"></ReportSaveButton>
        </div>
    </MainContainer>
    <ReportingFilterBar
        v-model:selected-members="selectedMembers"
        v-model:selected-projects="selectedProjects"
        v-model:selected-tasks="selectedTasks"
        v-model:selected-clients="selectedClients"
        v-model:selected-tags="selectedTags"
        v-model:billable="billable"
        v-model:rounding-enabled="roundingEnabled"
        v-model:rounding-type="roundingType"
        v-model:rounding-minutes="roundingMinutes"
        v-model:start-date="startDate"
        v-model:end-date="endDate" />
    <MainContainer>
        <div class="pt-10 w-full px-3 relative">
            <ReportingChart
                :grouped-type="aggregatedGraphTimeEntries?.grouped_type ?? null"
                :grouped-data="aggregatedGraphTimeEntries?.grouped_data ?? null"></ReportingChart>
        </div>
    </MainContainer>
    <MainContainer>
        <div class="sm:grid grid-cols-4 pt-6 items-start">
            <div class="col-span-3 bg-secondary rounded-lg border border-card-border pt-3">
                <div
                    class="text-sm flex text-text-primary items-center space-x-3 font-medium px-6 border-b border-card-background-separator pb-3">
                    <span>Group by</span>
                    <ReportingGroupBySelect
                        v-model="group"
                        :group-by-options="groupByOptions"></ReportingGroupBySelect>
                    <span>and</span>
                    <ReportingGroupBySelect
                        v-model="subGroup"
                        :group-by-options="
                            groupByOptions.filter((el) => el.value !== group)
                        "></ReportingGroupBySelect>
                </div>
                <div class="grid items-center" style="grid-template-columns: 1fr 100px 150px">
                    <div
                        class="contents [&>*]:border-card-background-separator [&>*]:border-b [&>*]:bg-secondary [&>*]:pb-1.5 [&>*]:pt-1 text-text-tertiary text-sm">
                        <div class="pl-6">Name</div>
                        <div class="text-right">Duration</div>
                        <div class="text-right pr-6">Cost</div>
                    </div>
                    <template
                        v-if="
                            aggregatedTableTimeEntries?.grouped_data &&
                            aggregatedTableTimeEntries.grouped_data?.length > 0
                        ">
                        <ReportingRow
                            v-for="entry in tableData"
                            :key="entry.description ?? 'none'"
                            :currency="getOrganizationCurrencyString()"
                            :type="aggregatedTableTimeEntries.grouped_type"
                            :entry="entry"></ReportingRow>
                        <div class="contents [&>*]:transition text-text-tertiary [&>*]:h-[50px]">
                            <div class="flex items-center pl-6 font-medium">
                                <span>Total</span>
                            </div>
                            <div class="justify-end flex items-center font-medium">
                                {{
                                    formatHumanReadableDuration(
                                        aggregatedTableTimeEntries.seconds,
                                        organization?.interval_format,
                                        organization?.number_format
                                    )
                                }}
                            </div>
                            <div class="justify-end pr-6 flex items-center font-medium">
                                {{
                                    aggregatedTableTimeEntries.cost
                                        ? formatCents(
                                              aggregatedTableTimeEntries.cost,
                                              getOrganizationCurrencyString(),
                                              organization?.currency_format,
                                              organization?.currency_symbol,
                                              organization?.number_format
                                          )
                                        : '--'
                                }}
                            </div>
                        </div>
                    </template>
                    <div
                        v-else
                        class="chart flex flex-col items-center justify-center py-12 col-span-3">
                        <p class="text-lg text-text-primary font-medium">No time entries found</p>
                        <p>Try to change the filters and time range</p>
                    </div>
                </div>
            </div>
            <div class="px-2 lg:px-4">
                <ReportingPieChart :data="groupedPieChartData"></ReportingPieChart>
            </div>
        </div>
    </MainContainer>
</template>

<style scoped></style>
