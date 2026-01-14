<script setup lang="ts">
import ReportingRow from '@/Components/Common/Reporting/ReportingRow.vue';
import ReportingGroupBySelect from '@/Components/Common/Reporting/ReportingGroupBySelect.vue';
import {
    formatHumanReadableDuration,
    getDayJsInstance,
    getLocalizedDayJs,
} from '@/packages/ui/src/utils/time';
import { formatCents } from '@/packages/ui/src/utils/money';
import { getOrganizationCurrencyString } from '@/utils/money';
import { type GroupingOption, useReportingStore } from '@/utils/useReporting';
import { getCurrentMembershipId, getCurrentOrganizationId, getCurrentRole } from '@/utils/useUser';
import {
    api,
    type AggregatedTimeEntries,
    type AggregatedTimeEntriesQueryParams,
    type Organization,
} from '@/packages/api/src';
import { useQuery } from '@tanstack/vue-query';
import { useStorage } from '@vueuse/core';
import { computed, inject, type ComputedRef, watch } from 'vue';

const organization = inject<ComputedRef<Organization>>('organization');

const group = useStorage<GroupingOption>('dashboard-reporting-group', 'project');
const subGroup = useStorage<GroupingOption>('dashboard-reporting-sub-group', 'task');

const reportingStore = useReportingStore();
const { groupByOptions, getNameForReportingRowEntry } = reportingStore;

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

const organizationId = computed(() => getCurrentOrganizationId());

const weekStartUtc = computed(() => {
    return getLocalizedDayJs(getDayJsInstance()().format())
        .startOf('week')
        .startOf('day')
        .utc()
        .format();
});

const weekEndUtc = computed(() => {
    return getLocalizedDayJs(getDayJsInstance()().format()).endOf('day').utc().format();
});

const queryParams = computed<AggregatedTimeEntriesQueryParams>(() => {
    return {
        start: weekStartUtc.value,
        end: weekEndUtc.value,
        group: group.value,
        sub_group: subGroup.value,
        member_id: getCurrentRole() === 'employee' ? getCurrentMembershipId() : undefined,
    };
});

const { data: reportingResponse, isLoading } = useQuery({
    queryKey: [
        'dashboardThisWeekReporting',
        organizationId,
        weekStartUtc,
        weekEndUtc,
        group,
        subGroup,
    ],
    queryFn: () => {
        return api.getAggregatedTimeEntries({
            params: {
                organization: organizationId.value!,
            },
            queries: queryParams.value,
        });
    },
    enabled: computed(() => !!organizationId.value),
});

const aggregatedTableTimeEntries = computed<AggregatedTimeEntries | null>(() => {
    return (reportingResponse.value?.data as AggregatedTimeEntries | undefined) ?? null;
});

const tableData = computed(() => {
    return (
        aggregatedTableTimeEntries.value?.grouped_data?.map((entry) => {
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
                            description: getNameForReportingRowEntry(
                                el.key,
                                entry.grouped_type ?? null
                            ),
                        };
                    }) ?? [],
            };
        }) ?? []
    );
});
</script>

<template>
    <div class="rounded-lg bg-card-background border border-card-border">
        <div
            class="text-sm flex text-text-primary pt-3 items-center space-x-3 font-medium px-6 border-b border-card-background-separator pb-3">
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
                class="contents [&>*]:border-card-background-separator [&>*]:border-b [&>*]:pb-1.5 [&>*]:pt-1 text-text-tertiary text-sm">
                <div class="pl-6">Name</div>
                <div class="text-right">Duration</div>
                <div class="text-right pr-6">Cost</div>
            </div>

            <div v-if="isLoading" class="flex justify-center py-10 col-span-3 text-text-tertiary">
                Loading reporting data…
            </div>

            <template
                v-else-if="
                    aggregatedTableTimeEntries?.grouped_data &&
                    aggregatedTableTimeEntries.grouped_data?.length > 0
                ">
                <ReportingRow
                    v-for="entry in tableData"
                    :key="entry.description ?? 'none'"
                    :currency="getOrganizationCurrencyString()"
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

            <div v-else class="chart flex flex-col items-center justify-center py-12 col-span-3">
                <p class="text-lg text-text-primary font-medium">No time entries found</p>
                <p>Try to track some time entries this week</p>
            </div>
        </div>
    </div>
</template>

<style scoped></style>
