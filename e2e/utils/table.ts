import type { Locator, Page } from '@playwright/test';

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

/**
 * The visual order of the given seeded names within the table, ignoring any other rows.
 */
export async function getSeededRowOrder(table: Locator, seeded: string[]): Promise<string[]> {
    const rowNames = await getTableRowNames(table);
    return rowNames
        .map((rowName) => seeded.find((name) => rowName.includes(name)))
        .filter((name): name is string => Boolean(name));
}

/**
 * Drop a table's persisted sort/filter state so a test starts from the defaults.
 */
export async function clearTableState(page: Page, key: string) {
    await page.evaluate((storageKey) => {
        localStorage.removeItem(storageKey);
    }, key);
}
