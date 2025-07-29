import { formatNumber, type NumberFormat } from './number';

export type CurrencyFormat =
    | 'iso-code-before-with-space'
    | 'iso-code-after-with-space'
    | 'symbol-before'
    | 'symbol-after'
    | 'symbol-before-with-space'
    | 'symbol-after-with-space';

function formatMoney(
    amount: number,
    currency?: string,
    format?: CurrencyFormat,
    currencySymbol?: string,
    numberFormat?: NumberFormat
) {
    const formattedAmount = formatNumber(amount, numberFormat);

    switch (format) {
        case 'iso-code-before-with-space':
            return `${currency} ${formattedAmount}`;
        case 'iso-code-after-with-space':
            return `${formattedAmount} ${currency}`;
        case 'symbol-before':
            return `${currencySymbol}${formattedAmount}`;
        case 'symbol-after':
            return `${formattedAmount}${currencySymbol}`;
        case 'symbol-before-with-space':
            return `${currencySymbol} ${formattedAmount}`;
        case 'symbol-after-with-space':
            return `${formattedAmount} ${currencySymbol}`;
    }
}

export function formatCents(
    amount: number,
    currency?: string,
    format?: CurrencyFormat,
    currencySymbol?: string,
    numberFormat?: NumberFormat
) {
    return formatMoney(amount / 100, currency, format, currencySymbol, numberFormat);
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
