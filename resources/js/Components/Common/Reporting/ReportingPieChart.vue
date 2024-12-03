<script setup lang="ts">
import VChart, { THEME_KEY } from 'vue-echarts';
import { computed, provide, ref } from 'vue';
import { use } from 'echarts/core';
import { CanvasRenderer } from 'echarts/renderers';
import { PieChart } from 'echarts/charts';
import {
    GridComponent,
    LegendComponent,
    TitleComponent,
    TooltipComponent,
} from 'echarts/components';
import { formatHumanReadableDuration } from '@/packages/ui/src/utils/time';

use([
    CanvasRenderer,
    PieChart,
    TitleComponent,
    GridComponent,
    TooltipComponent,
    LegendComponent,
]);

provide(THEME_KEY, 'dark');

type ReportingChartDataEntry = {
    value: number;
    name: string;
    color: string;
}[];

const props = defineProps<{
    data: ReportingChartDataEntry | null;
}>();

const seriesData = computed(() => {
    return props.data?.map((el) => {
        return {
            ...el,
            ...{
                itemStyle: {
                    color: `${el.color}BB`,
                },
                emphasis: {
                    itemStyle: {
                        color: `${el.color}`,
                    },
                },
            },
        };
    });
});
const option = ref({
    tooltip: {
        trigger: 'item',
    },
    legend: {
        show: true,
        top: '250px',
    },
    backgroundColor: 'transparent',
    series: [
        {
            label: {
                show: false,
            },
            tooltip: {
                valueFormatter: (value: number) => {
                    return formatHumanReadableDuration(value);
                },
            },
            data: seriesData,
            radius: ['30%', '60%'],
            top: '-45%',
            type: 'pie',
        },
    ],
});
</script>

<template>
    <v-chart
        class="background-transparent max-w-[300px] mx-auto h-[460px]"
        :autoresize="true"
        :option="option" />
</template>

<style scoped></style>
