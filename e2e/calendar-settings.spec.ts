import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';

async function goToCalendar(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/calendar');
    await expect(page.locator('.fc')).toBeVisible();
}

async function openSettingsPopover(page: Page) {
    await page.getByRole('button', { name: 'Calendar settings' }).click();
    await expect(page.getByText('Calendar Settings')).toBeVisible();
}

async function clearCalendarSettings(page: Page) {
    await page.evaluate(() => localStorage.removeItem('solidtime:calendar-settings'));
}

test.describe('Calendar Settings', () => {
    test.beforeEach(async ({ page }) => {
        await clearCalendarSettings(page);
    });

    test('settings popover shows all fields with correct defaults', async ({ page }) => {
        await goToCalendar(page);
        await openSettingsPopover(page);

        await expect(page.getByLabel('Snap Interval')).toContainText('15 min');
        await expect(page.getByLabel('Start Time')).toContainText('12:00 AM');
        await expect(page.getByLabel('End Time')).toContainText('12:00 AM (next)');
        await expect(page.getByLabel('Grid Scale')).toContainText('15 min');
    });

    test('snap interval can be changed and persists across reload', async ({ page }) => {
        await goToCalendar(page);
        await openSettingsPopover(page);

        // Change snap interval to 30 min
        await page.getByLabel('Snap Interval').click();
        await page.getByRole('option', { name: '30 min' }).click();
        await page.locator('.fc-toolbar-title').click();

        // Verify localStorage was updated
        const stored = await page.evaluate(() =>
            JSON.parse(localStorage.getItem('solidtime:calendar-settings') || '{}')
        );
        expect(stored.snapMinutes).toBe(30);

        // Reload and verify persistence
        await page.reload();
        await expect(page.locator('.fc')).toBeVisible();
        await openSettingsPopover(page);
        await expect(page.getByLabel('Snap Interval')).toContainText('30 min');
    });

    test('start time change is applied to calendar and rejects values >= end time', async ({
        page,
    }) => {
        await goToCalendar(page);

        // Verify 7 AM slot exists with default start (00:00)
        await expect(page.locator('.fc-timegrid-slot[data-time="07:00:00"]')).not.toHaveCount(0);

        await openSettingsPopover(page);

        // Set end time to 6 PM first
        await page.getByLabel('End Time').click();
        await page.getByRole('option', { name: '6:00 PM' }).click();

        // Change start time to 8 AM (valid)
        await page.getByLabel('Start Time').click();
        await page.getByRole('option', { name: '8:00 AM' }).click();
        await page.locator('.fc-toolbar-title').click();

        // Calendar should no longer show hours before 8 AM
        await expect(page.locator('.fc-timegrid-slot[data-time="07:00:00"]')).toHaveCount(0);
        await expect(page.locator('.fc-timegrid-slot[data-time="08:00:00"]')).not.toHaveCount(0);

        // Try to set start time to 6 PM (invalid: equals end time)
        await openSettingsPopover(page);
        await page.getByLabel('Start Time').click();
        await page.getByRole('option', { name: '6:00 PM' }).click();

        // Should be rejected — start time stays at 8 AM
        await expect(page.getByLabel('Start Time')).toContainText('8:00 AM');
    });

    test('end time change is applied to calendar and rejects values <= start time', async ({
        page,
    }) => {
        await goToCalendar(page);

        // Verify 19:00 slot exists with default end (24:00)
        await expect(page.locator('.fc-timegrid-slot[data-time="19:00:00"]')).not.toHaveCount(0);

        await openSettingsPopover(page);

        // Set start time to 8 AM first
        await page.getByLabel('Start Time').click();
        await page.getByRole('option', { name: '8:00 AM' }).click();

        // Change end time to 6 PM (valid)
        await page.getByLabel('End Time').click();
        await page.getByRole('option', { name: '6:00 PM' }).click();
        await page.locator('.fc-toolbar-title').click();

        // Calendar should no longer show hours at or after 6 PM
        await expect(page.locator('.fc-timegrid-slot[data-time="18:00:00"]')).toHaveCount(0);
        await expect(page.locator('.fc-timegrid-slot[data-time="17:00:00"]')).not.toHaveCount(0);

        // Try to set end time to 8 AM (invalid: equals start time)
        await openSettingsPopover(page);
        await page.getByLabel('End Time').click();
        await page.getByRole('option', { name: '8:00 AM' }).click();

        // Should be rejected — end time stays at 6 PM
        await expect(page.getByLabel('End Time')).toContainText('6:00 PM');
    });

    test('grid scale affects number of calendar slots', async ({ page }) => {
        await goToCalendar(page);

        // Count slots with default 15-min scale
        const defaultSlotCount = await page.locator('.fc-timegrid-slot').count();

        // Change to 30 min scale (should halve the slots)
        await openSettingsPopover(page);
        await page.getByLabel('Grid Scale').click();
        await page.getByRole('option', { name: '30 min' }).click();
        await page.locator('.fc-toolbar-title').click();

        const largerSlotCount = await page.locator('.fc-timegrid-slot').count();
        expect(largerSlotCount).toBeLessThan(defaultSlotCount);

        // Change to 5 min scale (should have many more slots)
        await openSettingsPopover(page);
        await page.getByLabel('Grid Scale').click();
        await page.getByRole('option', { name: '5 min', exact: true }).click();
        await page.locator('.fc-toolbar-title').click();

        const smallerSlotCount = await page.locator('.fc-timegrid-slot').count();
        expect(smallerSlotCount).toBeGreaterThan(defaultSlotCount);
    });

    test('all settings persist across navigation', async ({ page }) => {
        await goToCalendar(page);
        await openSettingsPopover(page);

        // Change every setting
        await page.getByLabel('Snap Interval').click();
        await page.getByRole('option', { name: '5 min', exact: true }).click();
        await page.getByLabel('Start Time').click();
        await page.getByRole('option', { name: '6:00 AM' }).click();
        await page.getByLabel('End Time').click();
        await page.getByRole('option', { name: '10:00 PM' }).click();
        await page.getByLabel('Grid Scale').click();
        await page.getByRole('option', { name: '30 min' }).click();
        await page.locator('.fc-toolbar-title').click();

        // Navigate away and back
        await page.goto(PLAYWRIGHT_BASE_URL + '/time');
        await goToCalendar(page);

        // Verify all settings persisted
        await openSettingsPopover(page);
        await expect(page.getByLabel('Snap Interval')).toContainText('5 min');
        await expect(page.getByLabel('Start Time')).toContainText('6:00 AM');
        await expect(page.getByLabel('End Time')).toContainText('10:00 PM');
        await expect(page.getByLabel('Grid Scale')).toContainText('30 min');
    });
});
