<script lang="ts" setup>
import VChart, { THEME_KEY } from "vue-echarts";
import { provide, computed } from "vue";
import { use } from "echarts/core";
import DashboardCard from "@/Components/Dashboard/DashboardCard.vue";
import { BoltIcon } from "@heroicons/vue/20/solid";
import { HeatmapChart } from "echarts/charts";
import {
    CalendarComponent,
    TitleComponent,
    TooltipComponent,
    VisualMapComponent
} from "echarts/components";
import { CanvasRenderer } from "echarts/renderers";
import dayjs from "dayjs";
import {
    firstDayIndex,
    formatDate,
    formatHumanReadableDuration,
    getDayJsInstance
} from "@/packages/ui/src/utils/time";
import { useCssVar } from "@vueuse/core";
import { useQuery } from "@tanstack/vue-query";
import { getCurrentOrganizationId } from "@/utils/useUser";
import { api } from "@/packages/api/src";
import { LoadingSpinner } from "@/packages/ui/src";

// Get the organization ID using the utility function
const organizationId = computed(() => getCurrentOrganizationId());


const { data: dailyHoursTracked, isLoading } = useQuery({
    queryKey: ["dailyTrackedHours", organizationId],
    queryFn: () => {
        return api.dailyTrackedHours({
            params: {
                organization: organizationId.value!
            }
        });
    },
    enabled: computed(() => !!organizationId.value)
});

use([
    TitleComponent,
    TooltipComponent,
    VisualMapComponent,
    CalendarComponent,
    HeatmapChart,
    CanvasRenderer
]);

provide(THEME_KEY, "dark");

const max = computed(() => {
        if (!isLoading.value && dailyHoursTracked.value) {
            return Math.max(
                Math.max(...dailyHoursTracked.value.map((el) => el.duration)),
                1
            );
        } else {
            return 1;
        }
    }
);

const backgroundColor = useCssVar("--color-bg-secondary");
const itemBackgroundColor = useCssVar("--color-bg-tertiary");
const option = computed(() => {
        return {
            tooltip: {},
            visualMap: {
                min: 0,
                max: max.value,
                type: "piecewise",
                orient: "horizontal",
                left: "center",
                top: "center",
                inRange: {
                    color: [itemBackgroundColor.value, "#2DBE45"]
                },
                show: false
            },
            calendar: {
                top: 40,
                bottom: 20,
                left: 40,
                right: 10,
                cellSize: [40, 40],
                dayLabel: {
                    firstDay: firstDayIndex.value
                },
                splitLine: {
                    show: false
                },
                range: [
                    dayjs().format("YYYY-MM-DD"),
                    getDayJsInstance()()
                        .subtract(50, "day")
                        .startOf("week")
                        .format("YYYY-MM-DD")
                ],
                itemStyle: {
                    color: "transparent",
                    borderWidth: 8,
                    borderColor: backgroundColor.value
                },
                yearLabel: { show: false }
            },
            series: {
                type: "heatmap",
                coordinateSystem: "calendar",
                data: dailyHoursTracked?.value?.map((el) => [el.date, el.duration]) ?? [],
                itemStyle: {
                    borderRadius: 5,
                    borderColor: "rgba(255,255,255,0.05)",
                    borderWidth: 1
                },
                tooltip: {
                    valueFormatter: (value: number, dataIndex: number) => {
                        if(dailyHoursTracked?.value){
                            return (
                                formatDate(dailyHoursTracked?.value[dataIndex].date) +
                                ": " +
                                formatHumanReadableDuration(value)
                            );
                        }
                        else {
                            return "";
                        }

                    }
                }
            },
            backgroundColor: "transparent"
        };
    });

</script>

<template>
    <DashboardCard title="Activity Graph" :icon="BoltIcon">
        <div class="px-2">
            <div v-if="isLoading" class="flex justify-center items-center h-40">
                <LoadingSpinner />
            </div>
            <div v-else-if="dailyHoursTracked">
                <v-chart
                    class="chart"
                    :autoresize="true"
                    :option="option"
                    style="height: 260px; background-color: transparent" />
            </div>
            <div v-else class="text-center text-gray-500 py-8">
                No activity data available
            </div>
        </div>
    </DashboardCard>
</template>

<style></style>
