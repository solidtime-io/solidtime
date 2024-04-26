import { usePage } from '@inertiajs/vue3';

const page = usePage<{
    auth: {
        user: {
            current_team: {
                currency: string;
            };
        };
    };
}>();

export function formatMoney(
    amount: number,
    currency: string = getOrganizationCurrencyString()
) {
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: currency,
    }).format(amount);
}

export function formatCents(amount: number) {
    return formatMoney(amount / 100);
}

export function getOrganizationCurrencyString() {
    return page.props?.auth?.user?.current_team?.currency ?? 'EUR';
}

export function getOrganizationCurrencySymbol() {
    return (0)
        .toLocaleString('de-DE', {
            style: 'currency',
            currency: getOrganizationCurrencyString(),
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        })
        .replace(/\d/g, '')
        .trim();
}
