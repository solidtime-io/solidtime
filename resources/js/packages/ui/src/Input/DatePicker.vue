<script setup lang="ts">
import { ref, watch } from 'vue';
import {
    getDayJsInstance,
    getLocalizedDayJs,
} from '@/packages/ui/src/utils/time';
import { twMerge } from 'tailwind-merge';

const props = defineProps<{
    class?: string;
    tabindex?: string;
}>();

// This has to be a localized timestamp, not UTC
const model = defineModel<string | null>({
    default: null,
});

const tempDate = ref(getLocalizedDayJs(model.value).format('YYYY-MM-DD'));

watch(model, (value) => {
    tempDate.value = getLocalizedDayJs(value).format('YYYY-MM-DD');
});

function updateDate(event: Event) {
    const target = event.target as HTMLInputElement;
    const newValue = target.value;
    const newDate = getDayJsInstance()(newValue);
    if (newDate.isValid()) {
        model.value = getLocalizedDayJs(model.value)
            .set('year', newDate.year())
            .set('month', newDate.month())
            .set('date', newDate.date())
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
    <div class="flex items-center text-muted">
        <input
            id="start"
            ref="datePicker"
            :tabindex="tabindex"
            :class="
                twMerge(
                    'bg-input-background border text-text-primary border-input-border focus-visible:outline-0 focus-visible:border-input-border-active focus-visible:ring-0 rounded-md',
                    props.class
                )
            "
            type="date"
            name="trip-start"
            :value="tempDate"
            @change="updateTempValue"
            @blur="updateDate"
            @keydown.enter="updateDate" />
    </div>
</template>

<style scoped>
input::-webkit-calendar-picker-indicator {
    filter: invert(1);

    opacity: 0.2;
}
</style>
