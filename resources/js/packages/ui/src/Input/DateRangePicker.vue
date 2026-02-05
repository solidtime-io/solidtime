<script setup lang="ts">
import { Popover, PopoverContent, PopoverTrigger } from '../popover';
import Button from '../Buttons/Button.vue';
import { RangeCalendar } from '../range-calendar';
import { CalendarDate } from '@internationalized/date';
import { CalendarIcon } from 'lucide-vue-next';
import { computed, ref, inject, type ComputedRef, watch } from 'vue';
import { twMerge } from 'tailwind-merge';
import {
    getDayJsInstance,
    getLocalizedDayJs,
    firstDayIndex,
    type WeekStartDay,
} from '@/packages/ui/src/utils/time';
import { type Organization } from '@/packages/api/src';
import { getUserTimezone } from '@/packages/ui/src/utils/settings';
import { formatDate } from '@/packages/ui/src/utils/time';

const weekStartsOn = computed((): WeekStartDay => firstDayIndex.value as WeekStartDay);

const props = defineProps<{
    start: string;
    end: string;
}>();

const emit = defineEmits<{
    (e: 'update:start', value: string): void;
    (e: 'update:end', value: string): void;
    (e: 'submit'): void;
}>();

interface CalendarDateRange {
    start: CalendarDate | undefined;
    end: CalendarDate | undefined;
}

const today = computed(() => {
    const now = getDayJsInstance()();
    return new CalendarDate(now.year(), now.month() + 1, now.date());
});

const modelValue = computed<CalendarDateRange>({
    get: () => ({
        start: props.start
            ? new CalendarDate(
                  getLocalizedDayJs(props.start).year(),
                  getLocalizedDayJs(props.start).month() + 1,
                  getLocalizedDayJs(props.start).date()
              )
            : undefined,
        end: props.end
            ? new CalendarDate(
                  getLocalizedDayJs(props.end).year(),
                  getLocalizedDayJs(props.end).month() + 1,
                  getLocalizedDayJs(props.end).date()
              )
            : undefined,
    }),
    set: (newValue) => {
        if (newValue.start) {
            console.log(newValue.start);
            const date = newValue.start.toDate(getUserTimezone());
            emit('update:start', getLocalizedDayJs(date.toString()).format());
        }
        if (newValue.end) {
            const date = newValue.end.toDate(getUserTimezone());
            emit('update:end', getLocalizedDayJs(date.toString()).format());
        }
    },
});

const open = ref(false);

function setToday() {
    emit('update:start', getLocalizedDayJs().startOf('day').format());
    emit('update:end', getLocalizedDayJs().endOf('day').format());
    open.value = false;
}

function setThisWeek() {
    emit('update:start', getLocalizedDayJs().startOf('week').format());
    emit('update:end', getLocalizedDayJs().endOf('week').format());
    open.value = false;
}

function setLastWeek() {
    emit('update:start', getLocalizedDayJs().subtract(1, 'week').startOf('week').format());
    emit('update:end', getLocalizedDayJs().subtract(1, 'week').endOf('week').format());
    open.value = false;
}

function setLast14Days() {
    emit('update:start', getLocalizedDayJs().subtract(14, 'days').format());
    emit('update:end', getLocalizedDayJs().format());
    open.value = false;
}

function setThisMonth() {
    emit('update:start', getLocalizedDayJs().startOf('month').format());
    emit('update:end', getLocalizedDayJs().endOf('month').format());
    open.value = false;
}

function setLastMonth() {
    emit('update:start', getLocalizedDayJs().subtract(1, 'month').startOf('month').format());
    emit('update:end', getLocalizedDayJs().subtract(1, 'month').endOf('month').format());
    open.value = false;
}

function setLast30Days() {
    emit('update:start', getLocalizedDayJs().subtract(30, 'days').format());
    emit('update:end', getLocalizedDayJs().format());
    open.value = false;
}

function setLast90Days() {
    emit('update:start', getDayJsInstance()().subtract(90, 'days').format());
    emit('update:end', getDayJsInstance()().format());
    open.value = false;
}

function setLast12Months() {
    emit('update:start', getLocalizedDayJs().subtract(12, 'months').format());
    emit('update:end', getLocalizedDayJs().format());
    open.value = false;
}

function setThisYear() {
    emit('update:start', getLocalizedDayJs().startOf('year').format());
    emit('update:end', getLocalizedDayJs().endOf('year').format());
    open.value = false;
}

function setLastYear() {
    emit('update:start', getLocalizedDayJs().subtract(1, 'year').startOf('year').format());
    emit('update:end', getLocalizedDayJs().subtract(1, 'year').endOf('year').format());
    open.value = false;
}

const organization = inject<ComputedRef<Organization>>('organization');

watch(open, (value) => {
    if (value === false) {
        emit('submit');
    }
});
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                variant="outline"
                :class="
                    twMerge(
                        'flex w-full items-center justify-between whitespace-nowrap h-[34px] text-start',
                        !modelValue && 'text-muted-foreground'
                    )
                ">
                <CalendarIcon class="-ml-0.5 text-text-quaternary h-4 w-4" />
                <template v-if="modelValue.start">
                    <template v-if="modelValue.end">
                        {{ formatDate(modelValue.start.toString(), organization?.date_format) }}
                        -
                        {{ formatDate(modelValue.end.toString(), organization?.date_format) }}
                    </template>
                    <template v-else>
                        {{ formatDate(modelValue.start.toString(), organization?.date_format) }}
                    </template>
                </template>
                <template v-else> Pick a date </template>
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0">
            <div class="flex divide-x divide-border-secondary">
                <div
                    class="text-text-primary text-sm flex flex-col space-y-0.5 items-start py-2 px-2">
                    <Button variant="ghost" size="sm" class="justify-start" @click="setToday"
                        >Today</Button
                    >
                    <Button variant="ghost" size="sm" class="justify-start" @click="setThisWeek"
                        >This Week</Button
                    >
                    <Button variant="ghost" size="sm" class="justify-start" @click="setLastWeek"
                        >Last Week</Button
                    >
                    <Button variant="ghost" size="sm" class="justify-start" @click="setLast14Days"
                        >Last 14 days</Button
                    >
                    <Button variant="ghost" size="sm" class="justify-start" @click="setThisMonth"
                        >This Month</Button
                    >
                    <Button variant="ghost" size="sm" class="justify-start" @click="setLastMonth"
                        >Last Month</Button
                    >
                    <Button variant="ghost" size="sm" class="justify-start" @click="setLast30Days"
                        >Last 30 days</Button
                    >
                    <Button variant="ghost" size="sm" class="justify-start" @click="setLast90Days"
                        >Last 90 days</Button
                    >
                    <Button variant="ghost" size="sm" class="justify-start" @click="setLast12Months"
                        >Last 12 months</Button
                    >
                    <Button variant="ghost" size="sm" class="justify-start" @click="setThisYear"
                        >This year</Button
                    >
                    <Button variant="ghost" size="sm" class="justify-start" @click="setLastYear"
                        >Last year</Button
                    >
                </div>
                <div class="pl-2">
                    <RangeCalendar
                        v-model="modelValue"
                        initial-focus
                        :number-of-months="2"
                        :max-value="today"
                        :week-starts-on="weekStartsOn" />
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>
