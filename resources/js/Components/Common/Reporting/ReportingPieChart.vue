<script setup lang="ts">
import VChart, { THEME_KEY } from 'vue-echarts';
import { computed, provide, ref } from 'vue';
import LinearGradient from 'zrender/lib/graphic/LinearGradient';
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
import { getRandomColorWithSeed } from '@/packages/ui/src/utils/color';
import type { GroupedDataEntries } from '@/packages/api/src';
import { useReportingStore } from '@/utils/useReporting';

use([
    CanvasRenderer,
    PieChart,
    TitleComponent,
    GridComponent,
    TooltipComponent,
    LegendComponent,
]);

provide(THEME_KEY, 'dark');

function hexToRGBA(hex: string, opacity = 1) {
    // Remove the hash at the start if it's there
    hex = hex.replace(/^#/, '');

    // Parse the hex color
    let r, g, b;
    if (hex.length === 3) {
        r = parseInt(hex.charAt(0) + hex.charAt(0), 16);
        g = parseInt(hex.charAt(1) + hex.charAt(1), 16);
        b = parseInt(hex.charAt(2) + hex.charAt(2), 16);
    } else if (hex.length === 6) {
        r = parseInt(hex.substring(0, 2), 16);
        g = parseInt(hex.substring(2, 4), 16);
        b = parseInt(hex.substring(4, 6), 16);
    } else {
        throw new Error('Invalid HEX color.');
    }

    // Return the RGBA color string
    return `rgba(${r}, ${g}, ${b}, ${opacity})`;
}

const props = defineProps<{
    data: GroupedDataEntries | null;
    type: string | null;
}>();
const { getNameForReportingRowEntry } = useReportingStore();

const groupChartData = computed(() => {
    return (
        props?.data?.map((entry) => {
            return {
                value: entry.seconds,
                name: getNameForReportingRowEntry(entry.key, props.type),
                color: getRandomColorWithSeed(entry.key ?? 'none'),
            };
        }) ?? []
    );
});

const seriesData = computed(() => {
    return groupChartData.value.map((el) => {
        return {
            ...el,
            ...{
                itemStyle: {
                    color: el.color

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
        top: '250px'
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
            top: '-50%',
            type: 'pie',
        },
    ],
});
</script>

<template>
    <v-chart class="background-transparent h-[450px]" :autoresize="true" :option="option" />
</template>

<style scoped>
</style>
