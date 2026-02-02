<script setup lang="ts">
import VChart from 'vue-echarts';
import { computed } from 'vue';
import { useCssVariable } from '@/utils/useCssVariable';

const props = defineProps<{
    history: number[];
}>();

const accentColor = useCssVariable('--theme-color-chart');

const seriesData = computed(() =>
    props.history.map((el) => {
        return {
            value: el,
            ...{
                itemStyle: {
                    borderWidth: 1,
                    borderColor: 'rgba(' + accentColor.value + ',0.8)',
                    borderRadius: [2, 2, 0, 0],
                    color: 'rgba(' + accentColor.value + ',0.8)',
                },
            },
        };
    })
);
const option = computed(() => ({
    grid: {
        top: 0,
        right: 0,
        left: 0,
        bottom: 0,
    },
    backgroundColor: 'transparent',
    xAxis: {
        type: 'category',
        data: ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'],
        show: false,
    },
    yAxis: {
        type: 'value',
        show: false,
    },
    series: [
        {
            data: seriesData.value,
            type: 'bar',
        },
    ],
}));
</script>

<template>
    <v-chart style="height: 20px; width: 80px" class="chart" :option="option" />
</template>

<style scoped></style>
