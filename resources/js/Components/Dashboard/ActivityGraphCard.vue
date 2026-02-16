<script lang="ts" setup>
import VChart, { THEME_KEY } from 'vue-echarts';
import { provide, computed, inject, ref, type ComputedRef } from 'vue';
import { use } from 'echarts/core';
import { useElementSize } from '@vueuse/core';
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
import {
    firstDayIndex,
    formatDate,
    formatHumanReadableDuration,
    getDayJsInstance,
} from '@/packages/ui/src/utils/time';
import chroma from 'chroma-js';
import { useCssVariable } from '@/utils/useCssVariable';
import { useQuery } from '@tanstack/vue-query';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { api, type Organization } from '@/packages/api/src';
import { LoadingSpinner } from '@/packages/ui/src';

const organization = inject<ComputedRef<Organization>>('organization');

// Get the organization ID using the utility function
const organizationId = computed(() => getCurrentOrganizationId());

const { data: dailyHoursTracked, isLoading } = useQuery({
    queryKey: ['dailyTrackedHours', organizationId],
    queryFn: () => {
        return api.dailyTrackedHours({
            params: {
                organization: organizationId.value!,
            },
        });
    },
    enabled: computed(() => !!organizationId.value),
});

use([
    TitleComponent,
    TooltipComponent,
    VisualMapComponent,
    CalendarComponent,
    HeatmapChart,
    CanvasRenderer,
]);

provide(THEME_KEY, 'dark');

const max = computed(() => {
    if (!isLoading.value && dailyHoursTracked.value) {
        return Math.max(Math.max(...dailyHoursTracked.value.map((el) => el.duration)), 1);
    } else {
        return 1;
    }
});

const backgroundColor = useCssVariable('--theme-color-card-background');
const borderColor = useCssVariable('--color-border');
const labelColor = useCssVariable('--color-text-secondary');
const chartColorRaw = useCssVariable('--theme-color-chart');

const chartEmptyColorRaw = useCssVariable('--color-bg-tertiary');
const chartEmptyColor = computed(() => {
    if (!chartEmptyColorRaw.value) return '#2a2c32';
    return chroma(chartEmptyColorRaw.value).hex();
});
const chartColor = computed(() => {
    if (!chartColorRaw.value) return '#bae6fd';
    return `rgb(${chartColorRaw.value})`;
});

// Track chart container size
const chartContainer = ref<HTMLElement | null>(null);
const { width: containerWidth } = useElementSize(chartContainer);

// Calculate number of weeks based on available width
// Rough estimate: 40px per cell + 80px for labels = ~360px for 7 weeks
const numberOfWeeks = computed(() => {
    const availableWidth = containerWidth.value || 400;
    const minCellSize = 25; // Minimum cell size in pixels
    const labelSpace = 80; // Space for day labels
    const usableWidth = availableWidth - labelSpace;
    const maxWeeks = Math.floor(usableWidth / minCellSize);
    // Clamp between 4 and 12 weeks for reasonable display
    return Math.max(4, Math.min(12, maxWeeks));
});

// Calculate date range based on dynamic number of weeks
const dateRange = computed(() => {
    const today = getDayJsInstance()();
    const startOfWeek = today.startOf('week');
    // Go back (numberOfWeeks - 1) weeks from the start of current week
    const rangeStart = startOfWeek.subtract(numberOfWeeks.value - 1, 'week');
    return [today.format('YYYY-MM-DD'), rangeStart.format('YYYY-MM-DD')];
});

const option = computed(() => {
    return {
        tooltip: {},
        visualMap: {
            type: 'piecewise',
            orient: 'horizontal',
            left: 'center',
            top: 'center',
            pieces: [
                { value: 0, color: chartEmptyColor.value },
                {
                    gt: 0,
                    lte: max.value * 0.25,
                    color: chroma.mix(chartEmptyColor.value, chartColor.value, 0.3).hex(),
                },
                {
                    gt: max.value * 0.25,
                    lte: max.value * 0.5,
                    color: chroma.mix(chartEmptyColor.value, chartColor.value, 0.6).hex(),
                },
                {
                    gt: max.value * 0.5,
                    lte: max.value * 0.75,
                    color: chroma.mix(chartEmptyColor.value, chartColor.value, 0.8).hex(),
                },
                { gt: max.value * 0.75, lte: max.value, color: chartColor.value },
            ],
            show: false,
        },
        calendar: {
            top: 35,
            bottom: 20,
            left: 35,
            right: 5,
            cellSize: 'auto',
            orient: 'horizontal',
            dayLabel: {
                firstDay: firstDayIndex.value,
                color: labelColor.value,
                fontFamily: 'Inter, sans-serif',
            },
            monthLabel: {
                color: labelColor.value,
                fontFamily: 'Inter, sans-serif',
            },
            splitLine: {
                show: false,
            },
            range: dateRange.value,
            itemStyle: {
                color: 'transparent',
                borderWidth: 8,
                borderColor: backgroundColor.value,
            },
            yearLabel: { show: false },
        },
        series: {
            type: 'heatmap',
            coordinateSystem: 'calendar',
            data: dailyHoursTracked?.value?.map((el) => [el.date, el.duration]) ?? [],
            itemStyle: {
                borderRadius: 5,
                borderColor: borderColor.value,
                borderWidth: 1,
            },
            tooltip: {
                valueFormatter: (value: number, dataIndex: number) => {
                    if (dailyHoursTracked?.value) {
                        return (
                            formatDate(
                                dailyHoursTracked?.value[dataIndex]?.date ?? '',
                                organization?.value?.date_format
                            ) +
                            ': ' +
                            formatHumanReadableDuration(
                                value,
                                organization?.value?.interval_format,
                                organization?.value?.number_format
                            )
                        );
                    } else {
                        return '';
                    }
                },
            },
        },
        backgroundColor: 'transparent',
    };
});
</script>

<template>
    <DashboardCard title="Activity Graph" :icon="BoltIcon">
        <div class="px-2">
            <div v-if="isLoading" class="flex justify-center items-center h-40">
                <LoadingSpinner />
            </div>
            <div v-else-if="dailyHoursTracked" ref="chartContainer">
                <v-chart
                    class="chart"
                    :autoresize="true"
                    :option="option"
                    style="height: 260px; background-color: transparent" />
            </div>
            <div v-else class="text-center text-gray-500 py-8">No activity data available</div>
        </div>
    </DashboardCard>
</template>

<style></style>
