<script setup lang="ts">
import { computed, ref, watch } from 'vue';

const value = defineModel();
const emit = defineEmits(['changed']);

function onChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.value !== value.value) {
        emit('changed', target.value);
        value.value = target.value;
    }
}

watch(
    () => value.value,
    (newValue) => {
        liveDataValue.value = newValue;
    }
);

function onInput(event: Event) {
    liveDataValue.value = (event.target as HTMLInputElement).value;
}

const liveDataValue = ref(value.value);

const displaysPlaceholder = computed(() => {
    return liveDataValue.value === '' || liveDataValue.value === null;
});
</script>

<template>
    <div
        class="relative min-w-0 flex-1 text-ellipsis whitespace-nowrap overflow-hidden">
        <div class="relative text-sm font-medium min-w-0">
            <div
                :class="[
                    'opacity-0 h-4 text-sm whitespace-pre font-medium min-w-0 pl-3 pr-1',
                    { 'min-w-[130px]': displaysPlaceholder },
                ]">
                {{ liveDataValue }}
            </div>
            <input
                data-testid="time_entry_description"
                :value="liveDataValue"
                placeholder="Add a description"
                class="absolute px-0 h-full min-w-0 pl-3 pr-1 left-0 top-0 w-full text-sm text-text-primary font-medium bg-transparent focus-visible:ring-0 rounded-lg border-0"
                @blur="onChange"
                @input="onInput"
                @keydown.enter="onChange" />
        </div>
    </div>
</template>
