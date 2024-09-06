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
import { computed, provide, ref } from 'vue';
import StatCard from '@/Components/Common/StatCard.vue';
import { ClockIcon } from '@heroicons/vue/20/solid';
import CardTitle from '@/packages/ui/src/CardTitle.vue';
import LinearGradient from 'zrender/lib/graphic/LinearGradient';
import ProjectsChartCard from '@/Components/Dashboard/ProjectsChartCard.vue';
import { formatHumanReadableDuration } from '@/packages/ui/src/utils/time';
import { formatCents } from '@/packages/ui/src/utils/money';
import { getWeekStart } from '@/packages/ui/src/utils/settings';
import { useCssVar } from '@vueuse/core';
import { getOrganizationCurrencyString } from '@/utils/money';

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
const accentColor = useCssVar('--color-accent-quaternary');

const seriesData = computed(() => {
    return props.weeklyHistory.map((el) => {
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
        const startIndex = daysOrder.indexOf(dayMapping[getWeekStart()]);

        for (let i = startIndex; i < 7 + startIndex; i++) {
            customOrder.push(daysOrder[i % daysOrder.length]);
        }

        return customOrder;
    } else {
        return daysOrder;
    }
});

const markLineColor = useCssVar('--color-border-secondary');

const option = ref({
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
                color: markLineColor.value,
            },
        },
    },
    series: [
        {
            data: seriesData,
            type: 'bar',
            tooltip: {
                valueFormatter: (value: number) => {
                    return formatHumanReadableDuration(value);
                },
            },
        },
    ],
});
</script>

<template>
    <div
        class="grid space-y-5 sm:space-y-0 sm:gap-x-6 xl:gap-x-6 grid-cols-1 lg:grid-cols-3 xl:grid-cols-4">
        <div class="col-span-2 xl:col-span-3">
            <CardTitle
                title="This Week"
                class="pb-8"
                :icon="ClockIcon"></CardTitle>
            <v-chart :autoresize="true" class="chart" :option="option" />
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
                    formatCents(
                        props.totalWeeklyBillableAmount.value,
                        getOrganizationCurrencyString()
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
    height: 280px;
    background: transparent;
}
</style>
