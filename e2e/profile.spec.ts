import { test, expect } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL, TEST_USER_PASSWORD } from '../playwright/config';
import type { Page } from '@playwright/test';

async function goToProfilePage(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/user/profile');
}

test('test that user name can be updated', async ({ page }) => {
    await page.goto(PLAYWRIGHT_BASE_URL + '/user/profile');
    await page.getByLabel('Name', { exact: true }).fill('NEW NAME');
    await Promise.all([
        page.getByRole('button', { name: 'Save' }).first().click(),
        page.waitForResponse('**/user/profile-information'),
    ]);
    await page.reload();
    await expect(page.getByLabel('Name', { exact: true })).toHaveValue('NEW NAME');
});

test.skip('test that user email can be updated', async ({ page }) => {
    // this does not work because of email verification currently
    await page.goto(PLAYWRIGHT_BASE_URL + '/user/profile');
    const emailId = Math.round(Math.random() * 10000);
    await page.getByLabel('Email').fill(`newemail+${emailId}@test.com`);
    await page.getByRole('button', { name: 'Save' }).first().click();
    await page.reload();
    await expect(page.getByLabel('Email')).toHaveValue(`newemail+${emailId}@test.com`);
});

async function createNewApiToken(page) {
    await page.getByLabel('API Key Name').fill('NEW API KEY');
    await Promise.all([
        page.getByRole('button', { name: 'Create API Key' }).click(),
        page.waitForResponse('**/users/me/api-tokens'),
    ]);

    await expect(page.locator('body')).toContainText('API Token created successfully');
    await page.getByRole('dialog').getByText('Close').click();
    await expect(page.locator('body')).toContainText('NEW API KEY');
}

test('test that user can create an API key', async ({ page }) => {
    await page.goto(PLAYWRIGHT_BASE_URL + '/user/profile');
    await createNewApiToken(page);
});

test('test that user can delete an API key', async ({ page }) => {
    await page.goto(PLAYWRIGHT_BASE_URL + '/user/profile');
    await createNewApiToken(page);
    page.getByLabel('Delete API Token NEW API KEY').click();
    await expect(page.getByRole('dialog')).toContainText(
        'Are you sure you would like to delete this API token?'
    );
    await Promise.all([
        page.getByRole('dialog').getByRole('button', { name: 'Delete' }).click(),
        page.waitForResponse('**/users/me/api-tokens'),
    ]);
    await expect(page.locator('body')).not.toContainText('NEW API KEY');
});

test('test that user can revoke an API key', async ({ page }) => {
    await page.goto(PLAYWRIGHT_BASE_URL + '/user/profile');
    await createNewApiToken(page);
    page.getByLabel('Revoke API Token NEW API KEY').click();
    await expect(page.getByRole('dialog')).toContainText(
        'Are you sure you would like to revoke this API token?'
    );
    await Promise.all([
        page.getByRole('dialog').getByRole('button', { name: 'Revoke' }).click(),
        page.waitForResponse('**/users/me/api-tokens'),
    ]);
    await expect(page.getByRole('button', { name: 'Revoke' })).toBeHidden();
    await expect(page.locator('body')).toContainText('NEW API KEY');
    await expect(page.locator('body')).toContainText('Revoked');
});

// =============================================
// Update Password Form Tests
// =============================================

test('test that password mismatch shows error', async ({ page }) => {
    await goToProfilePage(page);

    // Fill in with mismatched passwords
    await page.getByLabel('Current Password').fill(TEST_USER_PASSWORD);
    await page.getByLabel('New Password').fill('newSecurePassword456');
    await page.getByLabel('Confirm Password').fill('differentPassword789');

    // Find the form containing the Confirm Password field and click its Save button
    const passwordForm = page.getByLabel('Confirm Password').locator('xpath=ancestor::form');
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/user/password') && response.request().method() === 'PUT'
        ),
        passwordForm.getByRole('button', { name: 'Save' }).click(),
    ]);

    // Verify error message about password confirmation
    await expect(page.getByText('confirmation does not match')).toBeVisible();
});

test('test that short password shows validation error', async ({ page }) => {
    await goToProfilePage(page);

    // Fill in with a too short password
    await page.getByLabel('Current Password').fill(TEST_USER_PASSWORD);
    await page.getByLabel('New Password').fill('short');
    await page.getByLabel('Confirm Password').fill('short');

    // Find the form containing the Confirm Password field and click its Save button
    const passwordForm = page.getByLabel('Confirm Password').locator('xpath=ancestor::form');
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/user/password') && response.request().method() === 'PUT'
        ),
        passwordForm.getByRole('button', { name: 'Save' }).click(),
    ]);

    // Verify error message about password length
    await expect(page.getByText('must be at least')).toBeVisible();
});

test('test that incorrect current password shows validation error', async ({ page }) => {
    await goToProfilePage(page);

    // Fill in with wrong current password
    await page.getByLabel('Current Password').fill('wrongCurrentPassword123');
    await page.getByLabel('New Password').fill('newSecurePassword456');
    await page.getByLabel('Confirm Password').fill('newSecurePassword456');

    // Find the form containing the Confirm Password field and click its Save button
    const passwordForm = page.getByLabel('Confirm Password').locator('xpath=ancestor::form');
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/user/password') && response.request().method() === 'PUT'
        ),
        passwordForm.getByRole('button', { name: 'Save' }).click(),
    ]);

    // Verify error message about incorrect password
    await expect(page.getByText('does not match')).toBeVisible();
});

