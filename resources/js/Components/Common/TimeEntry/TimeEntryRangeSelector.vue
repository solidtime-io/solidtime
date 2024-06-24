<script setup lang="ts">
import Dropdown from '@/Components/Dropdown.vue';
import { defineProps, ref } from 'vue';
import { formatStartEnd } from '@/utils/time';
import TimeRangeSelector from '@/Components/Common/TimeRangeSelector.vue';

defineProps<{
    start: string;
    end: string | null;
}>();

const emit = defineEmits(['changed']);

const open = ref(false);
</script>

<template>
    <div class="relative">
        <Dropdown
            v-model="open"
            @submit="open = false"
            align="bottom"
            :close-on-content-click="false">
            <template #trigger>
                <button
                    data-testid="time_entry_range_selector"
                    class="text-muted w-[110px] px-2 py-2 bg-transparent text-center hover:bg-card-background rounded-lg border border-transparent hover:border-card-border text-sm font-medium">
                    {{ formatStartEnd(start, end) }}
                </button>
            </template>
            <template #content>
                <TimeRangeSelector
                    @changed="
                        (newStart, newEnd) => emit('changed', newStart, newEnd)
                    "
                    focus
                    :start="start"
                    :end="end">
                </TimeRangeSelector>
            </template>
        </Dropdown>
    </div>
</template>

<style></style>
