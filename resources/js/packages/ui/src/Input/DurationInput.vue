<script setup lang="ts">
import { computed, ref } from 'vue';
import { TextInput } from '@/packages/ui/src';

const model = defineModel<number | null>({
    default: null,
});

const emit = defineEmits<{
    submit: [];
}>();

const temporaryCustomTimerEntry = ref<string>('');

function updateDuration() {
    const hours = parseInt(temporaryCustomTimerEntry.value);
    if (!isNaN(hours)) {
        model.value = hours * 60 * 60;
    }
    temporaryCustomTimerEntry.value = '';
}

const currentTime = computed({
    get() {
        if (temporaryCustomTimerEntry.value !== '') {
            return temporaryCustomTimerEntry.value;
        }
        if (model.value === null) {
            return '';
        }
        return Math.round(model.value / 60 / 60).toString();
    },
    // setter
    set(newValue) {
        if (newValue) {
            temporaryCustomTimerEntry.value = newValue;
        } else {
            temporaryCustomTimerEntry.value = '';
        }
    },
});

function selectInput(event: Event) {
    const target = event.target as HTMLInputElement;
    target.select();
}

function updateAndSubmit() {
    updateDuration();
    emit('submit');
}
</script>

<template>
    <div class="relative">
        <TextInput
            v-model="currentTime"
            class="w-full overflow-hidden pr-14"
            placeholder="0"
            @focus="selectInput"
            @blur="updateDuration"
            @keydown.enter="updateAndSubmit">
        </TextInput>
        <div class="absolute top-0 right-0 h-full flex items-center px-4 font-medium">
            <span> hours </span>
        </div>
    </div>
</template>

<style scoped></style>