test('test that password can be updated successfully', async ({ page }) => {
    await goToProfilePage(page);
    const newPassword = 'newSecurePassword456';

    // Change password to new password
    await page.getByLabel('Current Password').fill(TEST_USER_PASSWORD);
    await page.getByLabel('New Password').fill(newPassword);
    await page.getByLabel('Confirm Password').fill(newPassword);

    const passwordForm = page.getByLabel('Confirm Password').locator('xpath=ancestor::form');
    const responsePromise = page.waitForResponse(
        (response) =>
            response.url().includes('/user/password') && response.request().method() === 'PUT'
    );
    await passwordForm.getByRole('button', { name: 'Save' }).click();
    const response = await responsePromise;

    // Verify successful response (303 is Inertia redirect on success, means password was updated)
    expect(response.status()).toBe(303);

    // Verify no error messages are displayed
    await expect(page.getByText('does not match')).not.toBeVisible();
    await expect(page.getByText('must be at least')).not.toBeVisible();
});

// =============================================
// Theme Selection Tests
// =============================================

test('test that theme can be changed to dark and light', async ({ page }) => {
    await goToProfilePage(page);

    // The theme select is a Reka UI combobox (button), not a native <select>
    const themeSelect = page.locator('button[role="combobox"]');

    // Change theme to dark
    await themeSelect.click();
    await page.getByRole('option', { name: 'Dark' }).click();

    // Verify the html element has 'dark' class
    await expect(page.locator('html')).toHaveClass(/dark/);

    // Change theme to light
    await themeSelect.click();
    await page.getByRole('option', { name: 'Light' }).click();

    // Verify the html element has 'light' class and no 'dark' class
    await expect(page.locator('html')).toHaveClass(/light/);
    await expect(page.locator('html')).not.toHaveClass(/dark/);

    // Verify localStorage persists the setting
    const storedTheme = await page.evaluate(() => localStorage.getItem('theme'));
    expect(storedTheme).toContain('light');

    // Reload and verify the theme persists
    await page.reload();
    await expect(page.locator('html')).toHaveClass(/light/);

    // Reset to system
    await page.locator('button[role="combobox"]').click();
    await page.getByRole('option', { name: 'System' }).click();
    await expect(page.getByText('System default:')).toBeVisible();
});

// =============================================
// Two Factor Authentication Tests
// =============================================

test('test that password confirmation modal can be cancelled without sending API request', async ({
    page,
}) => {
    await goToProfilePage(page);

    // Find the Enable button in the 2FA section
    const enableButton = page
        .getByText('You have not enabled two factor authentication.')
        .locator('..')
        .getByRole('button', { name: 'Enable' });
    await enableButton.click();

    // Verify password confirmation modal appears
    await expect(page.getByRole('dialog')).toBeVisible();

    // Set up listener to verify no POST request is sent to confirm-password
    let confirmPasswordRequestSent = false;
    page.on('request', (request) => {
        if (request.url().includes('/user/confirm-password') && request.method() === 'POST') {
            confirmPasswordRequestSent = true;
        }
    });

    // Click Cancel
    await page.getByRole('dialog').getByRole('button', { name: 'Cancel' }).click();

    // Verify modal is closed
    await expect(page.getByRole('dialog')).not.toBeVisible();

    // Verify no confirm-password request was sent
    expect(confirmPasswordRequestSent).toBe(false);
});

test('test that password confirmation modal shows error for incorrect password', async ({
    page,
}) => {
    await goToProfilePage(page);

    // Find the Enable button in the 2FA section
    const enableButton = page
        .getByText('You have not enabled two factor authentication.')
        .locator('..')
        .getByRole('button', { name: 'Enable' });
    await enableButton.click();

    // Verify password confirmation modal appears
    await expect(page.getByRole('dialog')).toBeVisible();

    // Enter incorrect password and confirm
    await page.getByPlaceholder('Password').fill('wrongpassword123');
    await page.getByRole('dialog').getByRole('button', { name: 'Confirm' }).click();

    // Should show error message (wait longer for API response)
    await expect(page.getByRole('dialog').getByText('incorrect')).toBeVisible({ timeout: 10000 });
});

test('test that 2FA can be enabled with correct password', async ({ page }) => {
    await goToProfilePage(page);

    // Verify 2FA is not enabled
    await expect(page.getByText('You have not enabled two factor authentication.')).toBeVisible();

    // Find the Enable button in the 2FA section
    const enableButton = page
        .getByText('You have not enabled two factor authentication.')
        .locator('..')
        .getByRole('button', { name: 'Enable' });
    await enableButton.click();

    // Verify password confirmation modal appears
    await expect(page.getByRole('dialog')).toBeVisible();

    // Enter correct password and confirm
    await page.getByPlaceholder('Password').fill(TEST_USER_PASSWORD);
    await Promise.all([
        page.getByRole('dialog').getByRole('button', { name: 'Confirm' }).click(),
        page.waitForResponse(
            (response) =>
                response.url().includes('/user/two-factor-authentication') &&
                response.request().method() === 'POST'
        ),
    ]);

    // Verify QR code is shown
    await expect(page.getByRole('heading', { name: 'Finish enabling two factor' })).toBeVisible();
    await expect(page.getByText('Setup Key:')).toBeVisible();
    await expect(page.getByLabel('Code')).toBeVisible();
});

// =============================================
// Logout Other Browser Sessions Tests
// =============================================

test('test that logout other browser sessions works with correct password', async ({ page }) => {
    await goToProfilePage(page);

    await page.getByRole('button', { name: 'Log Out Other Browser Sessions' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();

    await page.getByPlaceholder('Password').fill(TEST_USER_PASSWORD);
    await Promise.all([
        page
            .getByRole('dialog')
            .getByRole('button', { name: 'Log Out Other Browser Sessions' })
            .click(),
        page.waitForResponse(
            (response) =>
                response.url().includes('/user/other-browser-sessions') &&
                response.request().method() === 'DELETE'
        ),
    ]);
});
