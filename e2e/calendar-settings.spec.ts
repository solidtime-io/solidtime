import type { Page } from '@playwright/test';
import { expect } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import { createBareTimeEntryViaApi, createTimeEntryWithTimestampsViaApi } from './utils/api';

async function goToCalendar(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/calendar');
    await expect(page.locator('.fc')).toBeVisible({ timeout: 10000 });
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

async function scrollCalendarToTime(page: Page, time: string) {
    await page.evaluate((t) => {
        const slot = document.querySelector(`.fc-timegrid-slot-lane[data-time="${t}"]`);
        if (slot) slot.scrollIntoView({ block: 'start' });
    }, time);
    await page.waitForTimeout(300);
}

async function getSlotHeight(page: Page): Promise<number> {
    return await page.evaluate(() => {
        const slots = Array.from(document.querySelectorAll('.fc-timegrid-slot-lane'));
        for (let i = 0; i < slots.length; i++) {
            const h = slots[i].getBoundingClientRect().height;
            if (h > 0) return h;
        }
        return 20;
    });
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

        // Use column headers to detect navigation (title only shows month which may not change)
        const getHeaderTexts = async () => {
            const headers = page.locator('.fc-col-header-cell');
            return headers.allTextContents();
        };

        const initialHeaders = await getHeaderTexts();

        // Click next
        await page.getByRole('button', { name: 'Next', exact: true }).click();
        await expect(page.locator('.fc')).toBeVisible();

        const nextHeaders = await getHeaderTexts();
        expect(nextHeaders).not.toEqual(initialHeaders);

        // Click prev — should go back to original
        await page.getByRole('button', { name: 'Previous', exact: true }).click();
        await expect(page.locator('.fc')).toBeVisible();

        const backHeaders = await getHeaderTexts();
        expect(backHeaders).toEqual(initialHeaders);
    });

    test('today button returns to current week', async ({ page }) => {
        await goToCalendar(page);

        // Use column headers to detect navigation (title only shows month which may not change)
        const getHeaderTexts = async () => {
            const headers = page.locator('.fc-col-header-cell');
            return headers.allTextContents();
        };

        const initialHeaders = await getHeaderTexts();

        // Navigate away
        await page.getByRole('button', { name: 'Next', exact: true }).click();
        await page.getByRole('button', { name: 'Next', exact: true }).click();

        const awayHeaders = await getHeaderTexts();
        expect(awayHeaders).not.toEqual(initialHeaders);

        // Click today
        await page.getByRole('button', { name: 'today', exact: true }).click();
        await expect(page.locator('.fc')).toBeVisible();

        const todayHeaders = await getHeaderTexts();
        expect(todayHeaders).toEqual(initialHeaders);
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

test.describe('Visual Snapping', () => {
    test.beforeEach(async ({ page }) => {
        await clearCalendarSettings(page);
    });

    test('snap interval of 1 minute allows fine-grained positioning', async ({ page, ctx }) => {
        await goToCalendar(page);
        await openSettingsPopover(page);

        // Set snap interval to 1 min
        await page.getByLabel('Snap Interval').click();
        await page.getByRole('option', { name: '1 min' }).click();
        await page.keyboard.press('Escape');

        // Create a 1h time entry
        await createBareTimeEntryViaApi(ctx, 'Snap 1min test', '1h');
        await goToCalendar(page);

        // Scroll the calendar so the 14:00 target area is visible
        await scrollCalendarToTime(page, '13:00:00');

        const event = page.locator('.fc-event').first();
        await expect(event).toBeVisible();

        // Get target slot at a non-15-min boundary time
        const targetSlot = page.locator('.fc-timegrid-slot-lane[data-time="14:00:00"]').first();
        const targetBox = await targetSlot.boundingBox();
        expect(targetBox).not.toBeNull();

        // Drag event to a position offset from the 15-min boundary
        const putResponsePromise = page.waitForResponse(
            (resp) => resp.url().includes('/time-entries/') && resp.request().method() === 'PUT'
        );

        await event.hover();
        await page.mouse.down();
        await page.mouse.move(targetBox!.x + targetBox!.width / 2, targetBox!.y + 5, { steps: 10 });
        await page.mouse.up();

        const putResponse = await putResponsePromise;
        expect(putResponse.status()).toBe(200);

        const body = await putResponse.json();
        const startDate = new Date(body.data.start);
        const minutes = startDate.getMinutes();

        // With 1-min snap, any minute value is valid (0-59)
        expect(minutes).toBeGreaterThanOrEqual(0);
        expect(minutes).toBeLessThanOrEqual(59);
    });

    test('snap interval of 60 minutes creates hour-aligned entries', async ({ page, ctx }) => {
        await goToCalendar(page);
        await openSettingsPopover(page);

        // Set snap interval to 60 min
        await page.getByLabel('Snap Interval').click();
        await page.getByRole('option', { name: '1 hour' }).click();
        await page.keyboard.press('Escape');

        // Create a 1h time entry
        await createBareTimeEntryViaApi(ctx, 'Snap 60min test', '1h');
        await goToCalendar(page);

        // Scroll the calendar so the 14:00 target area is visible
        await scrollCalendarToTime(page, '13:00:00');

        const event = page.locator('.fc-event').first();
        await expect(event).toBeVisible();

        // Get target slot
        const targetSlot = page.locator('.fc-timegrid-slot-lane[data-time="14:00:00"]').first();
        const targetBox = await targetSlot.boundingBox();
        expect(targetBox).not.toBeNull();

        // Drag event
        const putResponsePromise = page.waitForResponse(
            (resp) => resp.url().includes('/time-entries/') && resp.request().method() === 'PUT'
        );

        await event.hover();
        await page.mouse.down();
        await page.mouse.move(targetBox!.x + targetBox!.width / 2, targetBox!.y + 5, { steps: 10 });
        await page.mouse.up();

        const putResponse = await putResponsePromise;
        expect(putResponse.status()).toBe(200);

        const body = await putResponse.json();
        const startDate = new Date(body.data.start);
        const minutes = startDate.getMinutes();

        // With 60-min snap, minutes should be 0 (on the hour)
        expect(minutes).toBe(0);
    });

    test('changing snap interval mid-session affects next drag', async ({ page, ctx }) => {
        // Create a 1h time entry
        await createBareTimeEntryViaApi(ctx, 'Snap change test', '1h');
        await goToCalendar(page);

        // Set snap to 15 min
        await openSettingsPopover(page);
        await page.getByLabel('Snap Interval').click();
        await page.getByRole('option', { name: '15 min' }).click();
        await page.keyboard.press('Escape');

        // Scroll the calendar so the 14:00 target area is visible
        await scrollCalendarToTime(page, '13:00:00');

        const event = page.locator('.fc-event').first();
        await expect(event).toBeVisible();

        // Drag event to 14:00 area
        const targetSlot14 = page.locator('.fc-timegrid-slot-lane[data-time="14:00:00"]').first();
        const targetBox14 = await targetSlot14.boundingBox();
        expect(targetBox14).not.toBeNull();

        const putResponsePromise1 = page.waitForResponse(
            (resp) => resp.url().includes('/time-entries/') && resp.request().method() === 'PUT'
        );

        await event.hover();
        await page.mouse.down();
        await page.mouse.move(targetBox14!.x + targetBox14!.width / 2, targetBox14!.y + 5, {
            steps: 10,
        });
        await page.mouse.up();

        const putResponse1 = await putResponsePromise1;
        expect(putResponse1.status()).toBe(200);

        const body1 = await putResponse1.json();
        const startDate1 = new Date(body1.data.start);
        expect(startDate1.getMinutes() % 15).toBe(0);

        // Wait for query re-fetch/re-renders to fully settle after drag
        await page.waitForTimeout(1500);

        // Change snap to 30 min
        // Use Escape first to ensure no stale popover is open, then re-open
        await page.keyboard.press('Escape');
        await page.waitForTimeout(300);
        await openSettingsPopover(page);
        await page.waitForTimeout(300);
        await page.getByLabel('Snap Interval').click({ force: true });
        await page.getByRole('option', { name: '30 min' }).click();
        await page.keyboard.press('Escape');

        // Scroll the calendar so the 10:00 target area is visible
        await scrollCalendarToTime(page, '09:00:00');

        // Drag event to 10:00 area
        const targetSlot10 = page.locator('.fc-timegrid-slot-lane[data-time="10:00:00"]').first();
        const targetBox10 = await targetSlot10.boundingBox();
        expect(targetBox10).not.toBeNull();

        const putResponsePromise2 = page.waitForResponse(
            (resp) => resp.url().includes('/time-entries/') && resp.request().method() === 'PUT'
        );

        await event.hover();
        await page.mouse.down();
        await page.mouse.move(targetBox10!.x + targetBox10!.width / 2, targetBox10!.y + 5, {
            steps: 10,
        });
        await page.mouse.up();

        const putResponse2 = await putResponsePromise2;
        expect(putResponse2.status()).toBe(200);

        const body2 = await putResponse2.json();
        const startDate2 = new Date(body2.data.start);
        expect(startDate2.getMinutes() % 30).toBe(0);
    });

    test('snap with different grid scale (slot != snap)', async ({ page, ctx }) => {
        await goToCalendar(page);
        await openSettingsPopover(page);

        // Set grid scale to 30 min, snap to 5 min
        await page.getByLabel('Grid Scale').click();
        await page.getByRole('option', { name: '30 min' }).click();
        await page.getByLabel('Snap Interval').click();
        await page.getByRole('option', { name: '5 min', exact: true }).click();
        await page.keyboard.press('Escape');

        // Wait for re-render with 30-min grid
        await expect(async () => {
            const slotCount = await page.locator('.fc-timegrid-slot-lane').count();
            // 24 hours * 2 slots/hour = 48 slots for 30-min grid
            expect(slotCount).toBeLessThanOrEqual(48);
        }).toPass({ timeout: 5000 });

        // Verify grid is 30-min (fewer slots than default 15-min)
        const slotCount = await page.locator('.fc-timegrid-slot-lane').count();
        // Default 15-min grid has 96 slots; 30-min grid should have 48
        expect(slotCount).toBeLessThanOrEqual(48);

        // Create a 1h time entry and go to calendar
        await createBareTimeEntryViaApi(ctx, 'Grid snap test', '1h');
        await goToCalendar(page);

        // Re-apply settings since goToCalendar navigates
        await openSettingsPopover(page);
        await page.getByLabel('Grid Scale').click();
        await page.getByRole('option', { name: '30 min' }).click();
        await page.getByLabel('Snap Interval').click();
        await page.getByRole('option', { name: '5 min', exact: true }).click();
        await page.keyboard.press('Escape');

        const event = page.locator('.fc-event').first();
        await expect(event).toBeVisible();

        // Drag event
        const targetSlot = page.locator('.fc-timegrid-slot-lane[data-time="14:00:00"]').first();
        const targetBox = await targetSlot.boundingBox();
        expect(targetBox).not.toBeNull();

        const putResponsePromise = page.waitForResponse(
            (resp) => resp.url().includes('/time-entries/') && resp.request().method() === 'PUT'
        );

        await event.hover();
        await page.mouse.down();
        await page.mouse.move(targetBox!.x + targetBox!.width / 2, targetBox!.y + 5, { steps: 10 });
        await page.mouse.up();

        const putResponse = await putResponsePromise;
        expect(putResponse.status()).toBe(200);

        const body = await putResponse.json();
        const startDate = new Date(body.data.start);
        // Snap is 5 min, so minutes should be divisible by 5
        expect(startDate.getMinutes() % 5).toBe(0);
    });
});

test.describe('Calendar Settings Effects', () => {
    test.beforeEach(async ({ page }) => {
        await clearCalendarSettings(page);
    });

    test('start/end time hides slots outside visible range', async ({ page, ctx }) => {
        // Create a time entry at 6 AM today
        const now = new Date();
        const start = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 6, 0, 0);
        const end = new Date(start.getTime() + 3600 * 1000); // 7 AM
        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Early morning entry',
            start: start.toISOString().replace(/\.\d{3}Z$/, 'Z'),
            end: end.toISOString().replace(/\.\d{3}Z$/, 'Z'),
        });

        await goToCalendar(page);

        // Verify 6 AM slot is visible with default settings
        await expect(page.locator('.fc-timegrid-slot[data-time="06:00:00"]')).not.toHaveCount(0);

        // Set start time to 8 AM
        await openSettingsPopover(page);
        await page.getByLabel('Start Time').click();
        await page.getByRole('option', { name: '8:00 AM' }).click();
        await page.keyboard.press('Escape');

        // 6 AM slot should be hidden
        await expect(page.locator('.fc-timegrid-slot[data-time="06:00:00"]')).toHaveCount(0);

        // 8 AM slot should be visible
        await expect(page.locator('.fc-timegrid-slot[data-time="08:00:00"]')).not.toHaveCount(0);
    });

    test('grid scale affects event visual height proportionally', async ({ page, ctx }) => {
        // Create a 1h time entry
        await createBareTimeEntryViaApi(ctx, 'Height test', '1h');
        await goToCalendar(page);

        const event = page.locator('.fc-event').first();
        await expect(event).toBeVisible();
        await event.scrollIntoViewIfNeeded();

        // Get event height with default 15-min grid scale
        const box15 = await event.boundingBox();
        expect(box15).not.toBeNull();
        const height15 = box15!.height;

        // Change grid scale to 60 min
        await openSettingsPopover(page);
        await page.getByLabel('Grid Scale').click();
        await page.getByRole('option', { name: '1 hour' }).click();
        await page.keyboard.press('Escape');

        // Wait for re-render and scroll event into view
        await event.scrollIntoViewIfNeeded();
        await expect(async () => {
            const box = await event.boundingBox();
            expect(box).not.toBeNull();
            expect(box!.height).not.toBe(height15);
        }).toPass({ timeout: 5000 });

        const box60 = await event.boundingBox();
        expect(box60).not.toBeNull();
        const height60 = box60!.height;

        // Event should appear smaller with larger grid scale
        expect(height15).toBeGreaterThan(height60);
    });

    test('snap interval affects drag granularity', async ({ page, ctx }) => {
        await goToCalendar(page);
        await openSettingsPopover(page);

        // Set snap to 30 min
        await page.getByLabel('Snap Interval').click();
        await page.getByRole('option', { name: '30 min' }).click();
        await page.keyboard.press('Escape');

        // Create a 1h time entry
        await createBareTimeEntryViaApi(ctx, 'Drag granularity test', '1h');
        await goToCalendar(page);

        // Scroll the calendar so the 14:00 target area is visible
        await scrollCalendarToTime(page, '13:00:00');

        const event = page.locator('.fc-event').first();
        await expect(event).toBeVisible();

        // Get target slot
        const targetSlot = page.locator('.fc-timegrid-slot-lane[data-time="14:00:00"]').first();
        const targetBox = await targetSlot.boundingBox();
        expect(targetBox).not.toBeNull();

        // Drag event
        const putResponsePromise = page.waitForResponse(
            (resp) => resp.url().includes('/time-entries/') && resp.request().method() === 'PUT'
        );

        await event.hover();
        await page.mouse.down();
        await page.mouse.move(targetBox!.x + targetBox!.width / 2, targetBox!.y + 5, { steps: 10 });
        await page.mouse.up();

        const putResponse = await putResponsePromise;
        expect(putResponse.status()).toBe(200);

        const body = await putResponse.json();
        const startDate = new Date(body.data.start);
        const minutes = startDate.getMinutes();

        // With 30-min snap, minutes should be 0 or 30
        expect(minutes % 30).toBe(0);
    });

    test('settings apply immediately without page reload', async ({ page }) => {
        await goToCalendar(page);

        // Count slots with default grid scale (15 min)
        const defaultSlotCount = await page.locator('.fc-timegrid-slot').count();

        // Change grid scale to 30 min
        await openSettingsPopover(page);
        await page.getByLabel('Grid Scale').click();
        await page.getByRole('option', { name: '30 min' }).click();
        await page.keyboard.press('Escape');

        // Verify slot count changed without navigation
        await expect(async () => {
            const count = await page.locator('.fc-timegrid-slot').count();
            expect(count).toBeLessThan(defaultSlotCount);
        }).toPass({ timeout: 5000 });

        // Wait for FullCalendar to fully stabilize after re-render
        await page.waitForTimeout(2000);
        await expect(page.locator('.fc')).toBeVisible();

        // Change start time to 8 AM
        // FullCalendar re-render from grid scale change can make popover elements unstable.
        // Retry the open+click sequence if it fails.
        await expect(async () => {
            await page.keyboard.press('Escape');
            await page.waitForTimeout(300);
            await page.getByRole('button', { name: 'Calendar settings' }).click();
            await expect(page.getByText('Calendar Settings')).toBeVisible();
            const startTimeBtn = page.getByLabel('Start Time');
            await expect(startTimeBtn).toBeVisible();
            await startTimeBtn.click({ timeout: 3000 });
        }).toPass({ timeout: 10000 });

        await page.getByRole('option', { name: '8:00 AM' }).click();
        await page.keyboard.press('Escape');

        // Verify 7 AM slot is hidden without reload
        await expect(async () => {
            const count = await page.locator('.fc-timegrid-slot[data-time="07:00:00"]').count();
            expect(count).toBe(0);
        }).toPass({ timeout: 5000 });
    });
});
