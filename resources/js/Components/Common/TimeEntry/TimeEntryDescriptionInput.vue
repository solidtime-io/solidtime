<script setup lang="ts">
import { computed, ref } from 'vue';

const value = defineModel();
const emit = defineEmits(['changed']);

function onChange(event: Event) {
    const target = event.target as HTMLInputElement;
    if (target.value !== value.value) {
        emit('changed', target.value);
        value.value = target.value;
    }
}

function onInput(event: Event) {
    liveDataValue.value = (event.target as HTMLInputElement).value;
}

const liveDataValue = ref(value.value);

const displaysPlaceholder = computed(() => {
    return liveDataValue.value === '' || liveDataValue.value === null;
});
</script>

<template>
    <div>
        <div class="relative text-sm font-medium p">
            <div
                :class="[
                    'opacity-0 py-2 text-base whitespace-pre pl-3 pr-1',
                    { 'min-w-[150px]': displaysPlaceholder },
                ]">
                {{ liveDataValue }}
            </div>
            <input
                data-testid="time_entry_description"
                :value="liveDataValue"
                @blur="onChange"
                @input="onInput"
                @keydown.enter="onChange"
                placeholder="Add a description"
                class="absolute px-0 h-full pl-3 pr-1 left-0 top-0 w-full text-white font-medium bg-transparent focus-visible:ring-0 rounded-lg border-0" />
        </div>
    </div>
</template>
