<script setup lang="ts">
import EstimatedTimeInput from '@/packages/ui/src/Input/EstimatedTimeInput.vue';
import { ClockIcon } from '@heroicons/vue/20/solid';
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue';

const model = defineModel<number | null>();
const emit = defineEmits(['submit']);
</script>

<template>
    <div class="pt-6">
        <div class="flex items-center space-x-1 mb-2">
            <ClockIcon class="text-text-quaternary w-4"></ClockIcon>
            <InputLabel for="billable" value="Time Estimated" />
        </div>
        <EstimatedTimeInput v-model="model" @submit="emit('submit')"></EstimatedTimeInput>
        <div class="flex items-center text-text-secondary text-xs pt-2 pl-1">
            <span>
                <span class="font-semibold">Info:</span>
                You can type natural language like
                <span class="font-semibold">2h 30m</span>
            </span>
        </div>
    </div>
</template>

<style scoped></style>
