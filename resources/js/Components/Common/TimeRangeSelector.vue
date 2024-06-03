<script setup lang="ts">
import { defineProps, ref, watch } from 'vue';
import TimePicker from '@/Components/Common/TimePicker.vue';
import { useFocusWithin } from '@vueuse/core';

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
        class="grid grid-cols-2 divide-x divide-card-background-separator text-center py-1">
        <div>
            <div class="font-bold text-white text-sm pb-1">Start</div>
            <TimePicker
                data-testid="time_entry_range_start"
                @changed="updateTimeEntry"
                v-model="tempStart"></TimePicker>
        </div>
        <div>
            <div class="font-bold text-white text-sm pb-1">End</div>
            <TimePicker
                v-if="tempEnd !== null"
                data-testid="time_entry_range_end"
                @changed="updateTimeEntry"
                v-model="tempEnd"></TimePicker>
            <div class="text-muted" v-else>-- : --</div>
        </div>
    </div>
</template>

<style></style>
