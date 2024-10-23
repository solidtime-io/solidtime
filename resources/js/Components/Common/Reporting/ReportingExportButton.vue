<script setup lang="ts">
import { SecondaryButton } from '@/packages/ui/src';
import { ArrowDownTrayIcon } from '@heroicons/vue/20/solid';
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import type { ExportFormat } from '@/types/reporting';

const emit = defineEmits<{
    submit: [ExportFormat];
}>();
</script>

<template>
    <Dropdown align="bottom-end">
        <template #trigger>
            <SecondaryButton>
                <div class="flex space-x-2 items-center">
                    <ArrowDownTrayIcon
                        class="w-4 text-text-tertiary"></ArrowDownTrayIcon>
                    <span>Export</span>
                </div>
            </SecondaryButton>
        </template>
        <template #content>
            <div class="flex flex-col space-y-1 p-1.5">
                <SecondaryButton
                    class="border-0 px-2"
                    @click="emit('submit', 'pdf')"
                    >Export as PDF</SecondaryButton
                >
                <SecondaryButton
                    class="border-0 px-2"
                    @click="emit('submit', 'xlsx')"
                    >Export as Excel</SecondaryButton
                >
                <SecondaryButton
                    class="border-0 px-2"
                    @click="emit('submit', 'csv')"
                    >Export as CSV</SecondaryButton
                >
                <SecondaryButton
                    class="border-0 px-2"
                    @click="emit('submit', 'ods')"
                    >Export as ODS
                </SecondaryButton>
            </div>
        </template>
    </Dropdown>
</template>

<style scoped></style>
