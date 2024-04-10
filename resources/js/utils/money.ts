export function formatMoney(
    amount: number,
    currency: string = getOrganizationCurrencyString()
) {
    return new Intl.NumberFormat('en-US', {
        style: 'currency',
        currency: currency,
    }).format(amount);
}

export function formatCents(amount: number) {
    return formatMoney(amount / 100);
}

export function getOrganizationCurrencyString() {
    return 'EUR';
}

export function getOrganizationCurrencySymbol() {
    return '€';
}
