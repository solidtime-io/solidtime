<script setup lang="ts">
import { CalendarIcon } from '@heroicons/vue/20/solid';
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import DatePicker from '@/packages/ui/src/Input/DatePicker.vue';
import {
    formatDateLocalized,
    getDayJsInstance,
    getLocalizedDayJs,
} from '@/packages/ui/src/utils/time';
import { ref } from 'vue';

const start = defineModel('start', { default: '' });
const end = defineModel('end', { default: '' });

const emit = defineEmits(['submit']);

const open = ref(false);

function setToday() {
    start.value = getLocalizedDayJs().startOf('day').format();
    end.value = getLocalizedDayJs().endOf('day').format();
    emit('submit');
    open.value = false;
}

function setThisWeek() {
    start.value = getLocalizedDayJs().startOf('week').format();
    end.value = getLocalizedDayJs().endOf('week').format();
    emit('submit');
    open.value = false;
}
function setLastWeek() {
    start.value = getLocalizedDayJs()
        .subtract(1, 'week')
        .startOf('week')
        .format();
    end.value = getLocalizedDayJs().subtract(1, 'week').endOf('week').format();
    emit('submit');
    open.value = false;
}
function setLast14Days() {
    start.value = getLocalizedDayJs().subtract(14, 'days').format();
    end.value = getLocalizedDayJs().format();
    emit('submit');
    open.value = false;
}
function setThisMonth() {
    start.value = getLocalizedDayJs().startOf('month').format();
    end.value = getLocalizedDayJs().endOf('month').format();
    emit('submit');
    open.value = false;
}
function setLastMonth() {
    start.value = getLocalizedDayJs()
        .subtract(1, 'month')
        .startOf('month')
        .format();
    end.value = getLocalizedDayJs()
        .subtract(1, 'month')
        .endOf('month')
        .format();
    emit('submit');
    open.value = false;
}
function setLast30Days() {
    start.value = getLocalizedDayJs().subtract(30, 'days').format();
    end.value = getLocalizedDayJs().format();
    emit('submit');
    open.value = false;
}
function setLast90Days() {
    start.value = getDayJsInstance()().subtract(90, 'days').format();
    end.value = getDayJsInstance()().format();
    emit('submit');
    open.value = false;
}
function setLast12Months() {
    start.value = getLocalizedDayJs().subtract(12, 'months').format();
    end.value = getLocalizedDayJs().format();
    emit('submit');
    open.value = false;
}
function setThisYear() {
    start.value = getLocalizedDayJs().startOf('year').format();
    end.value = getLocalizedDayJs().endOf('year').format();
    emit('submit');
    open.value = false;
}
function setLastYear() {
    start.value = getLocalizedDayJs()
        .subtract(1, 'year')
        .startOf('year')
        .format();
    end.value = getLocalizedDayJs().subtract(1, 'year').endOf('year').format();
    emit('submit');
    open.value = false;
}
</script>

<template>
    <Dropdown
        v-model="open"
        :close-on-content-click="false"
        align="bottom-end"
        @submit="emit('submit')">
        <template #trigger>
            <button
                class="px-2 py-1 bg-input-background border border-input-border font-medium rounded-lg flex items-center space-x-2">
                <CalendarIcon class="w-5"></CalendarIcon>
                <div class="text-text-primary">
                    {{ formatDateLocalized(start) }}
                    <span class="px-1.5 text-muted">-</span>
                    {{ formatDateLocalized(end) }}
                </div>
            </button>
        </template>
        <template #content>
            <div class="overflow-hidden w-[330px] px-3 py-1.5">
                <div
                    class="flex divide-x divide-border-secondary justify-between">
                    <div
                        class="text-text-primary text-sm flex flex-col space-y-0.5 items-start py-2 [&_button:hover]:bg-tertiary [&_button]:rounded [&_button]:px-2 [&_button]:py-1">
                        <button @click="setToday">Today</button>
                        <button @click="setThisWeek">This Week</button>
                        <button @click="setLastWeek">Last Week</button>
                        <button @click="setLast14Days">Last 14 days</button>
                        <button @click="setThisMonth">This Month</button>
                        <button @click="setLastMonth">Last Month</button>
                        <button @click="setLast30Days">Last 30 days</button>
                        <button @click="setLast90Days">Last 90 days</button>
                        <button @click="setLast12Months">Last 12 months</button>
                        <button @click="setThisYear">This year</button>
                        <button @click="setLastYear">Last year</button>
                    </div>
                    <div class="pl-5">
                        <div class="space-y-1 flex-col flex items-start">
                            <div class="text-xs font-semibold text-muted">
                                Start Date
                            </div>
                            <DatePicker v-model="start"></DatePicker>
                        </div>
                        <div class="mt-2 space-y-1 flex-col flex items-start">
                            <div class="text-sm font-medium text-muted">
                                End Date
                            </div>
                            <DatePicker v-model="end"></DatePicker>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Dropdown>
</template>

<style scoped></style>
