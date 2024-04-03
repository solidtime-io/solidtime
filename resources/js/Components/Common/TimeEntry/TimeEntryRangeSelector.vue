<script setup lang="ts">
import Dropdown from '@/Components/Dropdown.vue';
import { defineProps, ref, watch } from 'vue';
import { formatTime } from '@/utils/time';
import TimePicker from '@/Components/Common/TimePicker.vue';
import { useFocusWithin } from '@vueuse/core';

const props = defineProps<{
    start: string;
    end: string | null;
}>();

function formatStartEnd(start: string, end: string | null) {
    if (end) {
        return `${formatTime(start)} - ${formatTime(end)}`;
    } else {
        return `${formatTime(start)} - ...`;
    }
}
const emit = defineEmits(['changed']);
const tempStart = ref(props.start);
const tempEnd = ref(props.end || null);

watch(props, () => {
    tempStart.value = props.start;
    tempEnd.value = props.end;
});
function updateTimeEntry() {
    emit('changed', tempStart.value, tempEnd.value);
}

const dropdownContent = ref();
const { focused } = useFocusWithin(dropdownContent);

watch(focused, (newValue, oldValue) => {
    if (oldValue === true && newValue === false) {
        console.log(newValue, oldValue);
        updateTimeEntry();
    }
});
</script>

<template>
    <div class="relative">
        <Dropdown
            align="right"
            :close-on-content-click="false"
            @submit="updateTimeEntry">
            <template #trigger>
                <button
                    data-testid="time_entry_range_selector"
                    class="text-muted w-[110px] px-2 py-2 bg-transparent text-center hover:bg-card-background rounded-lg border border-transparent hover:border-card-border text-sm font-medium">
                    {{ formatStartEnd(start, end) }}
                </button>
            </template>
            <template #content>
                <div
                    ref="dropdownContent"
                    class="grid grid-cols-2 divide-x divide-card-background-separator text-center py-1">
                    <div>
                        <div class="font-bold text-white text-sm pb-1">
                            Start
                        </div>
                        <TimePicker
                            data-testid="time_entry_range_start"
                            @updated="updateTimeEntry"
                            v-model="tempStart"></TimePicker>
                    </div>
                    <div>
                        <div class="font-bold text-white text-sm pb-1">End</div>
                        <TimePicker
                            data-testid="time_entry_range_end"
                            @updated="updateTimeEntry"
                            v-model="tempEnd"></TimePicker>
                    </div>
                </div>
            </template>
        </Dropdown>
    </div>
</template>

<style></style>
