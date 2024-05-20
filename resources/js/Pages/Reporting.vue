<script setup lang="ts">
import MainContainer from '@/Pages/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { FolderIcon } from '@heroicons/vue/16/solid';
import PageTitle from '@/Components/Common/PageTitle.vue';
import {
    ChartBarIcon,
    UserGroupIcon,
    CheckCircleIcon,
    TagIcon,
} from '@heroicons/vue/20/solid';
import DateRangePicker from '@/Components/Common/DateRangePicker.vue';
import ReportingChart from '@/Components/Common/Reporting/ReportingChart.vue';
import BillableIcon from '@/Components/Common/Icons/BillableIcon.vue';
import { onMounted, ref } from 'vue';
import { formatHumanReadableDuration, getDayJsInstance } from '@/utils/time';
import { useReportingStore } from '@/utils/useReporting';
import { storeToRefs } from 'pinia';
import TagDropdown from '@/Components/Common/Tag/TagDropdown.vue';
import type { AggregatedTimeEntriesQueryParams } from '@/utils/api';
import ReportingFilterBadge from '@/Components/Common/Reporting/ReportingFilterBadge.vue';
import ProjectMultiselectDropdown from '@/Components/Common/Project/ProjectMultiselectDropdown.vue';
import MemberMultiselectDropdown from '@/Components/Common/Member/MemberMultiselectDropdown.vue';
import TaskMultiselectDropdown from '@/Components/Common/Task/TaskMultiselectDropdown.vue';
import SelectDropdown from '@/Components/Common/SelectDropdown.vue';
import ReportingGroupBySelect from '@/Components/Common/Reporting/ReportingGroupBySelect.vue';
import ReportingRow from '@/Components/Common/Reporting/ReportingRow.vue';
import { formatMoney } from '@/utils/money';

const startDate = ref<string | null>(
    getDayJsInstance()().subtract(31, 'd').format('YYYY-MM-DD')
);
const endDate = ref<string | null>(getDayJsInstance()().format('YYYY-MM-DD'));
const selectedTags = ref<string[]>([]);
const selectedProjects = ref<string[]>([]);
const selectedMembers = ref<string[]>([]);
const selectedTasks = ref<string[]>([]);
const billable = ref<'true' | 'false' | null>(null);

type GroupingOption = 'project' | 'task' | 'user' | 'billable' | 'client';

const group = ref<GroupingOption>('project');
const subGroup = ref<GroupingOption>('task');

function getFilterAttributes() {
    let params: AggregatedTimeEntriesQueryParams = {
        after: getDayJsInstance()(startDate.value).utc().format(),
        before: getDayJsInstance()(endDate.value).endOf('day').utc().format(),
    };
    if (selectedMembers.value.length > 0) {
        params = {
            ...params,
            member_ids: selectedMembers.value,
        };
    }
    if (selectedProjects.value.length > 0) {
        params = {
            ...params,
            project_ids: selectedProjects.value,
        };
    }
    if (selectedTasks.value.length > 0) {
        params = {
            ...params,
            task_ids: selectedTasks.value,
        };
    }
    if (selectedTags.value.length > 0) {
        params = {
            ...params,
            tag_ids: selectedTags.value,
        };
    }
    if (billable.value !== null) {
        params = {
            ...params,
            billable: billable.value,
        };
    }
    return params;
}

function updateGraphReporting() {
    const diffInDays = getDayJsInstance()(endDate.value).diff(
        getDayJsInstance()(startDate.value),
        'd'
    );
    const params = getFilterAttributes();
    params.group = getOptimalGroupingOption(diffInDays);
    useReportingStore().fetchGraphReporting(params);
}

function updateTableReporting() {
    const params = getFilterAttributes();
    params.group = group.value;
    params.sub_group = subGroup.value;
    useReportingStore().fetchTableReporting(params);
}

function updateReporting() {
    updateGraphReporting();
    updateTableReporting();
}

const reportingStore = useReportingStore();

