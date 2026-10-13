<script setup lang="ts">
import { Dialog, DialogContent, DialogTitle, DialogDescription } from './dialog';
import { LockClosedIcon } from '@heroicons/vue/16/solid';
import { computed, type Component } from 'vue';
import MockupBillableRates from './UpgradeModal/MockupBillableRates.vue';
import MockupEstimates from './UpgradeModal/MockupEstimates.vue';
import MockupGoals from './UpgradeModal/MockupGoals.vue';
import MockupInvoicing from './UpgradeModal/MockupInvoicing.vue';
import MockupPdfReports from './UpgradeModal/MockupPdfReports.vue';
import MockupShareableReports from './UpgradeModal/MockupShareableReports.vue';
import UpgradeCtaFooter from './UpgradeModal/UpgradeCtaFooter.vue';
import { featureIcons } from './UpgradeModal/featureIcons';
import {
    professionalFeatures,
    type ProfessionalFeatureKey,
    type UpgradeFeature,
} from './UpgradeModal/useUpgradeCta';

const show = defineModel<boolean>('show', { default: false });

const props = defineProps<{
    // The Professional feature the user tried to use, picks the mock-up and the copy
    feature: UpgradeFeature;
}>();

const contents: Record<
    UpgradeFeature,
    {
        mockup: Component;
        pill: string;
        headline: string;
        body: string;
        // Feature of the pricing page that is listed first, if the feature is listed there
        featureKey?: ProfessionalFeatureKey;
    }
> = {
    'billable-rates': {
        mockup: MockupBillableRates,
        pill: 'Unlock billable rates',
        headline: 'Turn tracked hours into revenue',
        body: 'Upgrade to solidtime Professional to track billable hours',
        featureKey: 'rates',
    },
    estimates: {
        mockup: MockupEstimates,
        pill: 'Unlock time estimates',
        headline: 'Keep every project on budget',
        body: 'Upgrade to solidtime Professional to set time estimates',
        featureKey: 'estimates',
    },
    goals: {
        mockup: MockupGoals,
        pill: 'Unlock unlimited goals',
        headline: 'Set a goal for everything that matters',
        body: 'Upgrade to solidtime Professional to track unlimited goals',
    },
    invoicing: {
        mockup: MockupInvoicing,
        pill: 'Unlock invoicing',
        headline: 'Get paid for every tracked hour',
        body: 'Upgrade to solidtime Professional to create invoices',
        featureKey: 'invoicing',
    },
    'pdf-reports': {
        mockup: MockupPdfReports,
        pill: 'Unlock PDF reports',
        headline: 'Send reports your clients will read',
        body: 'Upgrade to solidtime Professional to export PDF reports',
        featureKey: 'pdf',
    },
    'shareable-reports': {
        mockup: MockupShareableReports,
        pill: 'Unlock shareable reports',
        headline: 'Share live reports with one link',
        body: 'Upgrade to solidtime Professional to share reports',
        featureKey: 'shared',
    },
};

const content = computed(() => contents[props.feature]);

// The feature the user tried to use comes first
const orderedFeatures = computed(() => [
    ...professionalFeatures.filter((feature) => feature.key === content.value.featureKey),
    ...professionalFeatures.filter((feature) => feature.key !== content.value.featureKey),
]);
</script>

<template>
    <Dialog v-model:open="show">
        <DialogContent class="sm:max-w-xl p-0 overflow-hidden">
            <div class="relative">
                <component :is="content.mockup" />
                <div
                    class="absolute bottom-0 left-1/2 z-10 -translate-x-1/2 translate-y-1/2 whitespace-nowrap">
                    <div
                        class="flex items-center gap-1.5 rounded-full border border-gray-200 bg-white px-3 py-1 dark:border-border-secondary dark:bg-default-background text-xs font-medium text-text-primary shadow-sm">
                        <LockClosedIcon class="h-3.5 w-3.5 text-accent-500 dark:text-accent-300" />
                        {{ content.pill }}
                    </div>
                </div>
            </div>

            <div class="px-8 pt-9 text-center">
                <DialogTitle class="text-xl font-semibold tracking-tight text-text-primary">
                    {{ content.headline }}
                </DialogTitle>
                <DialogDescription class="mt-1.5 text-sm text-text-secondary">
                    {{ content.body }}
                </DialogDescription>
            </div>

            <div class="grid grid-cols-2 gap-x-6 gap-y-4 px-8 pt-9">
                <div v-for="item in orderedFeatures" :key="item.key" class="flex gap-3">
                    <div
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-border-tertiary bg-card-background">
                        <component
                            :is="featureIcons[item.key]"
                            class="h-3.5 w-3.5 text-text-secondary" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-text-primary">{{ item.title }}</p>
                        <p class="mt-0.5 text-xs leading-relaxed text-text-tertiary">
                            {{ item.description }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="px-8 pt-9 pb-6">
                <UpgradeCtaFooter
                    align="stack"
                    label="Upgrade to Professional"
                    class="mx-auto max-w-xs"
                    @close="show = false" />
            </div>
        </DialogContent>
    </Dialog>
</template>
