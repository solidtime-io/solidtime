import { usePage } from '@inertiajs/vue3';
import { getDayJsInstance } from '@/packages/ui/src/utils/time';

export function isBillingActivated() {
    const page = usePage<{
        has_billing_extension: boolean;
    }>();

    return page.props.has_billing_extension;
}

export function isInvoicingActivated() {
    const page = usePage<{
        has_invoicing_extension: boolean;
    }>();

    return page.props.has_invoicing_extension;
}

export function isGoalsExtensionActivated() {
    const page = usePage<{
        has_goals_extension: boolean;
    }>();

    return page.props.has_goals_extension;
}

export function isInTrial() {
    const page = usePage<{
        billing: {
            has_trial: boolean;
        };
    }>();

    return page.props.billing.has_trial;
}

export function daysLeftInTrial() {
    const page = usePage<{
        billing: {
            trial_until: string;
        };
    }>();

    return (
        getDayJsInstance()(page.props.billing.trial_until).diff(getDayJsInstance()(), 'days') + 1
    );
}

export function isBlocked() {
    const page = usePage<{
        billing: {
            is_blocked: boolean;
        };
    }>();

    return page.props.billing.is_blocked;
}

/**
 * The maximum number of goals the organization can have, null means unlimited.
 * Archived goals count towards the limit as well.
 */
export function goalLimit(): number | null {
    const page = usePage<{
        billing: {
            goal_limit: number | null;
        } | null;
    }>();

    return page.props.billing?.goal_limit ?? null;
}

export function canCreateMoreGoals(goalCount: number): boolean {
    const limit = goalLimit();

    return limit === null || goalCount < limit;
}

export function isFreePlan() {
    return !hasActiveSubscription() && !isInTrial();
}

export function hasActiveSubscription() {
    const page = usePage<{
        billing: {
            has_subscription: boolean;
        };
    }>();

    return page.props.billing.has_subscription;
}

export function isAllowedToPerformPremiumAction() {
    return (
        !isBillingActivated() ||
        (isBillingActivated() && hasActiveSubscription()) ||
        (isBillingActivated() && isInTrial())
    );
}
