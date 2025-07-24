<script setup lang="ts">
import { ref, watch, inject, type ComputedRef } from 'vue';
import { getLocalizedDayJs, formatTime } from '@/packages/ui/src/utils/time';
import { useFocus } from '@vueuse/core';
import { TextInput } from '@/packages/ui/src';
import { twMerge } from 'tailwind-merge';
import type { Organization } from '@/packages/api/src';

// This has to be a localized timestamp, not UTC
const model = defineModel<string | null>({
    default: null,
});

const organization = inject<ComputedRef<Organization>>('organization');

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

    // Get current hours and minutes for comparison
    const currentTime = model.value ? getLocalizedDayJs(model.value) : null;
    const currentHours = currentTime?.hour() ?? 0;
    const currentMinutes = currentTime?.minute() ?? 0;

    // Handle AM/PM format
    const amPmMatch = newValue.match(/^(\d{1,2}):?(\d{2})?\s*(AM|PM|am|pm)$/);
    if (amPmMatch) {
        let hours = amPmMatch[1];
        const minutes = amPmMatch[2] ?? '00';
        const period = amPmMatch[3];
        
        hours = parseInt(hours).toString();
        if (period.toUpperCase() === 'PM' && hours !== '12') {
            hours = (parseInt(hours) + 12).toString();
        } else if (period.toUpperCase() === 'AM' && hours === '12') {
            hours = '0';
        }
        
        const newHours = parseInt(hours);
        const newMinutes = parseInt(minutes);
        
        if (newHours !== currentHours || newMinutes !== currentMinutes) {
            model.value = getLocalizedDayJs(model.value)
                .set('hours', newHours)
                .set('minutes', newMinutes)
                .set('seconds', 0)
                .format();
            emit('changed', model.value);
        }
        return;
    }

    // Handle existing formats
    if (newValue.split(':').length === 2) {
        const [hours, minutes] = newValue.split(':');
        if (!isNaN(parseInt(hours)) && !isNaN(parseInt(minutes))) {
            const newHours = Math.min(parseInt(hours), 23);
            const newMinutes = Math.min(parseInt(minutes), 59);
            
            if (newHours !== currentHours || newMinutes !== currentMinutes) {
                model.value = getLocalizedDayJs(model.value)
                    .set('hours', newHours)
                    .set('minutes', newMinutes)
                    .set('seconds', 0)
                    .format();
                emit('changed', model.value);
            }
        }
    }
    // check if input is only numbers
    else if (/^\d+$/.test(newValue)) {
        let newHours = currentHours;
        let newMinutes = currentMinutes;

        if (newValue.length === 4) {
            // parse 1300 to 13:00
            newHours = Math.min(parseInt(newValue.slice(0, 2)), 23);
            newMinutes = Math.min(parseInt(newValue.slice(2, 4)), 59);
        } else if (newValue.length === 3) {
            // parse 130 to 01:30
            newHours = Math.min(parseInt(newValue.slice(0, 1)), 23);
            newMinutes = Math.min(parseInt(newValue.slice(1, 3)), 59);
        } else if (newValue.length === 2) {
            // parse 13 to 13:00
            newHours = Math.min(parseInt(newValue), 23);
            newMinutes = 0;
        } else if (newValue.length === 1) {
            // parse 1 to 01:00
            newHours = Math.min(parseInt(newValue), 23);
            newMinutes = 0;
        }

        if (newHours !== currentHours || newMinutes !== currentMinutes) {
            model.value = getLocalizedDayJs(model.value)
                .set('hours', newHours)
                .set('minutes', newMinutes)
                .set('seconds', 0)
                .format();
            emit('changed', model.value);
        }
    }
}

watch(model, (value) => {
    inputValue.value = value
        ? formatTime(value, organization?.value?.time_format || '24-hours')
        : null;
});

const timeInput = ref<HTMLInputElement | null>(null);
const emit = defineEmits(['changed']);

useFocus(timeInput, { initialValue: props.focus });

const inputValue = ref(
    model.value
        ? formatTime(
              model.value,
              organization?.value?.time_format || '24-hours'
          )
        : null
);
</script>

<template>
    <TextInput
        v-bind="$attrs"
        ref="timeInput"
        v-model="inputValue"
        :class="
            twMerge('text-center w-28 px-3 py-2', size === 'large' && 'w-28')
        "
        data-testid="time_picker_input"
        type="text"
        @blur="updateTime"
        @focus="($event.target as HTMLInputElement).select()"
        @mouseup="($event.target as HTMLInputElement).select()"
        @click="($event.target as HTMLInputElement).select()"
        @pointerup="($event.target as HTMLInputElement).select()" />
</template>

<style scoped></style>
