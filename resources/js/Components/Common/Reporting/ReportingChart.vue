<script setup lang="ts">
import VChart, { THEME_KEY } from 'vue-echarts';
import { computed, provide, inject, shallowRef, type ComputedRef } from 'vue';
import LinearGradient from 'zrender/lib/graphic/LinearGradient';
import { formatDate, formatHumanReadableDuration, formatWeek } from '@/packages/ui/src/utils/time';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { BarChart } from 'echarts/charts';
import {
    GridComponent,
    LegendComponent,
    TitleComponent,
    TooltipComponent,
} from 'echarts/components';
import type { AggregatedTimeEntries, Organization } from '@/packages/api/src';
import { useCssVariable } from '@/utils/useCssVariable';

use([CanvasRenderer, BarChart, TitleComponent, GridComponent, TooltipComponent, LegendComponent]);

provide(THEME_KEY, 'dark');

const organization = inject<ComputedRef<Organization>>('organization');
const chart = shallowRef(null);
type GroupedData = AggregatedTimeEntries['grouped_data'];

const props = defineProps<{
    groupedData: GroupedData;
    groupedType: string | null;
}>();

const xAxisLabels = computed(() => {
    if (props.groupedType === 'week') {
        return props?.groupedData?.map((el) => formatWeek(el.key));
    }
    return props?.groupedData?.map((el) =>
        formatDate(el.key ?? '', organization?.value?.date_format)
    );
});
const accentColor = useCssVariable('--theme-color-chart');
const labelColor = useCssVariable('--color-text-secondary');
const markLineColor = useCssVariable('--color-border-secondary');
const splitLineColor = useCssVariable('--color-border-tertiary');

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

const option = computed(() => ({
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
        data: xAxisLabels.value,
        markLine: {
            lineStyle: {
                color: markLineColor.value,
                type: 'dashed',
            },
        },
        axisLine: {
            show: false,
        },
        axisLabel: {
            fontSize: 12,
            fontWeight: 400,
            color: labelColor.value,
            margin: 16,
            fontFamily: 'Inter, sans-serif',
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
                color: splitLineColor.value,
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
}));
</script>

<template>
    <div class="w-[calc(100%-1px)]">
        <v-chart
            v-if="groupedData && groupedData?.length > 0"
            ref="chart"
            :autoresize="true"
            class="chart"
            :option="option" />
        <div v-else class="chart flex flex-col items-center justify-center">
            <p class="text-lg text-text-primary font-semibold">No time entries found</p>
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
