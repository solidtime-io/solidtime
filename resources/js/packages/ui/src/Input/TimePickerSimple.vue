<script setup lang="ts">
import { ref, watch } from 'vue';
import { getLocalizedDayJs } from '@/packages/ui/src/utils/time';
import { useFocus } from '@vueuse/core';
import { TextInput } from '@/packages/ui/src';
import { twMerge } from 'tailwind-merge';

// This has to be a localized timestamp, not UTC
const model = defineModel<string | null>({
    default: null,
});

const props = withDefaults(
    defineProps<{
        size?: 'base' | 'large';
        focus?: boolean;
    }>(),
    {
        size: 'base',
        focus: false,
    }
);

function updateTime(event: Event) {
    const target = event.target as HTMLInputElement;
    const newValue = target.value.trim();
    if (newValue.split(':').length === 2) {
        const [hours, minutes] = newValue.split(':');
        if (!isNaN(parseInt(hours)) && !isNaN(parseInt(minutes))) {
            model.value = getLocalizedDayJs(model.value)
                .set('hours', Math.min(parseInt(hours), 23))
                .set('minutes', Math.min(parseInt(minutes), 59))
                .set('seconds', 0)
                .format();
            emit('changed', model.value);
        }
    }
    // check if input is only numbers
    else if (/^\d+$/.test(newValue)) {
        if (newValue.length === 4) {
            // parse 1300 to 13:00
            const [hours, minutes] = [newValue.slice(0, 2), newValue.slice(2, 4)];
            model.value = getLocalizedDayJs(model.value)
                .set('hours', Math.min(parseInt(hours), 23))
                .set('minutes', Math.min(parseInt(minutes), 59))
                .set('seconds', 0)
                .format();
            emit('changed', model.value);
        } else if (newValue.length === 3) {
            // parse 130 to 01:30
            const [hours, minutes] = [newValue.slice(0, 1), newValue.slice(1, 3)];
            model.value = getLocalizedDayJs(model.value)
                .set('hours', Math.min(parseInt(hours), 23))
                .set('minutes', Math.min(parseInt(minutes), 59))
                .set('seconds', 0)
                .format();
            emit('changed', model.value);
        } else if (newValue.length === 2) {
            // parse 13 to 13:00
            model.value = getLocalizedDayJs(model.value)
                .set('hours', Math.min(parseInt(newValue), 23))
                .set('minutes', 0)
                .set('seconds', 0)
                .format();
            emit('changed', model.value);
        } else if (newValue.length === 1) {
            // parse 1 to 01:00
            model.value = getLocalizedDayJs(model.value)
                .set('hours', Math.min(parseInt(newValue), 23))
                .set('minutes', 0)
                .set('seconds', 0)
                .format();
            emit('changed', model.value);
        }
    }

    inputValue.value = getLocalizedDayJs(model.value).format('HH:mm');
}

watch(model, (value) => {
    inputValue.value = value ? getLocalizedDayJs(value).format('HH:mm') : null;
});

const timeInput = ref<HTMLInputElement | null>(null);
const emit = defineEmits(['changed']);

useFocus(timeInput, { initialValue: props.focus });

const inputValue = ref(model.value ? getLocalizedDayJs(model.value).format('HH:mm') : null);
</script>

<template>
    <TextInput
        v-bind="$attrs"
        ref="timeInput"
        v-model="inputValue"
        :class="twMerge('text-center w-24 px-3 py-2', size === 'large' && 'w-28')"
        data-testid="time_picker_input"
        type="text"
        @blur="updateTime"
        @keydown.enter.prevent="updateTime"
        @focus="($event.target as HTMLInputElement).select()"
        @mouseup="($event.target as HTMLInputElement).select()"
        @click="($event.target as HTMLInputElement).select()"
        @pointerup="($event.target as HTMLInputElement).select()" />
</template>

<style scoped></style>
