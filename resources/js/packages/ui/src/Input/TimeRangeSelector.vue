<script setup lang="ts">
import { defineProps, nextTick, ref, watch } from 'vue';
import { useFocusWithin } from '@vueuse/core';
import DatePicker from '@/packages/ui/src/Input/DatePicker.vue';
import {
    getDayJsInstance,
    getLocalizedDayJs,
} from '@/packages/ui/src/utils/time';
import dayjs from 'dayjs';
import TimePickerSimple from '@/packages/ui/src/Input/TimePickerSimple.vue';

const props = defineProps<{
    start: string;
    end: string | null;
    focus?: boolean;
}>();

// The timestamps for the changed event are UTC
const emit = defineEmits(['changed', 'close']);

const tempStart = ref(
    props.start ? getLocalizedDayJs(props.start).format() : dayjs().format()
);
const tempEnd = ref(props.end ? getLocalizedDayJs(props.end).format() : null);

watch(props, () => {
    tempStart.value = getLocalizedDayJs(props.start).format();
    tempEnd.value = props.end ? getLocalizedDayJs(props.end).format() : null;
});
function updateTimeEntry() {
    const tempStartUtc = getDayJsInstance()(tempStart.value).utc().format();
    const tempEndUtc = tempEnd.value
        ? getDayJsInstance()(tempEnd.value).utc().format()
        : null;
    if (tempStartUtc !== props.start || tempEndUtc !== props.end) {
        emit(
            'changed',
            getDayJsInstance()(tempStart.value).utc().format(),
            getDayJsInstance()(tempEnd.value).utc().format()
        );
    }
}

const dropdownContent = ref();
const { focused } = useFocusWithin(dropdownContent);

watch(focused, (newValue, oldValue) => {
    if (oldValue === true && newValue === false) {
        updateTimeEntry();
    }
});
</script>

<template>
    <form
        ref="dropdownContent"
        class="grid grid-cols-2 divide-x divide-card-background-separator text-center py-2">
        <div class="px-2">
            <div class="font-semibold text-text-primary text-sm pb-2">
                Start
            </div>
            <div class="space-y-2">
                <TimePickerSimple
                    v-model="tempStart"
                    data-testid="time_entry_range_start"
                    tabindex="0"
                    :focus
                    @keydown.enter.prevent="nextTick(() => emit('close'))"
                    @keydown.exact.tab.shift.stop.prevent="emit('close')"
                    @changed="updateTimeEntry"></TimePickerSimple>
            </div>
        </div>
        <div class="px-2">
            <div class="font-semibold text-text-primary text-sm pb-2">End</div>
            <div v-if="tempEnd !== null" class="space-y-2">
                <TimePickerSimple
                    v-model="tempEnd"
                    data-testid="time_entry_range_end"
                    @keydown.enter.prevent="nextTick(() => emit('close'))"
                    @changed="updateTimeEntry"></TimePickerSimple>
            </div>
            <div v-else class="text-text-secondary">-- : --</div>
        </div>
        <div class="px-2 pt-2">
            <DatePicker
                v-model="tempStart"
                size="sm"
                class="text-xs text-text-tertiary max-w-28 px-1.5 py-1.5"
                @changed="updateTimeEntry"></DatePicker>
        </div>
        <div class="px-2 pt-2">
            <DatePicker
                v-if="tempEnd !== null"
                v-model="tempEnd"
                size="sm"
                class="text-xs text-text-tertiary max-w-28 px-1.5 py-1.5"
                @changed="updateTimeEntry"></DatePicker>
        </div>
        <div
            tabindex="0"
            class="focus-visible:outline-none"
            @focusin="emit('close')"></div>
    </form>
</template>

<style></style>
