export type NumberFormat =
    | 'point-comma'
    | 'comma-point'
    | 'space-comma'
    | 'space-point'
    | 'apostrophe-point';

/**
 * Formats a number according to the specified format
 * @param value - The number to format
 * @param format - The format to use
 * @returns The formatted number as a string
 */
export function formatNumber(value: number, format?: string): string {
    // Convert to fixed 2 decimal places first
    const parts = value.toFixed(2).split('.');
    const wholePart = parts[0] ?? '0';
    const decimalPart = parts[1] ?? '00';

    // Format the whole number part based on the format
    let formattedWhole: string;
    switch (format) {
        case 'point-comma':
            formattedWhole = wholePart.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
            return `${formattedWhole},${decimalPart}`;
        case 'comma-point':
            formattedWhole = wholePart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            return `${formattedWhole}.${decimalPart}`;
        case 'space-comma':
            formattedWhole = wholePart.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            return `${formattedWhole},${decimalPart}`;
        case 'space-point':
            formattedWhole = wholePart.replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
            return `${formattedWhole}.${decimalPart}`;
        case 'apostrophe-point':
            formattedWhole = wholePart.replace(/\B(?=(\d{3})+(?!\d))/g, "'");
            return `${formattedWhole}.${decimalPart}`;
        default:
            return value.toString();
    }
}
