<script setup lang="ts">
import VChart, { THEME_KEY } from 'vue-echarts';
import { provide, inject, type ComputedRef } from 'vue';
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
import { useCssVar } from "@vueuse/core";
import type { Organization } from "@/packages/api/src";

use([
    CanvasRenderer,
    PieChart,
    TitleComponent,
    GridComponent,
    TooltipComponent,
    LegendComponent,
]);

provide(THEME_KEY, 'dark');
const labelColor = useCssVar('--color-text-secondary', null, { observe: true });

const props = defineProps<{
    weeklyProjectOverview: {
        value: number;
        name: string;
        color: string;
    }[];
}>();

const organization = inject<ComputedRef<Organization>>('organization');

const seriesData = props.weeklyProjectOverview.map((el) => {
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
import { computed } from 'vue';

const option = computed(() => ({
    tooltip: {
        trigger: 'item',
    },
    legend: {
        bottom: 'bottom',
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
                    return formatHumanReadableDuration(value, organization?.value?.interval_format, organization?.value?.number_format);
                },
            },
            data: seriesData,
            top: '-45%',
            radius: ['30%', '60%'],
            type: 'pie',
        },
    ],
}));
</script>

<template>
    <v-chart
        class="h-[420px] max-w-[300px] mx-auto bg-transparent"
        :autoresize="true"
        :option="option" />
</template>
