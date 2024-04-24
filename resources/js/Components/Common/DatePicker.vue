<script setup lang="ts">
import { computed, ref } from 'vue';
import { getLocalizedDayJs } from '@/utils/time';
import dayjs from 'dayjs';

const model = defineModel<string | null>({
    default: null,
});

function updateDate(event: Event) {
    const target = event.target as HTMLInputElement;
    const newValue = target.value;
    const newDate = dayjs(newValue);
    if (newDate) {
        console.log('old', model.value);
        model.value = getLocalizedDayJs(model.value)
            .set('year', newDate.year())
            .set('day', newDate.day())
            .set('month', newDate.month())
            .utc()
            .format();
    }
}

const date = computed(() => {
    return model.value
        ? getLocalizedDayJs(model.value).format('YYYY-MM-DD')
        : null;
});

const datePicker = ref<HTMLInputElement | null>(null);
</script>

<template>
    <div class="flex items-center justify-center text-muted">
        <input
            ref="datePicker"
            @change="updateDate"
            class="bg-input-background border text-white border-input-border rounded-md"
            type="date"
            id="start"
            name="trip-start"
            :value="date" />
    </div>
</template>

<style scoped>
input::-webkit-calendar-picker-indicator {
    filter: invert(1);
    opacity: 0.2;
}
</style>
