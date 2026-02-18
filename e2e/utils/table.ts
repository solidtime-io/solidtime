import type { Locator } from '@playwright/test';

/**
 * Extract the first cell's text content from each row in a table.
 * Useful for reading the ordered names/labels from a sorted table.
 */
export async function getTableRowNames(table: Locator): Promise<string[]> {
    const rows = table.getByRole('row');
    const count = await rows.count();
    const names: string[] = [];
    for (let i = 0; i < count; i++) {
        const text = await rows.nth(i).locator('div').first().textContent();
        if (text) names.push(text.trim());
    }
    return names;
}
