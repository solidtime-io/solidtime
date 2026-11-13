import { adminTest as test, expect } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { registerUser } from './utils/members';

test.describe('Impersonation', () => {
    test.beforeEach(async ({ page }) => {
        // The seeded super admin's timezone differs from the browser's, which opens a modal over the app.
        await page.addInitScript(() => {
            window.sessionStorage.setItem('hide-timezone-mismatch-modal', 'true');
        });
    });

    test('test that a super admin can exit an impersonation from the user menu', async ({
        page,
        browser,
    }) => {
        const userName = 'Impersonated User';
        const userEmail = `impersonated+${Date.now()}_${Math.floor(Math.random() * 10000)}@doe.com`;
        // Contexts inherit the super admin's storage state, so the new user registers in an empty one.
        const user = await registerUser(browser, userName, userEmail, {
            storageState: { cookies: [], origins: [] },
        });
        await user.close();

        try {
            await page.goto(
                PLAYWRIGHT_BASE_URL + '/admin/users?search=' + encodeURIComponent(userEmail)
            );
            const userRow = page.getByRole('row').filter({ hasText: userEmail });
            await userRow.getByRole('button', { name: 'Impersonate' }).click();

            await expect(page.getByTestId('dashboard_view')).toBeVisible();

            await page.getByRole('button', { name: userName }).click();
            await page.getByRole('menuitem', { name: 'Exit impersonation' }).click();

            await expect(page).toHaveURL(new RegExp('/admin/users'));
            await expect(page.getByRole('heading', { name: 'Users', level: 1 })).toBeVisible();
        } finally {
            // The admin session is shared by all tests of this worker, so it must never stay impersonating.
            await page.goto(PLAYWRIGHT_BASE_URL + '/filament-impersonate/leave');
        }
    });

    test('test that the exit impersonation menu item is hidden without an impersonation', async ({
        page,
    }) => {
        await page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
        await expect(page.getByTestId('dashboard_view')).toBeVisible();

        // The seeded super admin's name is unknown here, so the trigger has no stable accessible name.
        await page.getByTestId('current_user_button').click();

        await expect(page.getByRole('menuitem', { name: 'Log Out' })).toBeVisible();
        await expect(page.getByRole('menuitem', { name: 'Exit impersonation' })).toHaveCount(0);
    });
});
