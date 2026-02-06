<script setup lang="ts">
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { BarChart } from 'echarts/charts';
import {
    GridComponent,
    LegendComponent,
    TitleComponent,
    TooltipComponent,
} from 'echarts/components';
import VChart, { THEME_KEY } from 'vue-echarts';
import { computed, provide, inject, type ComputedRef } from 'vue';
import StatCard from '@/Components/Common/StatCard.vue';
import { ClockIcon } from '@heroicons/vue/20/solid';
import CardTitle from '@/packages/ui/src/CardTitle.vue';
import LinearGradient from 'zrender/lib/graphic/LinearGradient';
import ProjectsChartCard from '@/Components/Dashboard/ProjectsChartCard.vue';
import ThisWeekReportingTable from '@/Components/Dashboard/ThisWeekReportingTable.vue';
import { formatHumanReadableDuration } from '@/packages/ui/src/utils/time';
import { formatCents } from '@/packages/ui/src/utils/money';
import { getWeekStart } from '@/packages/ui/src/utils/settings';
import { useCssVariable } from '@/utils/useCssVariable';
import { getOrganizationCurrencyString } from '@/utils/money';
import { useQuery } from '@tanstack/vue-query';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { api, type Organization } from '@/packages/api/src';

use([CanvasRenderer, BarChart, TitleComponent, GridComponent, TooltipComponent, LegendComponent]);

provide(THEME_KEY, 'dark');

const weekdays = computed(() => {
    const daysOrder = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const dayMapping: Record<string, string> = {
        monday: 'Mon',
        tuesday: 'Tue',
        wednesday: 'Wed',
        thursday: 'Thu',
        friday: 'Fri',
        saturday: 'Sat',
        sunday: 'Sun',
    };
    if (dayMapping[getWeekStart()]) {
        const customOrder = [];
        const startIndex = daysOrder.indexOf(dayMapping[getWeekStart()]!);

        for (let i = startIndex; i < 7 + startIndex; i++) {
            customOrder.push(daysOrder[i % daysOrder.length]!);
        }

        return customOrder;
    } else {
        return daysOrder;
    }
});

const accentColor = useCssVariable('--theme-color-chart');

// Get the organization ID using the utility function
const organizationId = computed(() => getCurrentOrganizationId());

const organization = inject<ComputedRef<Organization>>('organization');

// Set up the queries
const { data: weeklyProjectOverview } = useQuery({
    queryKey: ['weeklyProjectOverview', organizationId],
    queryFn: () => {
        return api.weeklyProjectOverview({
            params: {
                organization: organizationId.value!,
            },
        });
    },
    enabled: computed(() => !!organizationId.value),
    staleTime: 1000 * 30, // 30 seconds
});

const { data: totalWeeklyTime } = useQuery({
    queryKey: ['totalWeeklyTime', organizationId],
    queryFn: () => {
        return api.totalWeeklyTime({
            params: {
                organization: organizationId.value!,
            },
        });
    },
    enabled: computed(() => !!organizationId.value),
    staleTime: 1000 * 30, // 30 seconds
});

const { data: totalWeeklyBillableTime } = useQuery({
    queryKey: ['totalWeeklyBillableTime', organizationId],
    queryFn: () => {
        return api.totalWeeklyBillableTime({
            params: {
                organization: organizationId.value!,
            },
        });
    },
    enabled: computed(() => !!organizationId.value),
    staleTime: 1000 * 30, // 30 seconds
});

const { data: totalWeeklyBillableAmount } = useQuery({
    queryKey: ['totalWeeklyBillableAmount', organizationId],
    queryFn: () => {
        return api.totalWeeklyBillableAmount({
            params: {
                organization: organizationId.value!,
            },
        });
    },
    enabled: computed(() => !!organizationId.value),
    staleTime: 1000 * 30, // 30 seconds
});

const { data: weeklyHistory } = useQuery({
    queryKey: ['weeklyHistory', organizationId],
    queryFn: () => {
        return api.weeklyHistory({
            params: {
                organization: organizationId.value!,
            },
        });
    },
    enabled: computed(() => !!organizationId.value),
    staleTime: 1000 * 30, // 30 seconds
});

