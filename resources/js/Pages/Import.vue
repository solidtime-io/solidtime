<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import { ArrowsRightLeftIcon } from '@heroicons/vue/16/solid';
import PageTitle from '@/Components/Common/PageTitle.vue';
import ImportData from '@/Pages/Teams/Partials/ImportData.vue';
import ExportData from '@/Pages/Teams/Partials/ExportData.vue';
import { onMounted } from 'vue';
import { useOrganizationStore } from '@/utils/useOrganization';

// The organization name and date/time formats are shown in the import and export sections
onMounted(() => useOrganizationStore().fetchOrganization());
</script>

<template>
    <AppLayout title="Import / Export" data-testid="import_view">
        <MainContainer
            class="py-5 border-b border-default-background-separator flex justify-between items-center">
            <div class="flex items-center space-x-6">
                <PageTitle :icon="ArrowsRightLeftIcon" title="Import / Export"> </PageTitle>
            </div>
        </MainContainer>
        <MainContainer class="py-6 space-y-4">
            <div class="grid lg:grid-cols-2 gap-6">
                <ImportData></ImportData>
                <ExportData></ExportData>
            </div>
        </MainContainer>
    </AppLayout>
</template>
