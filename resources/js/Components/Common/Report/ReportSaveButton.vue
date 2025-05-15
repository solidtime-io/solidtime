<script setup lang="ts">
import { SecondaryButton } from '@/packages/ui/src';
import ReportCreateModal from '@/Components/Common/Report/ReportCreateModal.vue';
import { h, ref } from 'vue';
import type { CreateReportBodyProperties } from '@/packages/api/src';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import UpgradeModal from '@/Components/Common/UpgradeModal.vue';
import { canCreateReports } from '@/utils/permissions';
defineProps<{
    reportProperties: CreateReportBodyProperties;
}>();

const showCreateReportModal = ref(false);
const showPremiumModal = ref(false);
const SaveIcon = h('div', {
    innerHTML:
        '<svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><g fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"><path d="M15.2 3a2 2 0 0 1 1.4.6l3.8 3.8a2 2 0 0 1 .6 1.4V19a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2z"/><path d="M17 21v-7a1 1 0 0 0-1-1H8a1 1 0 0 0-1 1v7M7 3v4a1 1 0 0 0 1 1h7"/></g></svg>',
});

function onSaveReportClick() {
    if (isAllowedToPerformPremiumAction()) {
        showCreateReportModal.value = true;
    } else {
        showPremiumModal.value = true;
    }
}
</script>

<template>
    <ReportCreateModal
        v-model:show="showCreateReportModal"
        :properties="reportProperties"></ReportCreateModal>
    <UpgradeModal v-model:show="showPremiumModal">
        <strong>Sharable Reports</strong> is only available in solidtime
        Professional.
    </UpgradeModal>
    <SecondaryButton
        v-if="canCreateReports()"
        :icon="SaveIcon"
        @click="onSaveReportClick"
        >Save Report</SecondaryButton
    >
</template>

<style scoped></style>
