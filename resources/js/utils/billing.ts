import { usePage } from '@inertiajs/vue3';

export function isBillingActivated() {
    const page = usePage<{
        has_billing_extension: boolean;
    }>();

    return page.props.has_billing_extension;
}

export function hasActiveSubscription() {
    const page = usePage<{
        billing: {
            has_subscription: boolean;
        };
    }>();

    return page.props.billing.has_subscription;
}
