<script setup lang="ts">
import { ref } from 'vue';
import { getLocalizedDayJs } from '@/utils/time';
import { twMerge } from 'tailwind-merge';

const props = defineProps<{
    class?: string;
}>();
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
            .set('month', newDate.month())
            .set('date', newDate.date())
            .utc()
            .format();
        emit('changed', model.value);
    }
}

const datePicker = ref<HTMLInputElement | null>(null);

function updateTempValue(event: Event) {
    const target = event.target as HTMLInputElement;
    tempDate.value = target.value;
}

const emit = defineEmits(['changed']);
</script>

<template>
    <div class="flex items-center justify-center text-muted">
        <input
            ref="datePicker"
            @change="updateTempValue"
            @blur="updateDate"
            @keydown.enter="updateDate"
            :class="
                twMerge(
                    'bg-input-background border text-white border-input-border rounded-md',
                    props.class
                )
            "
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
