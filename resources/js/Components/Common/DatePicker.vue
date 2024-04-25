<script setup lang="ts">
import { ref } from 'vue';
import { getLocalizedDayJs } from '@/utils/time';

const model = defineModel<string | null>({
    default: null,
});

const tempDate = ref(getLocalizedDayJs(model.value).format('YYYY-MM-DD'));

function updateDate(event: Event) {
    const target = event.target as HTMLInputElement;
    const newValue = target.value;
    const newDate = getLocalizedDayJs(newValue);
    if (newDate) {
        model.value = getLocalizedDayJs(model.value)
            .set('year', newDate.year())
            .set('day', newDate.day())
            .set('month', newDate.month())
            .utc()
            .format();
    }
}

const datePicker = ref<HTMLInputElement | null>(null);

function updateTempValue(event: Event) {
    const target = event.target as HTMLInputElement;
    tempDate.value = target.value;
}
</script>

<template>
    <div class="flex items-center justify-center text-muted">
        <input
            ref="datePicker"
            @change="updateTempValue"
            @blur="updateDate"
            class="bg-input-background border text-white border-input-border rounded-md"
            type="date"
            id="start"
            name="trip-start"
            :value="tempDate" />
    </div>
</template>

<style scoped>
input::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.2;
}
</style>
