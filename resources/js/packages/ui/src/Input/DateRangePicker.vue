<script setup lang="ts">
import {
    Popover,
    PopoverContent,
    PopoverTrigger,
} from '@/Components/ui/popover';
import { RangeCalendar } from '@/Components/ui/range-calendar';
import {
    CalendarDate,
    getLocalTimeZone,
} from '@internationalized/date';
import { CalendarIcon } from 'lucide-vue-next';
import { computed, ref, inject, type ComputedRef, watch } from 'vue';
import { twMerge } from 'tailwind-merge';
import {
    getDayJsInstance,
    getLocalizedDayJs,
} from '@/packages/ui/src/utils/time';
import { formatDateLocalized } from '@/packages/ui/src/utils/time';
import { type Organization } from '@/packages/api/src';

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
            const date = newValue.start.toDate(getLocalTimeZone());
            emit('update:start', getDayJsInstance()(date).format('YYYY-MM-DD'));
        }
        if (newValue.end) {
            const date = newValue.end.toDate(getLocalTimeZone());
            emit('update:end', getDayJsInstance()(date).format('YYYY-MM-DD'));
        }
    },
});

const open = ref(false);

function setToday() {
    emit(
        'update:start',
        getLocalizedDayJs().startOf('day').format('YYYY-MM-DD')
    );
    emit('update:end', getLocalizedDayJs().endOf('day').format('YYYY-MM-DD'));
    open.value = false;
}

function setThisWeek() {
    emit(
        'update:start',
        getLocalizedDayJs().startOf('week').format('YYYY-MM-DD')
    );
    emit('update:end', getLocalizedDayJs().endOf('week').format('YYYY-MM-DD'));
    open.value = false;
}

function setLastWeek() {
    emit(
        'update:start',
        getLocalizedDayJs()
            .subtract(1, 'week')
            .startOf('week')
            .format('YYYY-MM-DD')
    );
    emit(
        'update:end',
        getLocalizedDayJs()
            .subtract(1, 'week')
            .endOf('week')
            .format('YYYY-MM-DD')
    );
    open.value = false;
}

function setLast14Days() {
    emit(
        'update:start',
        getLocalizedDayJs().subtract(14, 'days').format('YYYY-MM-DD')
    );
    emit('update:end', getLocalizedDayJs().format('YYYY-MM-DD'));
    open.value = false;
}

function setThisMonth() {
    emit(
        'update:start',
        getLocalizedDayJs().startOf('month').format('YYYY-MM-DD')
    );
    emit('update:end', getLocalizedDayJs().endOf('month').format('YYYY-MM-DD'));
    open.value = false;
}

function setLastMonth() {
    emit(
        'update:start',
        getLocalizedDayJs()
            .subtract(1, 'month')
            .startOf('month')
            .format('YYYY-MM-DD')
    );
    emit(
        'update:end',
        getLocalizedDayJs()
            .subtract(1, 'month')
            .endOf('month')
            .format('YYYY-MM-DD')
    );
    open.value = false;
}

function setLast30Days() {
    emit(
        'update:start',
        getLocalizedDayJs().subtract(30, 'days').format('YYYY-MM-DD')
    );
    emit('update:end', getLocalizedDayJs().format('YYYY-MM-DD'));
    open.value = false;
}

function setLast90Days() {
    emit(
        'update:start',
        getDayJsInstance()().subtract(90, 'days').format('YYYY-MM-DD')
    );
    emit('update:end', getDayJsInstance()().format('YYYY-MM-DD'));
    open.value = false;
}

function setLast12Months() {
    emit(
        'update:start',
        getLocalizedDayJs().subtract(12, 'months').format('YYYY-MM-DD')
    );
    emit('update:end', getLocalizedDayJs().format('YYYY-MM-DD'));
    open.value = false;
}

function setThisYear() {
    emit(
        'update:start',
        getLocalizedDayJs().startOf('year').format('YYYY-MM-DD')
    );
    emit('update:end', getLocalizedDayJs().endOf('year').format('YYYY-MM-DD'));
    open.value = false;
}

function setLastYear() {
    emit(
        'update:start',
        getLocalizedDayJs()
            .subtract(1, 'year')
            .startOf('year')
            .format('YYYY-MM-DD')
    );
    emit(
        'update:end',
        getLocalizedDayJs()
            .subtract(1, 'year')
            .endOf('year')
            .format('YYYY-MM-DD')
    );
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
            <button
                :class="
                    twMerge(
                        'flex w-full items-center justify-between whitespace-nowrap rounded-md border border-input-border bg-input-background px-3 h-[34px] shadow-sm data-[placeholder]:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50 [&>span]:truncate text-start',
                        !modelValue && 'text-muted-foreground'
                    )
                ">
                <CalendarIcon class="mr-2 h-4 w-4" />
                <template v-if="modelValue.start">
                    <template v-if="modelValue.end">
                        {{ formatDateLocalized(modelValue.start.toString(), organization?.date_format) }}
                        -
                        {{ formatDateLocalized(modelValue.end.toString(), organization?.date_format) }}
                    </template>
                    <template v-else>
                        {{ formatDateLocalized(modelValue.start.toString(), organization?.date_format) }}
                    </template>
                </template>
                <template v-else> Pick a date </template>
            </button>
        </PopoverTrigger>
        <PopoverContent class="w-auto p-0">
            <div class="flex divide-x divide-border-secondary">
                <div
                    class="text-text-primary text-sm flex flex-col space-y-0.5 items-start py-2 px-2 [&_button:hover]:bg-tertiary [&_button]:rounded [&_button]:px-2 [&_button]:py-1">
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
                <div class="pl-2">
                    <RangeCalendar
                        v-model="modelValue"
                        initial-focus
                        :number-of-months="2"
                        :max-value="today" />
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>
