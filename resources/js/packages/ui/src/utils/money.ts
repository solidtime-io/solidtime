function formatMoney(amount: number, currency: string) {
    return new Intl.NumberFormat('de-DE', {
        style: 'currency',
        currency: currency,
    }).format(amount);
}

export function formatCents(amount: number, currency: string) {
    return formatMoney(amount / 100, currency);
}

export function getOrganizationCurrencySymbol(currency: string) {
    return (0)
        .toLocaleString('de-DE', {
            style: 'currency',
            currency: currency,
            minimumFractionDigits: 0,
            maximumFractionDigits: 0,
        })
        .replace(/\d/g, '')
        .trim();
}
