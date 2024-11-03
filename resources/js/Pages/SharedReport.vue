<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import { ChartBarIcon } from '@heroicons/vue/20/solid';
import ReportingChart from '@/Components/Common/Reporting/ReportingChart.vue';
import { formatHumanReadableDuration } from '@/packages/ui/src/utils/time';
import ReportingRow from '@/Components/Common/Reporting/ReportingRow.vue';
import { getOrganizationCurrencyString } from '@/utils/money';
import ReportingPieChart from '@/Components/Common/Reporting/ReportingPieChart.vue';
import { formatCents } from '@/packages/ui/src/utils/money';
import { computed, onMounted, ref } from 'vue';
import { useQuery } from '@tanstack/vue-query';
import { type AggregatedTimeEntries, api } from '@/packages/api/src';

const sharedSecret = ref<string | null>(null);

const hasSharedSecret = computed(() => {
    return sharedSecret.value !== null;
});

useQuery({
    enabled: hasSharedSecret,
    queryKey: ['reporting', sharedSecret.value],
    queryFn: () => {
        api.getPublicReport({
            headers: {
                'X-Api-Key': sharedSecret.value,
            },
        });
    },
});

onMounted(() => {
    const currentUrl = window.location.href;
    // check if # exists exactly once in the URL
    if (currentUrl.split('#').length === 2) {
        sharedSecret.value = currentUrl.split('#')[1];
    }
});

const aggregatedTableTimeEntries = computed<AggregatedTimeEntries>(() => {
    // Placeholder Data
    return {
        grouped_data: [],
        grouped_type: 'project',
        seconds: 0,
        cost: 0,
    };
});
const aggregatedGraphTimeEntries = computed<AggregatedTimeEntries>(() => {
    // Placeholder Data
    return {
        grouped_data: [],
        grouped_type: 'project',
        seconds: 0,
        cost: 0,
    };
});
const group = ref('billable');
const subGroup = ref('project');
</script>

<template>
    <div class="text-muted">
        <MainContainer
            class="py-3 sm:py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-3 sm:space-x-6">
                <PageTitle :icon="ChartBarIcon" title="Reporting"></PageTitle>
            </div>
        </MainContainer>
        <MainContainer>
            <div class="pt-10 w-full px-3 relative">
                <ReportingChart
                    :groupedType="aggregatedGraphTimeEntries?.grouped_type"
                    :groupedData="
                        aggregatedGraphTimeEntries?.grouped_data
                    "></ReportingChart>
            </div>
        </MainContainer>
        <MainContainer>
            <div class="sm:grid grid-cols-4 pt-6 items-start">
                <div
                    class="col-span-3 bg-card-background rounded-lg border border-card-border pt-3">
                    <div
                        class="text-sm flex text-white items-center space-x-3 font-medium px-6 border-b border-card-background-separator pb-3">
                        <span>Group by</span> {{ group }} <span>and</span>
                        {{ subGroup }}
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
                                        formatCents(
                                            aggregatedTableTimeEntries.cost,
                                            getOrganizationCurrencyString()
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
                <div class="px-2 lg:px-4">
                    <ReportingPieChart
                        :type="aggregatedTableTimeEntries?.grouped_type"
                        :data="
                            aggregatedTableTimeEntries?.grouped_data
                        "></ReportingPieChart>
                </div>
            </div>
        </MainContainer>
    </div>
</template>