const seriesData = computed(() => {
    if (!weeklyHistory.value) {
        return [];
    }
    return weeklyHistory.value?.map((el) => {
        return {
            value: el.duration,
            ...{
                itemStyle: {
                    borderColor: new LinearGradient(0, 0, 0, 1, [
                        {
                            offset: 0,
                            color: 'rgba(' + accentColor.value + ',0.7)',
                        },
                        {
                            offset: 1,
                            color: 'rgba(' + accentColor.value + ',0.5)',
                        },
                    ]),
                    emphasis: {
                        color: new LinearGradient(0, 0, 0, 1, [
                            {
                                offset: 0,
                                color: 'rgba(' + accentColor.value + ',0.9)',
                            },
                            {
                                offset: 1,
                                color: 'rgba(' + accentColor.value + ',0.7)',
                            },
                        ]),
                    },
                    borderRadius: [12, 12, 0, 0],
                    color: new LinearGradient(0, 0, 0, 1, [
                        {
                            offset: 0,
                            color: 'rgba(' + accentColor.value + ',0.7)',
                        },
                        {
                            offset: 1,
                            color: 'rgba(' + accentColor.value + ',0.5)',
                        },
                    ]),
                },
            },
        };
    });
});

const markLineColor = useCssVariable('--color-border-secondary');
const labelColor = useCssVariable('--color-text-secondary');
const option = computed(() => {
    return {
        tooltip: {
            trigger: 'item',
        },
        grid: {
            top: 0,
            right: 0,
            bottom: 50,
            left: 0,
        },
        backgroundColor: 'transparent',
        xAxis: {
            type: 'category',
            data: weekdays.value,
            axisLine: {
                show: false,
            },
            axisLabel: {
                fontSize: 14,
                fontWeight: 500,
                margin: 24,
                fontFamily: 'Inter, sans-serif',
                color: labelColor.value,
            },
            axisTick: {
                show: false,
            },
        },
        yAxis: {
            type: 'value',
            axisLabel: {
                show: false,
            },
            splitLine: {
                lineStyle: {
                    color: markLineColor.value,
                },
            },
        },
        series: [
            {
                data: seriesData.value,
                type: 'bar',
                tooltip: {
                    valueFormatter: (value: number) => {
                        return formatHumanReadableDuration(
                            value,
                            organization?.value?.interval_format,
                            organization?.value?.number_format
                        );
                    },
                },
            },
        ],
    };
});
</script>

<template>
    <div
        class="grid space-y-5 sm:space-y-0 sm:gap-x-6 xl:gap-x-6 grid-cols-1 lg:grid-cols-3 xl:grid-cols-4">
        <div class="col-span-2 xl:col-span-3">
            <CardTitle title="This Week" class="pb-8" :icon="ClockIcon"></CardTitle>
            <v-chart v-if="weeklyHistory" :autoresize="true" class="chart" :option="option" />

            <div class="mt-6">
                <ThisWeekReportingTable></ThisWeekReportingTable>
            </div>
        </div>
        <div class="space-y-6">
            <StatCard
                title="Spent Time"
                :value="
                    totalWeeklyTime
                        ? formatHumanReadableDuration(
                              totalWeeklyTime,
                              organization?.interval_format,
                              organization?.number_format
                          )
                        : '--'
                " />
            <StatCard
                title="Billable Time"
                :value="
                    totalWeeklyBillableTime
                        ? formatHumanReadableDuration(
                              totalWeeklyBillableTime,
                              organization?.interval_format,
                              organization?.number_format
                          )
                        : '--'
                " />
            <StatCard
                title="Billable Amount"
                :value="
                    totalWeeklyBillableAmount
                        ? formatCents(
                              totalWeeklyBillableAmount.value,
                              getOrganizationCurrencyString(),
                              organization?.currency_format,
                              organization?.currency_symbol,
                              organization?.number_format
                          )
                        : '--'
                " />
            <ProjectsChartCard
                v-if="weeklyProjectOverview"
                :weekly-project-overview="weeklyProjectOverview"></ProjectsChartCard>
        </div>
    </div>
</template>

<style scoped>
.chart {
    height: 280px;
    background: transparent;
}
</style>
