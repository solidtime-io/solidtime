<script setup lang="ts">
import { computed } from 'vue';
import { getLocalizedDayJs } from '@/utils/time';
import { twMerge } from 'tailwind-merge';

const model = defineModel<string | null>({
    default: null,
});

const props = withDefaults(
    defineProps<{
        size: 'base' | 'large';
    }>(),
    {
        size: 'base',
    }
);

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
    <div class="flex items-center justify-center text-white">
        <div
            :class="
                twMerge(
                    'border bg-input-background rounded-md border-input-border overflow-hidden',
                    props.size === 'large' ? 'py-1.5 px-2' : ''
                )
            ">
            <input
                :value="hours"
                @input="updateHours"
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
                :value="minutes"
                @input="updateMinutes"
                data-testid="time_picker_minute"
                type="text"
                :class="
                    twMerge(
                        'border-none bg-transparent px-1 py-0.5 w-[30px] text-center focus:ring-0 focus:bg-card-background-active',
                        props.size === 'large' ? 'text-base' : 'text-sm'
                    )
                " />
        </div>
    </div>
</template>

<style scoped></style>