const { aggregatedGraphTimeEntries, aggregatedTableTimeEntries } =
    storeToRefs(reportingStore);

function getOptimalGroupingOption(diff: number): 'day' | 'week' | 'month' {
    if (diff <= 31) {
        return 'day';
    } else if (diff <= 365) {
        return 'week';
    } else {
        return 'month';
    }
}

onMounted(() => {
    updateGraphReporting();
    updateTableReporting();
});
</script>

<template>
    <AppLayout title="Reporting" data-testid="reporting_view">
        <MainContainer
            class="py-3 sm:py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-3 sm:space-x-6">
                <PageTitle :icon="ChartBarIcon" title="Reporting"></PageTitle>
            </div>
            <DateRangePicker
                v-model:start="startDate"
                v-model:end="endDate"
                @submit="updateReporting"></DateRangePicker>
        </MainContainer>
        <div class="p-3 w-full border-b border-default-background-separator">
            <MainContainer>
                <div class="flex items-center space-x-4">
                    <div class="text-sm font-medium">Filters</div>
                    <MemberMultiselectDropdown
                        @submit="updateReporting"
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
                        @submit="updateReporting"
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
                        @submit="updateReporting"
                        v-model="selectedTasks">
                        <template v-slot:trigger>
                            <ReportingFilterBadge
                                :count="selectedTasks.length"
                                :active="selectedTasks.length > 0"
                                title="Tasks"
                                :icon="CheckCircleIcon"></ReportingFilterBadge>
                        </template>
                    </TaskMultiselectDropdown>
                    <TagDropdown
                        @submit="updateReporting"
                        v-model="selectedTags">
                        <template v-slot:trigger>
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
            </MainContainer>
        </div>
        <MainContainer>
            <div class="pt-10 w-full px-3 relative">
                <ReportingChart
                    :groupedData="
                        aggregatedGraphTimeEntries?.grouped_data
                    "></ReportingChart>
            </div>
        </MainContainer>
        <MainContainer>
            <div class="grid grid-cols-4 pt-6">
                <div
                    class="col-span-3 bg-card-background rounded-lg border border-card-border pt-3">
                    <div
                        class="text-sm flex text-white items-center space-x-3 font-medium px-6 border-b border-card-background-separator pb-3">
                        <span>Group by</span>
                        <ReportingGroupBySelect
                            @changed="updateTableReporting"
                            v-model="group"></ReportingGroupBySelect>
                        <span>and</span>
                        <ReportingGroupBySelect
                            @changed="updateTableReporting"
                            v-model="subGroup"></ReportingGroupBySelect>
                    </div>
                    <div
                        class="grid items-center"
                        style="grid-template-columns: 1fr 100px 150px">
                        <div
                            class="contents [&>*]:border-card-background-separator [&>*]:border-b [&>*]:bg-tertiary [&>*]:pb-1.5 [&>*]:pt-1 text-muted text-sm">
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
                                v-for="entry in aggregatedTableTimeEntries.grouped_data"
                                :key="entry.key ?? 'none'"
                                :entry="entry"></ReportingRow>
                            <div
                                class="contents [&>*]:transition text-text-tertiary [&>*]:h-[50px]">
                                <div class="flex items-center pl-6 font-medium">
                                    <span>Total</span>
                                </div>
                                <div class="justify-end flex items-center">
                                    {{
                                        formatHumanReadableDuration(
                                            aggregatedTableTimeEntries.seconds
                                        )
                                    }}
                                </div>
                                <div class="justify-end pr-6 flex items-center">
                                    {{
                                        formatMoney(
                                            aggregatedTableTimeEntries.cost
                                        )
                                    }}
                                </div>
                            </div>
                        </template>
                        <div
                            class="chart flex flex-col items-center justify-center py-12 col-span-3"
                            v-else>
                            <p class="text-lg text-white font-semibold">
                                No time entries found
                            </p>
                            <p>Try to change the filters and time range</p>
                        </div>
                    </div>
                </div>
                <div></div>
            </div>
        </MainContainer>
    </AppLayout>
</template>
