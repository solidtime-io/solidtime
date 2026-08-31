import { test, expect } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import type { Page, TestContext } from '../playwright/fixtures';
import { getCurrentUserViaApi, updateUserProfileViaApi } from './utils/api';

const BROWSER_TIMEZONE = 'Europe/Vienna';
const MISMATCHED_TIMEZONE = 'America/New_York';

test.use({ timezoneId: BROWSER_TIMEZONE });

function mismatchModal(page: Page) {
    return page.getByRole('dialog').filter({ hasText: 'Timezone mismatch detected' });
}

async function openPageWithTimezoneMismatch(page: Page, ctx: TestContext) {
    await updateUserProfileViaApi(ctx, { timezone: MISMATCHED_TIMEZONE });
    await page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
    await expect(mismatchModal(page)).toBeVisible();
}

test('timezone mismatch modal saves the device timezone through the users API', async ({
    page,
    ctx,
}) => {
    await openPageWithTimezoneMismatch(page, ctx);

    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/api/v1/users/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        mismatchModal(page).getByRole('button', { name: 'Update timezone' }).click(),
    ]);
    await expect(mismatchModal(page)).toBeHidden();

    const user = await getCurrentUserViaApi(ctx);
    expect(user.timezone).toBe(BROWSER_TIMEZONE);

    // After the automatic reload the timezones match again, so the modal stays gone.
    await page.waitForLoadState('load');
    await page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
    await expect(mismatchModal(page)).toBeHidden();
});

test('timezone mismatch modal does not open for a different timezone with the same time', async ({
    page,
    ctx,
}) => {
    // Berlin and Vienna share the same offset and DST rules, so the times match.
    await updateUserProfileViaApi(ctx, { timezone: 'Europe/Berlin' });
    await page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');

    // Wait until the dashboard is rendered so the modal had its chance to mount.
    await expect(page.getByTestId('dashboard_view')).toBeVisible();
    await expect(mismatchModal(page)).toBeHidden();
});

test('timezone mismatch modal stays open when the update fails', async ({ page, ctx }) => {
    await openPageWithTimezoneMismatch(page, ctx);

    await page.route('**/api/v1/users/*', (route) => {
        if (route.request().method() === 'PUT') {
            return route.fulfill({
                status: 500,
                contentType: 'application/json',
                body: JSON.stringify({ message: 'Server error' }),
            });
        }
        return route.fallback();
    });

    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/api/v1/users/') &&
                response.request().method() === 'PUT' &&
                response.status() === 500
        ),
        mismatchModal(page).getByRole('button', { name: 'Update timezone' }).click(),
    ]);

    await expect(mismatchModal(page)).toBeVisible();
    await expect(page.getByText('Failed to update profile')).toBeVisible();

    const user = await getCurrentUserViaApi(ctx);
    expect(user.timezone).toBe(MISMATCHED_TIMEZONE);
});
