import { test, expect } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL, TEST_USER_PASSWORD } from '../playwright/config';
import { generateTotpCode, generateInvalidTotpCode } from './utils/totp';
import type { Page } from '@playwright/test';

async function goToProfilePage(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/user/profile');
}

/**
 * ConfirmsPassword only opens the dialog when the password has not been
 * confirmed recently, so fill it only when it actually shows up.
 */
async function confirmPasswordIfPrompted(page: Page) {
    const dialog = page.getByRole('dialog');
    const appeared = await dialog
        .waitFor({ state: 'visible', timeout: 2500 })
        .then(() => true)
        .catch(() => false);
    if (appeared) {
        await dialog.getByPlaceholder('Password').fill(TEST_USER_PASSWORD);
        await dialog.getByRole('button', { name: 'Confirm' }).click();
        await expect(dialog).not.toBeVisible();
    }
}

/**
 * Enables 2FA from the profile page and returns the TOTP secret (setup key)
 * and the recovery codes fetched right after enabling.
 */
async function enableTwoFactor(page: Page): Promise<{ secret: string; recoveryCodes: string[] }> {
    await goToProfilePage(page);
    await page
        .getByText('You have not enabled two factor authentication.')
        .locator('..')
        .getByRole('button', { name: 'Enable' })
        .click();

    const dialog = page.getByRole('dialog');
    await expect(dialog).toBeVisible();

    const recoveryCodesResponse = page.waitForResponse(
        (response) =>
            response.url().includes('/user/two-factor-recovery-codes') &&
            response.request().method() === 'GET'
    );
    await dialog.getByPlaceholder('Password').fill(TEST_USER_PASSWORD);
    await dialog.getByRole('button', { name: 'Confirm' }).click();

    await expect(page.getByRole('heading', { name: 'Finish enabling two factor' })).toBeVisible();
    const recoveryCodes: string[] = await (await recoveryCodesResponse).json();

    const setupKeyText = await page.getByText('Setup Key:').textContent();
    const secret = setupKeyText!.replace('Setup Key:', '').trim();
    expect(secret.length).toBeGreaterThan(0);

    return { secret, recoveryCodes };
}

/**
 * Confirms a freshly enabled 2FA setup with a valid TOTP code.
 */
async function confirmTwoFactor(page: Page, secret: string) {
    await page.getByLabel('Code').fill(generateTotpCode(secret));
    await page.getByRole('button', { name: 'Confirm', exact: true }).click();
    await confirmPasswordIfPrompted(page);
    await expect(page.getByText('You have enabled two factor authentication.')).toBeVisible();
}

async function logout(page: Page) {
    await page.getByTestId('current_user_button').click();
    await page.getByText('Log Out', { exact: true }).click();
    await page.waitForURL(PLAYWRIGHT_BASE_URL + '/login');
}

/**
 * Reads the email of the current user from the profile form, waiting until
 * the user query has populated it.
 */
async function getProfileEmail(page: Page): Promise<string> {
    await goToProfilePage(page);
    const emailInput = page.getByLabel('Email', { exact: true });
    await expect(emailInput).toHaveValue(/@/);
    return await emailInput.inputValue();
}

async function loginUntilTwoFactorChallenge(page: Page, email: string) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/login');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Password').fill(TEST_USER_PASSWORD);
    await page.getByRole('button', { name: 'Log in' }).click();
    await page.waitForURL(PLAYWRIGHT_BASE_URL + '/two-factor-challenge');
}

test('test that 2FA can be confirmed with a TOTP code and shows recovery codes', async ({
    page,
}) => {
    const { secret, recoveryCodes } = await enableTwoFactor(page);
    await confirmTwoFactor(page, secret);

    await expect(page.getByText('Store these recovery codes')).toBeVisible();
    expect(recoveryCodes.length).toBeGreaterThan(0);
    for (const code of recoveryCodes) {
        await expect(page.getByText(code)).toBeVisible();
    }

    // The confirmed state survives a reload
    await page.reload();
    await expect(page.getByText('You have enabled two factor authentication.')).toBeVisible();
});

test('test that 2FA confirmation fails with an invalid TOTP code', async ({ page }) => {
    const { secret } = await enableTwoFactor(page);

    await page.getByLabel('Code').fill(generateInvalidTotpCode(secret));
    await page.getByRole('button', { name: 'Confirm', exact: true }).click();
    await confirmPasswordIfPrompted(page);

    await expect(page.getByRole('alert')).toContainText(
        'The provided two factor authentication code was invalid.'
    );
    await expect(page.getByRole('heading', { name: 'Finish enabling two factor' })).toBeVisible();
});

test('test that recovery codes can be regenerated', async ({ page }) => {
    const { secret, recoveryCodes } = await enableTwoFactor(page);
    await confirmTwoFactor(page, secret);

    const newCodesResponse = page.waitForResponse(
        (response) =>
            response.url().includes('/user/two-factor-recovery-codes') &&
            response.request().method() === 'GET'
    );
    await page.getByRole('button', { name: 'Regenerate Recovery Codes' }).click();
    await confirmPasswordIfPrompted(page);
    const newCodes: string[] = await (await newCodesResponse).json();

    expect(newCodes).not.toEqual(recoveryCodes);
    await expect(page.getByText(newCodes[0])).toBeVisible();
    await expect(page.getByText(recoveryCodes[0])).not.toBeVisible();
});

test('test that 2FA can be disabled', async ({ page }) => {
    const { secret } = await enableTwoFactor(page);
    await confirmTwoFactor(page, secret);

    await page.getByRole('button', { name: 'Disable' }).click();
    await confirmPasswordIfPrompted(page);
    await expect(page.getByText('You have not enabled two factor authentication.')).toBeVisible();

    // The disabled state survives a reload
    await page.reload();
    await expect(page.getByText('You have not enabled two factor authentication.')).toBeVisible();
});

test('test that login challenges for a TOTP code and rejects an invalid code', async ({ page }) => {
    const email = await getProfileEmail(page);

    const { secret } = await enableTwoFactor(page);
    await confirmTwoFactor(page, secret);
    await logout(page);

    await loginUntilTwoFactorChallenge(page, email);

    await page.getByLabel('Code').fill(generateInvalidTotpCode(secret));
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page.getByRole('alert')).toContainText(
        'The provided two factor authentication code was invalid.'
    );

    // Fortify rejects replayed codes, and the current window's code was
    // already consumed when confirming the setup — use the next window's
    // code, which the +/- 1 step verification window also accepts.
    await page.getByLabel('Code').fill(generateTotpCode(secret, Date.now() + 30_000));
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page.getByTestId('dashboard_view')).toBeVisible();
});

test('test that login works with a recovery code', async ({ page }) => {
    const email = await getProfileEmail(page);

    const { secret, recoveryCodes } = await enableTwoFactor(page);
    await confirmTwoFactor(page, secret);
    await logout(page);

    await loginUntilTwoFactorChallenge(page, email);

    await page.getByRole('button', { name: 'Use a recovery code' }).click();
    await page.getByLabel('Recovery Code').fill(recoveryCodes[0]);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page.getByTestId('dashboard_view')).toBeVisible();
});
