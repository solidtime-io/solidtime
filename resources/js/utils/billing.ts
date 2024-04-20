import { usePage } from '@inertiajs/vue3';

export function isBillingActivated() {
    const page = usePage<{
        has_billing_extension: boolean;
    }>();

    return page.props.has_billing_extension;
}
