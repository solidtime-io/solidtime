<script setup lang="ts">
import { SecondaryButton } from '@/packages/ui/src';
import { ArrowDownTrayIcon, LockClosedIcon } from '@heroicons/vue/20/solid';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/Components/ui/dropdown-menu';
import type { ExportFormat } from '@/types/reporting';
import { ref } from 'vue';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import UpgradeModal from '@/Components/Common/UpgradeModal.vue';

const props = defineProps<{
    download: (format: ExportFormat) => Promise<void>;
}>();
const loading = ref(false);
const showPremiumModal = ref(false);
function triggerDownload(format: ExportFormat) {
    if (format === 'pdf' && !isAllowedToPerformPremiumAction()) {
        showPremiumModal.value = true;
        return;
    }
    loading.value = true;
    props.download(format).finally(() => {
        loading.value = false;
    });
}
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <SecondaryButton :icon="ArrowDownTrayIcon" :loading> Export </SecondaryButton>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end">
            <DropdownMenuItem @click="triggerDownload('pdf')">
                <div class="flex items-center space-x-2">
                    <span>Export as PDF</span>
                    <LockClosedIcon
                        v-if="!isAllowedToPerformPremiumAction()"
                        class="w-3.5 text-text-tertiary" />
                </div>
            </DropdownMenuItem>
            <DropdownMenuItem @click="triggerDownload('xlsx')"> Export as Excel </DropdownMenuItem>
            <DropdownMenuItem @click="triggerDownload('csv')"> Export as CSV </DropdownMenuItem>
            <DropdownMenuItem @click="triggerDownload('ods')"> Export as ODS </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
    <UpgradeModal v-model:show="showPremiumModal">
        <strong>PDF Reports</strong> are only available in solidtime Professional.
    </UpgradeModal>
</template>

<style scoped></style>
