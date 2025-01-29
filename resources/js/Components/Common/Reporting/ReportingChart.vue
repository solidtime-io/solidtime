<script setup lang="ts">
import VChart, { THEME_KEY } from 'vue-echarts';
import { computed, provide, ref } from 'vue';
import LinearGradient from 'zrender/lib/graphic/LinearGradient';
import {
    formatDate,
    formatHumanReadableDuration,
    formatWeek,
} from '@/packages/ui/src/utils/time';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { BarChart } from 'echarts/charts';
import {
    GridComponent,
    LegendComponent,
    TitleComponent,
    TooltipComponent,
} from 'echarts/components';
import type { AggregatedTimeEntries } from '@/packages/api/src';
import { useCssVar } from '@vueuse/core';

use([
    CanvasRenderer,
    BarChart,
    TitleComponent,
    GridComponent,
    TooltipComponent,
    LegendComponent,
]);

provide(THEME_KEY, 'dark');

type GroupedData = AggregatedTimeEntries['grouped_data'];

const props = defineProps<{
    groupedData: GroupedData;
    groupedType: string | null;
}>();

const xAxisLabels = computed(() => {
    if (props.groupedType === 'week') {
        return props?.groupedData?.map((el) => formatWeek(el.key));
    }
    return props?.groupedData?.map((el) => formatDate(el.key ?? ''));
});
const accentColor = useCssVar('--color-accent-quaternary');

const seriesData = computed(() => {
    return props?.groupedData?.map((el) => {
        return {
            value: el.seconds,
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
        data: xAxisLabels,
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
            fontSize: 12,
            fontWeight: 600,
            color: 'rgba(255,255,255,0.7)',
            margin: 16,
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
    <div class="w-[calc(100%-1px)]">
        <v-chart
            v-if="groupedData && groupedData?.length > 0"
            :autoresize="true"
            class="chart"
            :option="option" />
        <div v-else class="chart flex flex-col items-center justify-center">
            <p class="text-lg text-white font-semibold">
                No time entries found
            </p>
            <p>Try to change the filters and time range</p>
        </div>
    </div>
</template>

<style scoped>
.chart {
    height: 300px;
    background: transparent;
}
</style>
