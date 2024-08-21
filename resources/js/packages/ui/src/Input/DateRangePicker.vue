<script setup lang="ts">
import { CalendarIcon } from '@heroicons/vue/20/solid';
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import DatePicker from '@/packages/ui/src/Input/DatePicker.vue';
import { formatDate } from '@/packages/ui/src/utils/time';

const start = defineModel('start', { default: '' });
const end = defineModel('end', { default: '' });
</script>

<template>
    <Dropdown :close-on-content-click="false" align="bottom-end">
        <template #trigger>
            <button
                class="px-3 py-1.5 bg-input-background border border-input-border font-medium rounded-lg flex items-center space-x-2">
                <CalendarIcon class="w-5"></CalendarIcon>
                <div class="text-white">
                    {{ formatDate(start) }}
                    <span class="px-1.5 text-muted">-</span>
                    {{ formatDate(end) }}
                </div>
            </button>
        </template>
        <template #content>
            <div class="overflow-hidden w-[280px] px-3 py-1.5">
                <div class="flex space-x-3 items-center justify-between">
                    <div class="text-sm font-medium text-muted">Start Date</div>
                    <DatePicker v-model="start"></DatePicker>
                </div>
                <div class="mt-2 flex space-x-3 items-center justify-between">
                    <div class="text-sm font-medium text-muted">End Date</div>
                    <DatePicker v-model="end"></DatePicker>
                </div>
            </div>
        </template>
    </Dropdown>
</template>

<style scoped></style>
