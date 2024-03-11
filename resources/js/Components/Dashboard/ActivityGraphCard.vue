<script lang="ts" setup>
import VChart, { THEME_KEY } from 'vue-echarts';
import { provide, ref } from 'vue';
import { use } from 'echarts/core';
import DashboardCard from '@/Components/Dashboard/DashboardCard.vue';
import { BoltIcon } from '@heroicons/vue/20/solid';
import { HeatmapChart } from 'echarts/charts';
import {
    CalendarComponent,
    TitleComponent,
    TooltipComponent,
    VisualMapComponent,
} from 'echarts/components';
import { CanvasRenderer } from 'echarts/renderers';
import dayjs from 'dayjs';

const props = defineProps<{
    dailyHoursTracked: [string, number][];
}>();

use([
    TitleComponent,
    TooltipComponent,
    VisualMapComponent,
    CalendarComponent,
    HeatmapChart,
    CanvasRenderer,
]);

provide(THEME_KEY, 'dark');

const max = Math.max(...props.dailyHoursTracked.map((el) => el[1]));

const option = ref({
    tooltip: {},
    visualMap: {
        min: 0,
        max: max,
        type: 'piecewise',
        orient: 'horizontal',
        left: 'center',
        top: 'center',
        inRange: {
            color: ['#242940', '#2DBE45'],
        },
        show: false,
    },
    calendar: {
        top: 40,
        bottom: 20,
        left: 40,
        right: 10,
        cellSize: [40, 40],
        splitLine: {
            show: false,
        },
        range: [
            dayjs().format('YYYY-MM-DD'),
            dayjs().subtract(50, 'day').startOf('week').format('YYYY-MM-DD'),
        ],
        itemStyle: {
            borderWidth: 8,
            borderColor: '#13152B',
        },
        yearLabel: { show: false },
    },
    series: {
        type: 'heatmap',
        coordinateSystem: 'calendar',
        data: props.dailyHoursTracked,
        itemStyle: {
            borderRadius: 5,
        },
    },
    backgroundColor: 'transparent',
});
</script>

<template>
    <DashboardCard title="Activity Graph" :icon="BoltIcon">
        <div class="px-2">
            <v-chart class="chart" :option="option" style="height: 310px" />
        </div>
    </DashboardCard>
</template>

<style></style>
