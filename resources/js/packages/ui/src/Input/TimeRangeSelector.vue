<script setup lang="ts">
import { nextTick, ref, watch } from 'vue';
import DatePicker from '@/packages/ui/src/Input/DatePicker.vue';
import { getDayJsInstance, getLocalizedDayJs } from '@/packages/ui/src/utils/time';
import dayjs from 'dayjs';
import TimePickerSimple from '@/packages/ui/src/Input/TimePickerSimple.vue';
import { Button } from '@/packages/ui/src/Buttons';

const props = defineProps<{
    start: string;
    end: string | null;
    focus?: boolean;
}>();

// The timestamps for the changed event are UTC
const emit = defineEmits(['changed', 'close']);

const tempStart = ref(props.start ? getLocalizedDayJs(props.start).format() : dayjs().format());
const tempEnd = ref(props.end ? getLocalizedDayJs(props.end).format() : null);
const showEndTimePicker = ref(false);

watch(props, () => {
    tempStart.value = getLocalizedDayJs(props.start).format();
    tempEnd.value = props.end ? getLocalizedDayJs(props.end).format() : null;
    showEndTimePicker.value = false;
});

function updateTimeEntry() {
    const tempStartUtc = getDayJsInstance()(tempStart.value).utc().format();
    const tempEndUtc = tempEnd.value ? getDayJsInstance()(tempEnd.value).utc().format() : null;

    if (tempStartUtc !== props.start || tempEndUtc !== props.end) {
        emit(
            'changed',
            getDayJsInstance()(tempStart.value).utc().format(),
            tempEnd.value ? getDayJsInstance()(tempEnd.value).utc().format() : null
        );
    }
}

function setEndTime() {
    showEndTimePicker.value = true;
    tempEnd.value = getDayJsInstance()().format();
}

function confirmEndTime() {
    // wait for the v-model for the end time to update
    nextTick(() => {
        updateTimeEntry();
        showEndTimePicker.value = false;
        emit('close');
    });
}

const dropdownContent = ref();
</script>

<template>
    <div
        ref="dropdownContent"
        class="grid grid-cols-2 divide-x divide-card-background-separator text-center py-2">
        <div class="px-2" @keydown.enter.prevent="nextTick(() => emit('close'))">
            <div class="font-semibold text-text-primary text-sm pb-2">Start</div>
            <div class="flex flex-col items-center space-y-2 w-28 mx-auto">
                <TimePickerSimple
                    v-model="tempStart"
                    class="w-full"
                    data-testid="time_entry_range_start"
                    tabindex="0"
                    :focus
                    @keydown.exact.tab.shift.stop.prevent="emit('close')"
                    @changed="updateTimeEntry"></TimePickerSimple>
                <DatePicker
                    v-model="tempStart"
                    class="w-full"
                    @changed="updateTimeEntry"></DatePicker>
            </div>
        </div>
        <div class="px-2">
            <div class="font-semibold text-text-primary text-sm pb-2">End</div>
            <div
                v-if="end !== null && tempEnd !== null"
                class="flex flex-col items-center space-y-2 w-28 mx-auto">
                <TimePickerSimple
                    v-model="tempEnd"
                    class="w-full"
                    data-testid="time_entry_range_end"
                    @changed="updateTimeEntry"></TimePickerSimple>
                <DatePicker
                    v-model="tempEnd"
                    class="w-full"
                    @changed="updateTimeEntry"></DatePicker>
            </div>
            <div v-else-if="end === null && !showEndTimePicker">
                <Button variant="outline" size="sm" @click="setEndTime"> Set End Time </Button>
            </div>
            <div
                v-else-if="showEndTimePicker && tempEnd !== null"
                class="flex flex-col items-center space-y-2 w-28 mx-auto">
                <TimePickerSimple
                    v-model="tempEnd"
                    class="w-full"
                    data-testid="time_entry_range_end"
                    @keydown.enter.prevent.stop="confirmEndTime"></TimePickerSimple>
                <DatePicker v-model="tempEnd" class="w-full"></DatePicker>
                <Button variant="outline" size="sm" class="w-full" @click="confirmEndTime">
                    Confirm
                </Button>
            </div>
            <div v-else class="text-text-secondary">-- : --</div>
            <div tabindex="0" @focusin="emit('close')"></div>
        </div>
    </div>
</template>

<style></style>
