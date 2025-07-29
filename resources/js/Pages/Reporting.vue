<script setup lang="ts">
import AppLayout from '@/Layouts/AppLayout.vue';
import ReportingOverview from '@/Components/Common/Reporting/ReportingOverview.vue';
</script>

<template>
    <AppLayout title="Reporting" data-testid="reporting_view" class="overflow-hidden">
        <ReportingOverview></ReportingOverview>
    </AppLayout>
</template>
