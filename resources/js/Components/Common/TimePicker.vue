<script setup lang="ts">
import { computed } from 'vue';
import { getLocalizedDayJs } from '@/utils/time';

const model = defineModel<string | null>({
    default: null,
});

const hours = computed(() => {
    return model.value ? getLocalizedDayJs(model.value).hour() : null;
});

const minutes = computed(() => {
    return model.value ? getLocalizedDayJs(model.value).minute() : null;
});

function updateMinutes(event: Event) {
    const target = event.target as HTMLInputElement;
    const newValue = target.value;
    if (!isNaN(parseInt(newValue))) {
        model.value = getLocalizedDayJs(model.value)
            .set('minutes', parseInt(newValue))
            .utc()
            .format();
    }
}

function updateHours(event: Event) {
    const target = event.target as HTMLInputElement;
    const newValue = target.value;
    if (!isNaN(parseInt(newValue))) {
        model.value = getLocalizedDayJs(model.value)
            .set('hours', parseInt(newValue))
            .utc()
            .format();
    }
}
</script>

<template>
    <div class="flex items-center justify-center text-muted">
        <input
            :value="hours"
            @input="updateHours"
            data-testid="time_picker_hour"
            type="text"
            class="bg-card-background border-none text-sm px-1 py-0.5 w-[30px] text-center focus:ring-0 focus:bg-card-background-active" />
        <span>:</span>
        <input
            :value="minutes"
            @input="updateMinutes"
            data-testid="time_picker_minute"
            type="text"
            class="bg-card-background border-none text-sm px-1 py-0.5 w-[30px] text-center focus:ring-0 focus:bg-card-background-active" />
    </div>
</template>

<style scoped></style>
