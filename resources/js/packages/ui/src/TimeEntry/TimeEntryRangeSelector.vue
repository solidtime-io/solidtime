<script setup lang="ts">
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import { defineProps, ref } from 'vue';
import { formatDate, formatStartEnd } from '@/packages/ui/src/utils/time';
import TimeRangeSelector from '@/packages/ui/src/Input/TimeRangeSelector.vue';
import { twMerge } from 'tailwind-merge';

defineProps<{
    start: string;
    end: string | null;
    showDate?: boolean;
}>();

const emit = defineEmits<{
    changed: [start: string, end: string | null];
}>();

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
                    :class="
                        twMerge(
                            'text-muted w-[110px] px-2 bg-transparent text-center hover:bg-card-background rounded-lg border border-transparent hover:border-card-border focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-white/80',
                            showDate
                                ? 'text-xs py-1.5 font-semibold'
                                : 'text-sm py-2 font-medium',
                            open && 'border-card-border bg-card-background'
                        )
                    ">
                    {{ formatStartEnd(start, end) }}
                    <span v-if="showDate" class="text-text-tertiary font-medium"
                        >{{ formatDate(start) }}
                    </span>
                </button>
            </template>
            <template #content>
                <TimeRangeSelector
                    @changed="
                        (newStart: string, newEnd: string) =>
                            emit('changed', newStart, newEnd)
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
