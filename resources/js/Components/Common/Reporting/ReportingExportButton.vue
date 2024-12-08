<script setup lang="ts">
import { SecondaryButton } from '@/packages/ui/src';
import { ArrowDownTrayIcon, LockClosedIcon } from '@heroicons/vue/20/solid';
import Dropdown from '@/packages/ui/src/Input/Dropdown.vue';
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
                    @click="triggerDownload('pdf')">
                    <div class="flex items-center space-x-2">
                        <span> Export as PDF </span>
                        <LockClosedIcon
                            v-if="!isAllowedToPerformPremiumAction()"
                            class="w-3.5 text-text-tertiary"></LockClosedIcon>
                    </div>
                </SecondaryButton>
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
    <UpgradeModal v-model:show="showPremiumModal">
        <strong>PDF Reports</strong> are only available in solidtime
        Professional.
    </UpgradeModal>
</template>

<style scoped></style>
