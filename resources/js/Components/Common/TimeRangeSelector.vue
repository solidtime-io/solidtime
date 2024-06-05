<script setup lang="ts">
import { defineProps, ref, watch } from 'vue';
import TimePicker from '@/Components/Common/TimePicker.vue';
import { useFocusWithin } from '@vueuse/core';
import DatePicker from '@/Components/Common/DatePicker.vue';

const props = defineProps<{
    start: string;
    end: string | null;
}>();

const emit = defineEmits(['changed']);
const tempStart = ref(props.start);
const tempEnd = ref(props.end || null);

watch(props, () => {
    tempStart.value = props.start;
    tempEnd.value = props.end;
});
function updateTimeEntry() {
    if (tempStart.value !== props.start || tempEnd.value !== props.end) {
        emit('changed', tempStart.value, tempEnd.value);
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
