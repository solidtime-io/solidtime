<script setup lang="ts">
import VChart, { THEME_KEY } from 'vue-echarts';
import { computed, provide, inject, type ComputedRef } from 'vue';
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
import { useCssVariable } from '@/utils/useCssVariable';
import type { Organization } from '@/packages/api/src';

use([
    CanvasRenderer,
    PieChart,
    TitleComponent,
    GridComponent,
    TooltipComponent,
    LegendComponent,
]);

provide(THEME_KEY, 'dark');

const organization = inject<ComputedRef<Organization>>('organization');

type ReportingChartDataEntry = {
    value: number;
    name: string;
    color: string;
}[];

const props = defineProps<{
    data: ReportingChartDataEntry | null;
}>();
const labelColor = useCssVariable('--color-text-secondary');

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
const option = computed(() => ({
    tooltip: {
        trigger: 'item',
    },
    legend: {
        show: true,
        top: '250px',
        textStyle: {
            color: labelColor.value,
        },
    },
    backgroundColor: 'transparent',
    series: [
        {
            label: {
                show: false,
            },
            tooltip: {
                valueFormatter: (value: number) => {
                    return formatHumanReadableDuration(
                        value,
                        organization?.value?.interval_format,
                        organization?.value?.number_format
                    );
                },
            },
            data: seriesData.value,
            radius: ['30%', '60%'],
            top: '-45%',
            type: 'pie',
        },
    ],
}));
</script>

<template>
    <v-chart
        class="background-transparent max-w-[300px] mx-auto h-[460px]"
        :autoresize="true"
        :option="option" />
</template>

<style scoped></style>
