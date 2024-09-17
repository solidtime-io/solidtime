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
import { storeToRefs } from 'pinia';

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
const { projects } = storeToRefs(useProjectsStore());

const groupChartData = computed(() => {
    return (
        props?.data?.map((entry) => {
            const name = getNameForReportingRowEntry(entry.key, props.type);
            let color = getRandomColorWithSeed(entry.key ?? 'none');
            if (name && props.type && emptyPlaceholder[props.type] === name) {
                color = '#CCCCCC';
            } else if (props.type === 'project') {
                color =
                    projects.value?.find((project) => project.id === entry.key)
                        ?.color ?? '#CCCCCC';
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
        class="background-transparent h-[460px]"
        :autoresize="true"
        :option="option" />
</template>

<style scoped></style>
