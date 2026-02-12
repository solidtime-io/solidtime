<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import PageTitle from '@/Components/Common/PageTitle.vue';
import {
    ChartBarIcon,
    ChevronLeftIcon,
    ChevronDoubleLeftIcon,
    ChevronRightIcon,
    ChevronDoubleRightIcon,
    CreditCardIcon,
    UserGroupIcon,
} from '@heroicons/vue/20/solid';
import { computed, ref, watch } from 'vue';

import { api, type ReportIndexResponse } from '@/packages/api/src';
import {
    PaginationEllipsis,
    PaginationFirst,
    PaginationLast,
    PaginationList,
    PaginationListItem,
    PaginationNext,
    PaginationPrev,
    PaginationRoot,
} from 'radix-vue';
import { useQuery, useQueryClient } from '@tanstack/vue-query';
import { getCurrentOrganizationId } from '@/utils/useUser';
import ReportingTabNavbar from '@/Components/Common/Reporting/ReportingTabNavbar.vue';
import ReportTable from '@/Components/Common/Report/ReportTable.vue';
import { isAllowedToPerformPremiumAction, isBillingActivated } from '@/utils/billing';
import { canManageBilling, canUpdateOrganization } from '@/utils/permissions';
import PrimaryButton from '../packages/ui/src/Buttons/PrimaryButton.vue';
import { Link } from '@inertiajs/vue3';

const pageLimit = 15;
const currentPage = ref(1);

const { data: reportsResponse } = useQuery<ReportIndexResponse>({
    queryKey: ['reports', currentPage],
    enabled: !!getCurrentOrganizationId(),
    queryFn: () =>
        api.getReports({
            params: {
                organization: getCurrentOrganizationId() || '',
            },
        }),
});

const reports = computed(() => {
    return reportsResponse.value?.data ?? [];
});

const totalPages = computed(() => {
    return 1;
});

const queryClient = useQueryClient();
async function updateFilteredTimeEntries() {
    await queryClient.invalidateQueries({
        queryKey: ['reports'],
    });
}
watch(currentPage, () => {
    updateFilteredTimeEntries();
});
</script>

<template>
    <AppLayout title="Reporting" data-testid="reporting_view" class="overflow-hidden">
        <MainContainer
            class="h-14 sm:h-16 border-b border-default-background-separator flex flex-wrap gap-y-3 justify-between items-center">
            <div class="flex items-center space-x-3 sm:space-x-6">
                <PageTitle :icon="ChartBarIcon" title="Reporting"></PageTitle>
                <ReportingTabNavbar
                    active="shared"
                    class="hidden sm:flex"></ReportingTabNavbar>
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

        <PaginationRoot
            v-if="reports.length > 0 || isAllowedToPerformPremiumAction()"
            v-model:page="currentPage"
            :total="totalPages"
            :items-per-page="pageLimit"
            class="flex justify-center items-center py-8"
            :sibling-count="1"
            show-edges>
            <PaginationList v-slot="{ items }" class="flex items-center space-x-1 relative">
                <div class="pr-2 flex items-center space-x-1 border-r border-border-primary mr-1">
                    <PaginationFirst class="navigation-item">
                        <ChevronDoubleLeftIcon class="w-4"> </ChevronDoubleLeftIcon>
                    </PaginationFirst>
                    <PaginationPrev class="mr-4 navigation-item">
                        <ChevronLeftIcon class="w-4 text-text-tertiary hover:text-text-primary">
                        </ChevronLeftIcon>
                    </PaginationPrev>
                </div>
                <template v-for="(page, index) in items">
                    <PaginationListItem
                        v-if="page.type === 'page'"
                        :key="index"
                        class="pagination-item"
                        :value="page.value">
                        {{ page.value }}
                    </PaginationListItem>
                    <PaginationEllipsis
                        v-else
                        :key="page.type"
                        :index="index"
                        class="PaginationEllipsis">
                        <div class="px-2">&#8230;</div>
                    </PaginationEllipsis>
                </template>
                <div class="!ml-2 pl-2 flex items-center space-x-1 border-l border-border-primary">
                    <PaginationNext class="navigation-item">
                        <ChevronRightIcon
                            class="w-4 text-text-tertiary hover:text-text-primary"></ChevronRightIcon>
                    </PaginationNext>
                    <PaginationLast class="navigation-item">
                        <ChevronDoubleRightIcon
                            class="w-4 text-text-tertiary hover:text-text-primary"></ChevronDoubleRightIcon>
                    </PaginationLast>
                </div>
            </PaginationList>
        </PaginationRoot>
    </AppLayout>
</template>
<style lang="postcss">
.navigation-item {
    @apply bg-quaternary h-8 w-8 flex items-center justify-center rounded border border-border-primary text-text-tertiary hover:text-text-primary transition cursor-pointer hover:border-border-secondary hover:bg-secondary focus-visible:text-text-primary focus-visible:outline-0 focus-visible:ring-2 focus-visible:ring-ring;
}

.pagination-item {
    @apply bg-secondary h-8 w-8 flex items-center justify-center rounded border border-border-tertiary text-text-secondary hover:text-text-primary transition cursor-pointer hover:border-border-secondary hover:bg-secondary focus-visible:text-text-primary focus-visible:outline-0 focus-visible:ring-2 focus-visible:ring-ring;
}
.pagination-item[data-selected] {
    @apply text-text-primary bg-accent-300/10 border border-accent-300/20 rounded-md font-medium hover:bg-accent-300/20 active:bg-accent-300/20 outline-0 focus-visible:ring-2 focus:ring-ring transition ease-in-out duration-150;
}
</style>
