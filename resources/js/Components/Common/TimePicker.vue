<script setup lang="ts">
import { ref, watch } from 'vue';
import { getDayJsInstance, getLocalizedDayJs } from '@/utils/time';
import { twMerge } from 'tailwind-merge';
import { useFocus } from '@vueuse/core';

// This has to be a localized timestamp, not UTC
const model = defineModel<string | null>({
    default: null,
});

const props = withDefaults(
    defineProps<{
        size: 'base' | 'large';
        focus: boolean;
    }>(),
    {
        size: 'base',
        focus: false,
    }
);

const hours = ref(
    model.value ? getLocalizedDayJs(model.value).format('HH') : null
);
const minutes = ref(
    model.value ? getLocalizedDayJs(model.value).format('mm') : null
);
watch(
    () => model.value,
    () => {
        hours.value = model.value
            ? getLocalizedDayJs(model.value).format('HH')
            : null;
        minutes.value = model.value
            ? getLocalizedDayJs(model.value).format('mm')
            : null;
    }
);

function updateMinutes(event: Event) {
    const target = event.target as HTMLInputElement;
    const newValue = target.value;
    if (!isNaN(parseInt(newValue))) {
        model.value = getDayJsInstance()(model.value)
            .set('minutes', Math.min(parseInt(newValue), 59))
            .format();
    }
    minutes.value = model.value
        ? getLocalizedDayJs(model.value).format('mm')
        : null;
}

function updateHours(event: Event) {
    const target = event.target as HTMLInputElement;
    const newValue = target.value;
    if (newValue.endsWith(':')) {
        minutesInput.value?.focus();
    } else if (!isNaN(parseInt(newValue))) {
        model.value = getLocalizedDayJs(model.value)
            .set('hours', Math.min(parseInt(newValue), 23))
            .format();
    }
    hours.value = model.value
        ? getLocalizedDayJs(model.value).format('HH')
        : null;
}

const hoursInput = ref<HTMLInputElement | null>(null);
const minutesInput = ref<HTMLInputElement | null>(null);
const emit = defineEmits(['changed']);

useFocus(hoursInput, { initialValue: props.focus });
</script>

<template>
    <div class="flex items-center justify-center text-white">
        <div
            :class="
                twMerge(
                    'border bg-input-background rounded-md border-input-border overflow-hidden',
                    props.size === 'large' ? 'py-1.5 px-2' : ''
                )
            ">
            <input
                v-model="hours"
                ref="hoursInput"
                @input="updateHours"
                @keydown.enter="emit('changed')"
                @focus="($event.target as HTMLInputElement).select()"
                data-testid="time_picker_hour"
                type="text"
                :class="
                    twMerge(
                        'border-none bg-transparent px-1 py-0.5 w-[30px] text-center focus:ring-0 focus:bg-card-background-active',
                        props.size === 'large' ? 'text-base' : 'text-sm'
                    )
                " />
            <span>:</span>
            <input
                v-model="minutes"
                ref="minutesInput"
                @keydown.enter="emit('changed')"
                @input="updateMinutes"
                @focus="($event.target as HTMLInputElement).select()"
                data-testid="time_picker_minute"
                type="text"
                :class="
                    twMerge(
                        'border-none bg-transparent px-1 py-1 w-[30px] text-center focus:ring-0 focus:bg-card-background-active',
                        props.size === 'large' ? 'text-base' : 'text-sm'
                    )
                " />
        </div>
    </div>
</template>

<style scoped></style>
