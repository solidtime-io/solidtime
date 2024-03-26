<script setup lang="ts">
import { computed } from 'vue';
import dayjs from 'dayjs';

const model = defineModel<string | null>({
    default: null,
});

const hours = computed(() => {
    return model.value ? dayjs(model.value).utc().hour() : null;
});

const minutes = computed(() => {
    return model.value ? dayjs(model.value).utc().minute() : null;
});

function updateMinutes(event: Event) {
    const target = event.target as HTMLInputElement;
    const newValue = target.value;
    if (parseInt(newValue)) {
        model.value = dayjs(model.value)
            .utc()
            .set('minutes', parseInt(newValue))
            .format();
    }
}

function updateHours(event: Event) {
    const target = event.target as HTMLInputElement;
    const newValue = target.value;
    if (parseInt(newValue)) {
        model.value = dayjs(model.value)
            .utc()
            .set('hours', parseInt(newValue))
            .format();
    }
}
</script>

<template>
    <div class="flex items-center justify-center">
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
