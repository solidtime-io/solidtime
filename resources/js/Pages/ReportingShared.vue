<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import { ChartBarIcon, CreditCardIcon, UserGroupIcon } from '@heroicons/vue/20/solid';
import { computed } from 'vue';

import { useQuery } from '@tanstack/vue-query';
import { getCurrentOrganizationId } from '@/utils/useUser';
import ReportingTabNavbar from '@/Components/Common/Reporting/ReportingTabNavbar.vue';
import ReportTable from '@/Components/Common/Report/ReportTable.vue';
import { isAllowedToPerformPremiumAction, isBillingActivated } from '@/utils/billing';
import { canManageBilling, canUpdateOrganization } from '@/utils/permissions';
import PrimaryButton from '../packages/ui/src/Buttons/PrimaryButton.vue';
import { Link } from '@inertiajs/vue3';
import { fetchAllReports } from '@/utils/useReportsQuery';

const { data: reportsData } = useQuery({
    queryKey: computed(() => ['reports', getCurrentOrganizationId()]),
    enabled: !!getCurrentOrganizationId(),
    queryFn: async () => {
        const organizationId = getCurrentOrganizationId();
        if (!organizationId) throw new Error('No organization');
        const data = await fetchAllReports(organizationId);
        return { data };
    },
    staleTime: 1000 * 30,
});

const reports = computed(() => {
    return reportsData.value?.data ?? [];
});
</script>

<template>
    <AppLayout title="Reporting" data-testid="reporting_view" class="overflow-hidden">
        <MainContainer
            class="h-14 sm:h-16 border-b border-default-background-separator flex flex-wrap gap-y-3 justify-between items-center">
            <div class="flex items-center space-x-3 sm:space-x-6">
                <PageTitle :icon="ChartBarIcon" title="Reporting"></PageTitle>
                <ReportingTabNavbar active="shared" class="hidden sm:flex"></ReportingTabNavbar>
            </div>
        </MainContainer>
        <MainContainer class="sm:hidden py-2 border-b border-default-background-separator">
            <ReportingTabNavbar active="shared"></ReportingTabNavbar>
        </MainContainer>

        <div v-if="!isAllowedToPerformPremiumAction()">
            <div class="py-12">
                <div
                    class="rounded-full flex items-center justify-center w-20 h-20 mx-auto border border-border-tertiary bg-secondary">
                    <UserGroupIcon class="w-12"></UserGroupIcon>
                </div>
                <div class="max-w-sm text-center mx-auto py-4 text-base">
                    <p class="py-1">
                        <slot></slot>
                    </p>
                    <p class="py-1">
                        If you want to use <strong>sharable reports</strong> ,
                        <strong>please upgrade to a paid plan</strong>.
                    </p>

                    <Link v-if="isBillingActivated() && canManageBilling()" href="/billing">
                        <PrimaryButton
                            v-if="isBillingActivated() && canUpdateOrganization()"
                            type="button"
                            class="mt-6"
                            :icon="CreditCardIcon">
                            Go to Billing
                        </PrimaryButton>
                    </Link>
                </div>
            </div>
        </div>

        <ReportTable
            v-if="reports.length > 0 || isAllowedToPerformPremiumAction()"
            :reports="reports"></ReportTable>
    </AppLayout>
</template>
