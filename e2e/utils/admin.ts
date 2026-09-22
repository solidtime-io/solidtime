import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../../playwright/config';

/**
 * The seeded user that is allowed to access the Filament admin panel.
 *
 * Panel access is granted by email address via the `SUPER_ADMINS` config, so
 * the admin panel tests cannot use the throwaway users created by the default
 * fixture and rely on the seeded user instead.
 */
export const SUPER_ADMIN_EMAIL = 'admin@example.com';
export const SEEDED_USER_PASSWORD = 'password';

/**
 * Log in as the seeded super admin user via the regular application login.
 */
export async function loginAsSuperAdmin(page: Page): Promise<void> {
    await page.goto(PLAYWRIGHT_BASE_URL + '/login');
    await page.getByLabel('Email').fill(SUPER_ADMIN_EMAIL);
    await page.getByLabel('Password').fill(SEEDED_USER_PASSWORD);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page.getByTestId('dashboard_view')).toBeVisible({ timeout: 15000 });
}
