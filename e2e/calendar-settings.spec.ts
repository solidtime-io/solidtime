import type { Page } from '@playwright/test';
import { expect } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';

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

function getCalendarTitle(page: Page) {
    return page.getByTestId('calendar-title');
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

        // Close the popover by pressing Escape
        await page.keyboard.press('Escape');

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

    test('start time change is applied to calendar and rejects invalid values', async ({
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

        // Try to set start time to 6 PM (invalid: equals end time) — should be rejected
        await page.getByLabel('Start Time').click();
        await page.getByRole('option', { name: '6:00 PM' }).click();

        // Should be rejected — start time stays at 8 AM
        await expect(page.getByLabel('Start Time')).toContainText('8:00 AM');

        // Close the popover
        await page.keyboard.press('Escape');

        // Calendar should no longer show hours before 8 AM
        await expect(page.locator('.fc-timegrid-slot[data-time="07:00:00"]')).toHaveCount(0);
        await expect(page.locator('.fc-timegrid-slot[data-time="08:00:00"]')).not.toHaveCount(0);
    });

    test('end time change is applied to calendar and rejects invalid values', async ({ page }) => {
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

        // Try to set end time to 8 AM (invalid: equals start time) — should be rejected
        await page.getByLabel('End Time').click();
        await page.getByRole('option', { name: '8:00 AM' }).click();

        // Should be rejected — end time stays at 6 PM
        await expect(page.getByLabel('End Time')).toContainText('6:00 PM');

        // Close the popover
        await page.keyboard.press('Escape');

        // Calendar should no longer show hours at or after 6 PM
        await expect(page.locator('.fc-timegrid-slot[data-time="18:00:00"]')).toHaveCount(0);
        await expect(page.locator('.fc-timegrid-slot[data-time="17:00:00"]')).not.toHaveCount(0);
    });

    test('grid scale affects number of calendar slots', async ({ page }) => {
        await goToCalendar(page);

        // Count slots with default 15-min scale
        const defaultSlotCount = await page.locator('.fc-timegrid-slot').count();

        // Change to 30 min scale (should halve the slots)
        await openSettingsPopover(page);
        await page.getByLabel('Grid Scale').click();
        await page.getByRole('option', { name: '30 min' }).click();
        await page.keyboard.press('Escape');

        // Wait for FullCalendar to re-render with new slot count
        await expect(async () => {
            const count = await page.locator('.fc-timegrid-slot').count();
            expect(count).toBeLessThan(defaultSlotCount);
        }).toPass({ timeout: 5000 });

        const largerSlotCount = await page.locator('.fc-timegrid-slot').count();

        // Navigate away and back to get a clean calendar mount
        await page.goto(PLAYWRIGHT_BASE_URL + '/time');
        await goToCalendar(page);

        // Change to 5 min scale (many more slots)
        await openSettingsPopover(page);
        await page.getByLabel('Grid Scale').click();
        await page.getByRole('option', { name: '5 min', exact: true }).click();
        await page.keyboard.press('Escape');

        // Wait for FullCalendar to re-render with new slot count
        await expect(async () => {
            const count = await page.locator('.fc-timegrid-slot').count();
            expect(count).toBeGreaterThan(largerSlotCount);
        }).toPass({ timeout: 5000 });
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

        // Close the popover
        await page.keyboard.press('Escape');

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

test.describe('Calendar Toolbar', () => {
    test('prev and next buttons navigate the calendar', async ({ page }) => {
        await goToCalendar(page);

        const initialTitle = await getCalendarTitle(page).textContent();

        // Click next
        await page.getByRole('button', { name: 'Next', exact: true }).click();
        await expect(page.locator('.fc')).toBeVisible();

        const nextTitle = await getCalendarTitle(page).textContent();
        expect(nextTitle).not.toBe(initialTitle);

        // Click prev — should go back to original
        await page.getByRole('button', { name: 'Previous', exact: true }).click();
        await expect(page.locator('.fc')).toBeVisible();

        const backTitle = await getCalendarTitle(page).textContent();
        expect(backTitle).toBe(initialTitle);
    });

    test('today button returns to current week', async ({ page }) => {
        await goToCalendar(page);

        const initialTitle = await getCalendarTitle(page).textContent();

        // Navigate away
        await page.getByRole('button', { name: 'Next', exact: true }).click();
        await page.getByRole('button', { name: 'Next', exact: true }).click();

        const awayTitle = await getCalendarTitle(page).textContent();
        expect(awayTitle).not.toBe(initialTitle);

        // Click today
        await page.getByRole('button', { name: 'today', exact: true }).click();
        await expect(page.locator('.fc')).toBeVisible();

        const todayTitle = await getCalendarTitle(page).textContent();
        expect(todayTitle).toBe(initialTitle);
    });

    test('view switcher toggles between week and day views', async ({ page }) => {
        await goToCalendar(page);

        // Default should be week view — verify multiple day columns exist
        await expect(page.locator('.fc-col-header-cell')).not.toHaveCount(1);

        // Switch to day view
        await page.getByRole('tab', { name: 'day', exact: true }).click();
        await expect(page.locator('.fc')).toBeVisible();

        // Day view should show exactly 1 day column
        await expect(page.locator('.fc-col-header-cell')).toHaveCount(1);

        // Switch back to week view
        await page.getByRole('tab', { name: 'week', exact: true }).click();
        await expect(page.locator('.fc')).toBeVisible();

        // Week view should show multiple day columns again
        await expect(page.locator('.fc-col-header-cell')).not.toHaveCount(1);
    });
});
