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
import { provide, ref } from 'vue';
import StatCard from '@/Components/common/StatCard.vue';
import { ClockIcon } from '@heroicons/vue/20/solid';
import CardTitle from '@/Components/common/CardTitle.vue';
import LinearGradient from 'zrender/lib/graphic/LinearGradient';
import ProjectsChartCard from '@/Components/Dashboard/ProjectsChartCard.vue';
import { formatHumanReadableDuration } from '@/utils/time';
import { formatMoney } from '@/utils/money';

use([
    CanvasRenderer,
    BarChart,
    TitleComponent,
    GridComponent,
    TooltipComponent,
    LegendComponent,
]);

provide(THEME_KEY, 'dark');

const props = defineProps<{
    weeklyProjectOverview: {
        value: number;
        name: string;
        color: string;
    }[];
    totalWeeklyTime: number;
    totalWeeklyBillableTime: number;
    totalWeeklyBillableAmount: {
        value: number;
        currency: string;
    };
    weeklyHistory: {
        date: string;
        duration: number;
    }[];
}>();

const seriesData = props.weeklyHistory.map((el) => {
    return {
        value: el.duration,
        ...{
            itemStyle: {
                borderColor: new LinearGradient(0, 0, 0, 1, [
                    {
                        offset: 0,
                        color: 'rgba(125,156,188,1)',
                    },
                    {
                        offset: 1,
                        color: 'rgba(125,156,188,0.7)',
                    },
                ]),
                borderWidth: 3,
                borderRadius: [12, 12, 0, 0],
                color: new LinearGradient(0, 0, 0, 1, [
                    {
                        offset: 0,
                        color: 'rgba(125,156,188,0.9)',
                    },
                    {
                        offset: 1,
                        color: 'rgba(125,156,188,0.4)',
                    },
                ]),
            },
        },
    };
});
const option = ref({
    grid: {
        top: 0,
        right: 0,
        bottom: 50,
        left: 0,
    },
    backgroundColor: 'transparent',
    xAxis: {
        type: 'category',
        data: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        markLine: {
            lineStyle: {
                color: 'rgba(125,156,188,0.1)',
                type: 'dashed',
            },
        },
        axisLine: {
            lineStyle: {
                color: 'transparent', // Set desired color here
            },
        },
        axisLabel: {
            fontSize: 16,
            fontWeight: 600,
            margin: 24,
            fontFamily: 'Outfit, sans-serif',
        },
        axisTick: {
            lineStyle: {
                color: 'transparent', // Set desired color here
            },
        },
    },
    yAxis: {
        type: 'value',
        splitLine: {
            lineStyle: {
                color: 'rgba(125,156,188,0.2)', // Set desired color here
            },
        },
    },
    series: [
        {
            data: seriesData,
            type: 'bar',
        },
    ],
});
</script>

<template>
    <div class="grid gap-x-6 grid-cols-4">
        <div class="col-span-3">
            <CardTitle
                title="This Week"
                class="pb-8"
                :icon="ClockIcon"></CardTitle>
            <v-chart class="chart" :option="option" />
        </div>
        <div class="space-y-6">
            <StatCard
                title="Total Time"
                :value="formatHumanReadableDuration(props.totalWeeklyTime)" />
            <StatCard
                title="Billable Time"
                :value="
                    formatHumanReadableDuration(props.totalWeeklyBillableTime)
                " />
            <StatCard
                title="Billable Amount"
                :value="
                    formatMoney(
                        props.totalWeeklyBillableAmount.value,
                        props.totalWeeklyBillableAmount.currency
                    )
                " />
            <ProjectsChartCard
                :weekly-project-overview="
                    props.weeklyProjectOverview
                "></ProjectsChartCard>
        </div>
    </div>
</template>

<style scoped>
.chart {
    height: 300px;
    background: transparent;
}
</style>
