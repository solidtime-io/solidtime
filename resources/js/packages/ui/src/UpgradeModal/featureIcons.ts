import type { Component } from 'vue';
import {
    AdjustmentsHorizontalIcon,
    ClockIcon,
    CurrencyEuroIcon,
    DocumentArrowDownIcon,
    DocumentTextIcon,
    LinkIcon,
} from '@heroicons/vue/16/solid';
import type { ProfessionalFeatureKey } from './useUpgradeCta';

export const featureIcons: Record<ProfessionalFeatureKey, Component> = {
    rates: CurrencyEuroIcon,
    invoicing: DocumentTextIcon,
    estimates: ClockIcon,
    rounding: AdjustmentsHorizontalIcon,
    pdf: DocumentArrowDownIcon,
    shared: LinkIcon,
};
