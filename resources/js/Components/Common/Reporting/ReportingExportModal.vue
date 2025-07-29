<script setup lang="ts">
import { ArrowDownTrayIcon, CheckCircleIcon, XMarkIcon } from '@heroicons/vue/20/solid';
import { Modal, PrimaryButton } from '@/packages/ui/src';
const props = defineProps<{
    exportUrl: string | null;
}>();

const showExportModal = defineModel('show', { default: false });

function downloadCurrentExport() {
    if (props.exportUrl) {
        window.open(props.exportUrl, '_blank')?.focus();
    }
}
</script>

<template>
    <Modal closeable max-width="lg" :show="showExportModal" @close="showExportModal = false">
        <button
            class="text-text-tertiary w-6 mx-auto absolute focus-visible:outline-none focus-visible:ring-2 rounded-full focus-visible:ring-ring transition focus-visible:text-text-primary hover:text-text-primary top-2 right-2">
            <XMarkIcon @click="showExportModal = false"></XMarkIcon>
        </button>
        <div class="text-center text-text-primary py-6">
            <div class="flex items-center font-semibold text-lg justify-center space-x-2 pb-2">
                <CheckCircleIcon class="text-text-tertiary w-6"></CheckCircleIcon>
                <span> Export Successful! </span>
            </div>
            <div class="text-center text-sm max-w-64 mx-auto">
                <p class="pb-5">Your export is ready, you can download it with the button below.</p>
                <PrimaryButton :icon="ArrowDownTrayIcon" @click="downloadCurrentExport"
                    >Download</PrimaryButton
                >
            </div>
        </div>
    </Modal>
</template>

<style scoped></style>
