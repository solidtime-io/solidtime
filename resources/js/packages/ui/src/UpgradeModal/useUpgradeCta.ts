import { computed, inject, type ComputedRef, type InjectionKey } from 'vue';

export interface UpgradeCta {
    canUpgrade: ComputedRef<boolean>;
    goToBilling: () => void;
}

export const upgradeCtaKey: InjectionKey<UpgradeCta> = Symbol('upgradeCta');

/**
 * Shared call to action for the upgrade modal variations: members who can manage billing are
 * sent to the billing page, everyone else is asked to contact the organization owner.
 *
 * The app layout provides who can upgrade and how to get to the billing page. Without a
 * provider (e.g. other consumers of this package) nobody can upgrade from the modal.
 */
export function useUpgradeCta(): UpgradeCta {
    return inject(upgradeCtaKey, {
        canUpgrade: computed(() => false),
        goToBilling: () => {},
    });
}

// Note: Copy mirrors https://www.solidtime.io/pricing so the gate and the pricing page match.
export const pricingUrl = 'https://www.solidtime.io/pricing';

// Professional features the upgrade modal has a variant for
export type UpgradeFeature =
    'billable-rates' | 'estimates' | 'goals' | 'invoicing' | 'pdf-reports' | 'shareable-reports';

export type ProfessionalFeatureKey =
    'rates' | 'invoicing' | 'estimates' | 'rounding' | 'pdf' | 'shared';

export type ProfessionalFeature = {
    key: ProfessionalFeatureKey;
    title: string;
    description: string;
};

// Note: Billing and reporting features of the Professional plan on https://www.solidtime.io/pricing
export const professionalFeatures: ProfessionalFeature[] = [
    {
        key: 'rates',
        title: 'Billable rates',
        description: 'Set rates per organization, project or member.',
    },
    {
        key: 'invoicing',
        title: 'Invoicing',
        description: 'Create invoices straight from your tracked time.',
    },
    {
        key: 'estimates',
        title: 'Time estimates',
        description: 'Budget hours per project and task and see what is left.',
    },
    {
        key: 'rounding',
        title: 'Rounding',
        description: 'Round time in reports, e.g. to 15 minutes.',
    },
    {
        key: 'pdf',
        title: 'PDF reports',
        description: 'Export clean reports to send to clients.',
    },
    {
        key: 'shared',
        title: 'Shareable reports',
        description: 'Share a live report link, no login needed.',
    },
];
