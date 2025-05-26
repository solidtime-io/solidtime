<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import { ChartBarIcon } from '@heroicons/vue/20/solid';
import ReportingChart from '@/Components/Common/Reporting/ReportingChart.vue';
import { formatHumanReadableDuration } from '@/packages/ui/src/utils/time';
import ReportingRow from '@/Components/Common/Reporting/ReportingRow.vue';
import ReportingPieChart from '@/Components/Common/Reporting/ReportingPieChart.vue';
import { formatCents } from '@/packages/ui/src/utils/money';
import type { CurrencyFormat } from '@/packages/ui/src/utils/money';
import { computed, onMounted, provide, ref } from 'vue';
import { useQuery } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { getRandomColorWithSeed } from '@/packages/ui/src/utils/color';
import { useReportingStore } from '@/utils/useReporting';
import { Head } from '@inertiajs/vue3';
import { useTheme } from '@/utils/theme';

const sharedSecret = ref<string | null>(null);

const hasSharedSecret = computed(() => {
    return sharedSecret.value !== null;
});

const { data: sharedReportResponseData } = useQuery({
    enabled: hasSharedSecret,
    queryKey: ['reporting', sharedSecret],
    queryFn: () =>
        api.getPublicReport({
            headers: {
                'X-Api-Key': sharedSecret.value,
            },
        }),
});

onMounted(() => {
    const currentUrl = window.location.href;
    // check if # exists exactly once in the URL
    if (currentUrl.split('#').length === 2) {
        sharedSecret.value = currentUrl.split('#')[1];
    }
});

const reportCurrency = computed(() => {
    if (sharedReportResponseData.value) {
        return sharedReportResponseData.value?.currency;
    }
    return 'EUR';
});

const reportIntervalFormat = computed(() => {
    return sharedReportResponseData.value?.interval_format;
});

const reportNumberFormat = computed(() => {
    return sharedReportResponseData.value?.number_format;
});

const reportCurrencyFormat = computed(() => {
    return (sharedReportResponseData.value?.currency_format ??
        'symbol-before') as CurrencyFormat;
});

const reportDateFormat = computed(() => {
    return sharedReportResponseData.value?.date_format;
});

const reportCurrencySymbol = computed(() => {
    return sharedReportResponseData.value?.currency_symbol;
});

provide(
    'organization',
    computed(() => ({
        'number_format': reportNumberFormat.value,
        'interval_format': reportIntervalFormat.value,
        'currency_format': reportCurrencyFormat.value,
        'currency_symbol': reportCurrencySymbol.value,
        'date_format': reportDateFormat.value,
    }))
);

const aggregatedTableTimeEntries = computed(() => {
    if (sharedReportResponseData.value) {
        return sharedReportResponseData.value?.data;
    }
    return {
        grouped_data: [],
        grouped_type: 'project',
        seconds: 0,
        cost: 0,
    };
});
const aggregatedGraphTimeEntries = computed(() => {
    if (sharedReportResponseData.value) {
        return sharedReportResponseData.value?.history_data;
    }
    // Placeholder Data
    return {
        grouped_data: [],
        grouped_type: 'project',
        seconds: 0,
        cost: 0,
    };
});

const group = computed(() => {
    if (sharedReportResponseData.value) {
        return sharedReportResponseData.value?.properties.group;
    }
    return 'billable';
});

const subGroup = computed(() => {
    if (sharedReportResponseData.value) {
        return sharedReportResponseData.value?.properties.sub_group;
    }
    return 'project';
});
const { emptyPlaceholder } = useReportingStore();

const groupedPieChartData = computed(() => {
    return (
        aggregatedTableTimeEntries.value?.grouped_data?.map((entry) => {
            if (entry.description === null) {
                return {
                    value: entry.seconds,
                    name: emptyPlaceholder[
                        aggregatedTableTimeEntries.value?.grouped_type ??
                            'project'
                    ],
                    color: '#CCCCCC',
                };
            }
            return {
                value: entry.seconds,
                name: entry.description,
                color:
                    entry.color ??
                    getRandomColorWithSeed(entry.description ?? 'none'),
            };
        }) ?? []
    );
});

const tableData = computed(() => {
    return aggregatedTableTimeEntries.value?.grouped_data?.map((entry) => {
        return {
            seconds: entry.seconds,
            cost: entry.cost,
            description:
                entry.description ??
                emptyPlaceholder[
                    aggregatedTableTimeEntries.value?.grouped_type ?? 'project'
                ],
            grouped_data:
                entry.grouped_data?.map((el) => {
                    return {
                        seconds: el.seconds,
                        cost: el.cost,
                        description:
                            el.description ??
                            emptyPlaceholder[entry.grouped_type ?? 'project'],
                    };
                }) ?? [],
        };
    });
});

const { groupByOptions } = useReportingStore();

function getGroupLabel(key: string) {
    return groupByOptions.find((option) => {
        return option.value === key;
    })?.label;
}

onMounted(async () => {
    useTheme();
});
</script>

<template>
    <Head :title="sharedReportResponseData?.name" />

    <div class="text-text-secondary">
        <MainContainer
            class="py-3 sm:py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-3 sm:space-x-6">
                <PageTitle :icon="ChartBarIcon" title="Reporting"></PageTitle>
            </div>
        </MainContainer>
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
                        class="text-sm flex text-text-primary items-center font-medium px-6 border-b border-card-background-separator pb-3">
                        Group by
                        <strong class="px-2">{{ getGroupLabel(group) }}</strong>
                        and
                        <strong class="px-2">{{
                            getGroupLabel(subGroup)
                        }}</strong>
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
                                :currency="reportCurrency"
                                :currency-format="reportCurrencyFormat"
                                :entry="entry"></ReportingRow>
                            <div
                                class="contents [&>*]:transition text-text-tertiary [&>*]:h-[50px]">
                                <div class="flex items-center pl-6 font-medium">
                                    <span>Total</span>
                                </div>
                                <div
                                    class="justify-end flex items-center font-medium">
                                    {{
                                        formatHumanReadableDuration(
                                            aggregatedTableTimeEntries.seconds,
                                            reportIntervalFormat,
                                            reportNumberFormat
                                        )
                                    }}
                                </div>
                                <div
                                    class="justify-end pr-6 flex items-center font-medium">
                                    {{
                                        formatCents(
                                            aggregatedTableTimeEntries.cost,
                                            reportCurrency,
                                            reportCurrencyFormat,
                                            reportCurrencySymbol
                                        )
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
    </div>
</template>
