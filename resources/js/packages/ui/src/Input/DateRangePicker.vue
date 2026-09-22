<script setup lang="ts">
import { Popover, PopoverContent, PopoverTrigger } from '../popover';
import Button from '../Buttons/Button.vue';
import { RangeCalendar } from '../range-calendar';
import { CalendarDate } from '@internationalized/date';
import { CalendarIcon } from '@lucide/vue';
import { computed, ref, inject, type ComputedRef, watch } from 'vue';
import { twMerge } from 'tailwind-merge';
import DateRangeNavigator from './DateRangeNavigator.vue';
import { getLocalizedDayJs, firstDayIndex, type WeekStartDay } from '@/packages/ui/src/utils/time';
import { getDateRangeLabel, shiftDateRange, type DateRangeDirection } from '../utils/dateRange';
import { type Organization } from '@/packages/api/src';
import { getUserTimezone } from '@/packages/ui/src/utils/settings';

const weekStartsOn = computed((): WeekStartDay => firstDayIndex.value as WeekStartDay);

const props = withDefaults(
    defineProps<{
        start: string;
        end: string;
        allowFuture?: boolean;
    }>(),
    { allowFuture: false }
);

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
    const now = getLocalizedDayJs();
    return new CalendarDate(now.year(), now.month() + 1, now.date());
});

const startDay = computed(() => (props.start ? getLocalizedDayJs(props.start) : undefined));
const endDay = computed(() => (props.end ? getLocalizedDayJs(props.end) : undefined));

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
            const date = newValue.start.toDate(getUserTimezone());
            emit('update:start', getLocalizedDayJs(date.toString()).format());
        }
        if (newValue.end) {
            const date = newValue.end.toDate(getUserTimezone());
            emit('update:end', getLocalizedDayJs(date.toString()).format());
        } else {
            emit('update:end', '');
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
    emit('update:start', getLocalizedDayJs().subtract(13, 'days').startOf('day').format());
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
    emit('update:start', getLocalizedDayJs().subtract(29, 'days').startOf('day').format());
    emit('update:end', getLocalizedDayJs().format());
    open.value = false;
}

function setLast90Days() {
    emit('update:start', getLocalizedDayJs().subtract(89, 'days').startOf('day').format());
    emit('update:end', getLocalizedDayJs().format());
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

const displayLabel = computed(() => {
    if (!startDay.value) return 'Pick a date';
    if (!endDay.value) {
        return getDateRangeLabel(startDay.value, startDay.value, {
            dateFormat: organization?.value?.date_format,
        });
    }
    return getDateRangeLabel(startDay.value, endDay.value, {
        dateFormat: organization?.value?.date_format,
    });
});

const nextRange = computed(() => {
    if (!startDay.value || !endDay.value) return undefined;
    return shiftDateRange(startDay.value, endDay.value, 1);
});

const nextDisabled = computed(
    () =>
        !props.allowFuture &&
        !!nextRange.value &&
        nextRange.value.start.isAfter(getLocalizedDayJs().endOf('day'))
);

function navigate(direction: DateRangeDirection) {
    if (!startDay.value || !endDay.value) return;
    const shifted = shiftDateRange(startDay.value, endDay.value, direction);
    emit('update:start', shifted.start.format());
    emit('update:end', shifted.end.format());
    emit('submit');
}

watch(open, (value) => {
    if (value === false) {
        emit('submit');
    }
});
</script>

<template>
    <DateRangeNavigator
        :label="displayLabel"
        :next-disabled="nextDisabled"
        previous-test-id="date_range_picker_previous"
        trigger-test-id="date_range_picker_display"
        next-test-id="date_range_picker_next"
        @previous="navigate(-1)"
        @next="navigate(1)">
        <template #trigger="{ triggerClass }">
            <Popover v-model:open="open">
                <PopoverTrigger as-child>
                    <Button
                        type="button"
                        variant="ghost"
                        size="sm"
                        :class="twMerge(triggerClass, !modelValue.start && 'text-muted-foreground')"
                        data-testid="date_range_picker_display">
                        <CalendarIcon class="text-text-quaternary h-4 w-4 shrink-0" />
                        <span class="min-w-0 truncate">{{ displayLabel }}</span>
                    </Button>
                </PopoverTrigger>
                <PopoverContent class="w-auto p-0">
                    <div class="flex divide-x divide-border-secondary">
                        <div
                            class="text-text-primary text-sm flex flex-col space-y-0.5 items-start py-2 px-2">
                            <Button
                                variant="ghost"
                                size="sm"
                                class="justify-start"
                                @click="setToday"
                                >Today</Button
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="justify-start"
                                @click="setThisWeek"
                                >This Week</Button
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="justify-start"
                                @click="setLastWeek"
                                >Last Week</Button
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="justify-start"
                                @click="setLast14Days"
                                >Last 14 Days</Button
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="justify-start"
                                @click="setThisMonth"
                                >This Month</Button
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="justify-start"
                                @click="setLastMonth"
                                >Last Month</Button
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="justify-start"
                                @click="setLast30Days"
                                >Last 30 Days</Button
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="justify-start"
                                @click="setLast90Days"
                                >Last 90 Days</Button
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="justify-start"
                                @click="setLast12Months"
                                >Last 12 Months</Button
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="justify-start"
                                @click="setThisYear"
                                >This Year</Button
                            >
                            <Button
                                variant="ghost"
                                size="sm"
                                class="justify-start"
                                @click="setLastYear"
                                >Last Year</Button
                            >
                        </div>
                        <div class="pl-2">
                            <RangeCalendar
                                v-model="modelValue"
                                initial-focus
                                :number-of-months="2"
                                :max-value="allowFuture ? undefined : today"
                                :week-starts-on="weekStartsOn" />
                        </div>
                    </div>
                </PopoverContent>
            </Popover>
        </template>
    </DateRangeNavigator>
</template>
