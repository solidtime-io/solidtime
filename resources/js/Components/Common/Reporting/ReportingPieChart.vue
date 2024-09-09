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
import { getRandomColorWithSeed } from '@/packages/ui/src/utils/color';
import type { GroupedDataEntries } from '@/packages/api/src';
import { useReportingStore } from '@/utils/useReporting';
import { useProjectsStore } from '@/utils/useProjects';

use([
    CanvasRenderer,
    PieChart,
    TitleComponent,
    GridComponent,
    TooltipComponent,
    LegendComponent,
]);

provide(THEME_KEY, 'dark');

const props = defineProps<{
    data: GroupedDataEntries | null;
    type: string | null;
}>();
const { getNameForReportingRowEntry, emptyPlaceholder } = useReportingStore();
const { projects } = useProjectsStore();

const groupChartData = computed(() => {
    return (
        props?.data?.map((entry) => {
            const name = getNameForReportingRowEntry(entry.key, props.type);
            let color = getRandomColorWithSeed(entry.key ?? 'none');
            if (name && props.type && emptyPlaceholder[props.type] === name) {
                color = '#CCC';
            } else if (props.type === 'project') {
                color =
                    projects.find((project) => project.id === entry.key)
                        ?.color ?? '#CCC';
            }
            return {
                value: entry.seconds,
                name: getNameForReportingRowEntry(entry.key, props.type),
                color: color,
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
                    color: el.color,
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
            top: '-50%',
            type: 'pie',
        },
    ],
});
</script>

<template>
    <v-chart
        class="background-transparent h-[450px]"
        :autoresize="true"
        :option="option" />
</template>

<style scoped></style>
