<script setup lang="ts">
import { defineProps, ref, watch } from 'vue';
import TimePicker from '@/Components/Common/TimePicker.vue';
import { useFocusWithin } from '@vueuse/core';
import DatePicker from '@/Components/Common/DatePicker.vue';
import { getDayJsInstance, getLocalizedDayJs } from '@/utils/time';
import dayjs from 'dayjs';

const props = defineProps<{
    start: string;
    end: string | null;
}>();

// The timestamps for the changed event are UTC
const emit = defineEmits(['changed']);

const tempStart = ref(
    props.start ? getLocalizedDayJs(props.start).format() : dayjs().format()
);
const tempEnd = ref(props.end ? getLocalizedDayJs(props.end).format() : null);

watch(props, () => {
    tempStart.value = getLocalizedDayJs(props.start).format();
    tempEnd.value = getLocalizedDayJs(props.end).format();
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
    <div
        ref="dropdownContent"
        class="grid grid-cols-2 divide-x divide-card-background-separator text-center py-2">
        <div class="px-2">
            <div class="font-bold text-white text-sm pb-2">Start</div>
            <div class="space-y-1">
                <TimePicker
                    data-testid="time_entry_range_start"
                    focus
                    @changed="updateTimeEntry"
                    v-model="tempStart"></TimePicker>
                <DatePicker
                    class="text-sm px-2 py-1"
                    @changed="updateTimeEntry"
                    v-model="tempStart"></DatePicker>
            </div>
        </div>
        <div class="px-2">
            <div class="font-bold text-white text-sm pb-2">End</div>
            <div v-if="tempEnd !== null" class="space-y-1">
                <TimePicker
                    data-testid="time_entry_range_end"
                    @changed="updateTimeEntry"
                    v-model="tempEnd"></TimePicker>
                <DatePicker
                    class="text-sm px-2 py-1"
                    @changed="updateTimeEntry"
                    v-model="tempEnd"></DatePicker>
            </div>
            <div class="text-muted" v-else>-- : --</div>
        </div>
    </div>
</template>

<style></style>
