import { formatCents } from '../../resources/js/packages/ui/src/utils/money';
import type { CurrencyFormat } from '../../resources/js/packages/ui/src/utils/money';
import { NumberFormat } from '../../resources/js/packages/ui/src/utils/number';

export function formatCentsWithOrganizationDefaults(
    cents: number,
    currencyCode: string = 'EUR',
    currencySymbol: string = '€'
): string {
    return formatCents(
        cents,
        currencyCode,
        'iso-code-after-with-space' as CurrencyFormat,
        currencySymbol,
        'point-comma' as NumberFormat
    );
} 