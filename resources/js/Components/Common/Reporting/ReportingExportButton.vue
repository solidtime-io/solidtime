<script setup lang="ts">
import { SecondaryButton } from '@/packages/ui/src';
import { ArrowDownTrayIcon } from '@heroicons/vue/20/solid';
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
import type { ExportFormat } from '@/types/reporting';
import { ref } from 'vue';

const props = defineProps<{
    download: (format: ExportFormat) => Promise<void>;
}>();
const loading = ref(false);
function triggerDownload(format: ExportFormat) {
    loading.value = true;
    props.download(format).finally(() => {
        loading.value = false;
    });
}
</script>

<template>
    <Dropdown align="bottom-end">
        <template #trigger>
            <SecondaryButton :icon="ArrowDownTrayIcon" :loading>
                Export
            </SecondaryButton>
        </template>
        <template #content>
            <div class="flex flex-col space-y-1 p-1.5">
                <SecondaryButton
                    class="border-0 px-2"
                    @click="triggerDownload('pdf')"
                    >Export as PDF</SecondaryButton
                >
                <SecondaryButton
                    class="border-0 px-2"
                    @click="triggerDownload('xlsx')"
                    >Export as Excel</SecondaryButton
                >
                <SecondaryButton
                    class="border-0 px-2"
                    @click="triggerDownload('csv')"
                    >Export as CSV</SecondaryButton
                >
                <SecondaryButton
                    class="border-0 px-2"
                    @click="triggerDownload('ods')"
                    >Export as ODS
                </SecondaryButton>
            </div>
        </template>
    </Dropdown>
</template>

<style scoped></style>
