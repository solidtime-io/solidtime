import { expect } from '@playwright/test';
import type { Locator, Page } from '@playwright/test';

/**
 * Helpers for the shared TimezoneCombobox and WeekStartSelect components used in
 * the profile form and the goal form.
 */

export function timezoneField(scope: Page | Locator) {
    // The trigger is named "Timezone: <current value>"
    return scope.getByRole('button', { name: /^Timezone/ });
}

export function weekStartField(scope: Page | Locator) {
    return scope.getByRole('combobox', { name: 'Start of the week' });
}

export async function selectTimezone(
    page: Page,
    scope: Page | Locator,
    timezone: string,
    search: string = timezone
) {
    await timezoneField(scope).click();
    await page.getByRole('combobox', { name: 'Search timezones' }).fill(search);
    await page.getByRole('option', { name: timezone, exact: true }).click();
    await expect(timezoneField(scope)).toHaveText(timezone);
}

export async function selectWeekStart(page: Page, scope: Page | Locator, weekdayLabel: string) {
    await weekStartField(scope).click();
    await page.getByRole('option', { name: weekdayLabel, exact: true }).click();
    await expect(weekStartField(scope)).toHaveText(weekdayLabel);
}
