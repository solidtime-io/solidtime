<script setup lang="ts">
import DurationInput from '@/packages/ui/src/Input/DurationInput.vue';
import { ClockIcon } from '@heroicons/vue/20/solid';
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue';

const model = defineModel<number | null>();
const emit = defineEmits(['submit']);
</script>

<template>
    <div class="pt-6">
        <div class="flex items-center space-x-1 mb-2">
            <ClockIcon class="text-text-quaternary w-4"></ClockIcon>
            <InputLabel for="time-estimated" value="Time Estimated" />
        </div>
        <DurationInput
            id="time-estimated"
            v-model="model"
            class="max-w-[150px]"
            @submit="emit('submit')"></DurationInput>
    </div>
</template>

<style scoped></style>
