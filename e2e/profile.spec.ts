import { test, expect } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL, TEST_USER_PASSWORD } from '../playwright/config';
import {
    countEmailsWithSubject,
    getEmailChangeVerificationUrl,
    waitForEmailCount,
} from './utils/mailpit';
import { getCurrentUserViaApi } from './utils/api';
import { registerUser } from './utils/members';
import type { Page } from '@playwright/test';
import path from 'path';

async function goToProfilePage(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/user/profile');
}

function profileInformationForm(page: Page) {
    return page
        .getByRole('heading', { name: 'Profile Information', exact: true })
        .locator('xpath=ancestor::*[descendant::form][1]');
}

async function saveProfileForm(page: Page): Promise<void> {
    const form = profileInformationForm(page);
    await form.getByRole('button', { name: 'Save' }).click();
    await expect(form.getByText('Saved.', { exact: true })).toBeVisible();
}

test('user name can be updated', async ({ page }) => {
    await goToProfilePage(page);
    await page.getByLabel('Name', { exact: true }).fill('NEW NAME');
    await saveProfileForm(page);
    await page.reload();
    await expect(page.getByLabel('Name', { exact: true })).toHaveValue('NEW NAME');
});

test('timezone change persists across reload', async ({ page }) => {
    await goToProfilePage(page);
    await page.getByLabel('Timezone').selectOption('America/New_York');
    await saveProfileForm(page);
    await page.reload();
    await expect(page.getByLabel('Timezone')).toHaveValue('America/New_York');
});

test('week-start change persists across reload', async ({ page }) => {
    await goToProfilePage(page);
    await page.getByLabel('Start of the week').selectOption('sunday');
    await saveProfileForm(page);
    await page.reload();
    await expect(page.getByLabel('Start of the week')).toHaveValue('sunday');
});

test('profile photo can be uploaded, persists across reload, and can be removed', async ({
    page,
}) => {
    await goToProfilePage(page);
    const form = profileInformationForm(page);
    const profilePhoto = form.getByRole('img', { name: 'John Doe' });

    await expect(profilePhoto).toBeVisible();
    await expect(profilePhoto).toHaveAttribute('src', /ui-avatars\.com/);
    await expect(form.getByRole('button', { name: 'Remove Photo' })).toBeHidden();

    await form.locator('#photo').setInputFiles(path.resolve('resources/testfiles/test.png'));
    await saveProfileForm(page);
    await expect(profilePhoto).toHaveAttribute('src', /profile-photos/);
    await expect(form.getByRole('button', { name: 'Remove Photo' })).toBeVisible();

    await page.reload();
    const reloadedForm = profileInformationForm(page);
    const reloadedProfilePhoto = reloadedForm.getByRole('img', { name: 'John Doe' });
    await expect(reloadedProfilePhoto).toHaveAttribute('src', /profile-photos/);

    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/api/v1/users/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        reloadedForm.getByRole('button', { name: 'Remove Photo' }).click(),
    ]);
    await expect(reloadedProfilePhoto).toHaveAttribute('src', /ui-avatars\.com/);
    await expect(reloadedForm.getByRole('button', { name: 'Remove Photo' })).toBeHidden();

    await page.reload();
    const finalForm = profileInformationForm(page);
    await expect(finalForm.getByRole('img', { name: 'John Doe' })).toHaveAttribute(
        'src',
        /ui-avatars\.com/
    );
    await expect(finalForm.getByRole('button', { name: 'Remove Photo' })).toBeHidden();
});

test('field-level validation errors render inline when the server returns 422', async ({
    page,
}) => {
    await goToProfilePage(page);
    const form = profileInformationForm(page);
    await form.getByLabel('Name').fill('a'.repeat(256));
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/api/v1/users/') &&
                response.request().method() === 'PUT' &&
                response.status() === 422
        ),
        form.getByRole('button', { name: 'Save' }).click(),
    ]);
    await expect(form.getByRole('alert').filter({ hasText: /255 characters/i })).toBeVisible();
});

test('submitting a new email keeps the current email displayed after reload', async ({
    page,
    ctx,
}) => {
    const { email: oldEmail } = await getCurrentUserViaApi(ctx);
    const newEmail = `newemail+${Date.now()}@test.com`;

    await goToProfilePage(page);
    await page.getByLabel('Email').fill(newEmail);
    await saveProfileForm(page);
    await page.reload();

    await expect(page.getByLabel('Email')).toHaveValue(oldEmail);
});

test('submitting a new email sends a verification email to the new address', async ({
    page,
    request,
}) => {
    await goToProfilePage(page);
    const newEmail = `newemail+${Date.now()}@test.com`;

    await page.getByLabel('Email').fill(newEmail);
    await saveProfileForm(page);

    expect(await waitForEmailCount(request, newEmail, 'Verify Email Address', 1)).toBeGreaterThan(
        0
    );
});

test('mixed-case email is lower-cased before the verification mail is sent', async ({
    page,
    request,
}) => {
    await goToProfilePage(page);
    const stamp = Date.now();
    const mixedCase = `MixedCase+${stamp}@Example.COM`;
    const lowerCased = `mixedcase+${stamp}@example.com`;

    await page.getByLabel('Email').fill(mixedCase);
    await saveProfileForm(page);

    const verifyUrl = await getEmailChangeVerificationUrl(request, lowerCased);
    expect(new URL(verifyUrl).searchParams.get('email')).toBe(lowerCased);
});

test('re-submitting the current email does not send a verification email', async ({
    page,
    ctx,
    request,
}) => {
    const { email: currentEmail } = await getCurrentUserViaApi(ctx);
    const beforeCount = await countEmailsWithSubject(request, currentEmail, 'Verify Email Address');

    await goToProfilePage(page);
    await page.getByLabel('Email').fill(currentEmail);
    await saveProfileForm(page);

    await new Promise((r) => setTimeout(r, 1000));
    const afterCount = await countEmailsWithSubject(request, currentEmail, 'Verify Email Address');
    expect(afterCount).toBe(beforeCount);
});

test('after submitting a new email the pending-email banner is shown with a resend button', async ({
    page,
}) => {
    await goToProfilePage(page);
    const newEmail = `pending+${Date.now()}@test.com`;
    await page.getByLabel('Email').fill(newEmail);
    await saveProfileForm(page);

    await expect(page.getByText(`A verification link was sent to`)).toBeVisible();
    await expect(page.getByText(newEmail)).toBeVisible();
    await expect(page.getByRole('button', { name: 'Resend verification email' })).toBeVisible();
});

test('clicking resend sends a second verification email and shows confirmation', async ({
    page,
    request,
}) => {
    await goToProfilePage(page);
    const newEmail = `resend+${Date.now()}@test.com`;
    await page.getByLabel('Email').fill(newEmail);
    await saveProfileForm(page);

    const beforeCount = await waitForEmailCount(request, newEmail, 'Verify Email Address', 1);
    await page.getByRole('button', { name: 'Resend verification email' }).click();

    await expect(page.getByText('Verification email sent.')).toBeVisible();
    const afterCount = await waitForEmailCount(
        request,
        newEmail,
        'Verify Email Address',
        beforeCount + 1
    );
    expect(afterCount).toBeGreaterThan(beforeCount);
});

test('cancelling a pending email change clears it and hides the banner', async ({ page, ctx }) => {
    const { email: currentEmail } = await getCurrentUserViaApi(ctx);
    const newEmail = `cancel+${Date.now()}@test.com`;

    await goToProfilePage(page);
    await page.getByLabel('Email').fill(newEmail);
    await saveProfileForm(page);

    // The pending-email banner is shown with the cancel control.
    await expect(page.getByText('A verification link was sent to')).toBeVisible();
    await expect(page.getByText(newEmail)).toBeVisible();
    const cancelButton = page.getByRole('button', { name: 'Cancel email change' });
    await expect(cancelButton).toBeVisible();

    // Cancelling clears the pending email server-side (204).
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/reset-pending-email') &&
                response.request().method() === 'POST' &&
                response.status() === 204
        ),
        cancelButton.click(),
    ]);

    // The banner disappears and the email field still shows the current address.
    await expect(page.getByText('A verification link was sent to')).toBeHidden();
    await expect(page.getByLabel('Email')).toHaveValue(currentEmail);

    // The cancellation is persistent — still gone after a reload.
    await page.reload();
    await expect(page.getByText('A verification link was sent to')).toBeHidden();
    await expect(page.getByLabel('Email')).toHaveValue(currentEmail);
});

test('re-submitting the same pending email does not send another verification email', async ({
    page,
    request,
}) => {
    await goToProfilePage(page);
    const newEmail = `dup+${Date.now()}@test.com`;
    await page.getByLabel('Email').fill(newEmail);
    await saveProfileForm(page);
    const beforeCount = await waitForEmailCount(request, newEmail, 'Verify Email Address', 1);

    await page.getByLabel('Email').fill(newEmail);
    await saveProfileForm(page);

    await new Promise((r) => setTimeout(r, 1000));
    const afterCount = await countEmailsWithSubject(request, newEmail, 'Verify Email Address');
    expect(afterCount).toBe(beforeCount);
});

test('clicking the verification link swaps the email and shows a success banner', async ({
    page,
}) => {
    await goToProfilePage(page);
    const newEmail = `verify+${Date.now()}@test.com`;
    await page.getByLabel('Email').fill(newEmail);
    await saveProfileForm(page);
    const verifyUrl = await getEmailChangeVerificationUrl(page.request, newEmail);

    await page.goto(verifyUrl);
    await page.waitForURL(/\/dashboard/);

    const banner = page.getByTestId('banner');
    await expect(banner).toBeVisible();
    await expect(banner).toContainText('Your email address has been updated successfully.');

    await goToProfilePage(page);
    await expect(page.getByLabel('Email')).toHaveValue(newEmail);
});

test('visiting another user’s verification link is forbidden', async ({ page, browser }) => {
    await goToProfilePage(page);
    const newEmail = `victim+${Date.now()}@test.com`;
    await page.getByLabel('Email').fill(newEmail);
    await saveProfileForm(page);
    const verifyUrl = await getEmailChangeVerificationUrl(page.request, newEmail);

    const other = await registerUser(browser, 'Other User', `other+${Date.now()}@test.com`);
    try {
        const response = await other.page.goto(verifyUrl);
        expect(response?.status()).toBe(403);
    } finally {
        await other.close();
    }
});

test('a stale verification link from a previous submission is rejected', async ({ page }) => {
    await goToProfilePage(page);
    const stamp = Date.now();
    const olderEmail = `older+${stamp}@test.com`;
    const newerEmail = `newer+${stamp}@test.com`;

    await page.getByLabel('Email').fill(olderEmail);
    await saveProfileForm(page);
    const staleUrl = await getEmailChangeVerificationUrl(page.request, olderEmail);

    await page.getByLabel('Email').fill(newerEmail);
    await saveProfileForm(page);

    const response = await page.goto(staleUrl);
    expect(response?.status()).toBe(403);
});

test('visiting the verification link while logged out redirects to login', async ({
    page,
    browser,
}) => {
    await goToProfilePage(page);
    const newEmail = `loggedout+${Date.now()}@test.com`;
    await page.getByLabel('Email').fill(newEmail);
    await saveProfileForm(page);
    const verifyUrl = await getEmailChangeVerificationUrl(page.request, newEmail);

    const anonContext = await browser.newContext();
    try {
        const anonPage = await anonContext.newPage();
        await anonPage.goto(verifyUrl);
        await anonPage.waitForURL(/\/login/);
    } finally {
        await anonContext.close();
    }
});

test('delete account shows an error when the password is wrong', async ({ page }) => {
    await goToProfilePage(page);
    await page.getByRole('button', { name: 'Delete Account' }).click();
    const dialog = page.getByRole('dialog');
    await dialog.getByPlaceholder('Password').fill('not-the-real-password');
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/api/v1/users/') &&
                response.request().method() === 'DELETE' &&
                response.status() === 422
        ),
        dialog.getByRole('button', { name: 'Delete Account' }).click(),
    ]);
    await expect(dialog.getByRole('alert')).toBeVisible();
    await expect(dialog).toBeVisible();
});

test('delete account succeeds with the correct password and logs the user out', async ({
    page,
}) => {
    await goToProfilePage(page);
    await page.getByRole('button', { name: 'Delete Account' }).click();
    const dialog = page.getByRole('dialog');
    await dialog.getByPlaceholder('Password').fill(TEST_USER_PASSWORD);
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/api/v1/users/') &&
                response.request().method() === 'DELETE' &&
                response.status() === 204
        ),
        dialog.getByRole('button', { name: 'Delete Account' }).click(),
    ]);
    await page.waitForURL(/\/login/);
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

test('test that creating an API key with empty name shows validation error', async ({ page }) => {
    await page.goto(PLAYWRIGHT_BASE_URL + '/user/profile');

    // Wait for the API Key Name input to be visible before interacting
    const nameInput = page.getByLabel('API Key Name');
    await expect(nameInput).toBeVisible();

    // Ensure the API Key Name input is empty
    await nameInput.fill('');

    // Click the create button and wait for the 422 response
    const [response] = await Promise.all([
        page.waitForResponse('**/users/me/api-tokens'),
        page.getByRole('button', { name: 'Create API Key' }).click(),
    ]);

    expect(response.status()).toBe(422);

    // Verify that an error notification is shown with validation message about the name field
    await expect(page.getByText('name field is required')).toBeVisible({ timeout: 5000 });
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
// Group similar time entries
// =============================================

test('test that group similar time entries setting can be toggled', async ({ page }) => {
    await goToProfilePage(page);

    // Get the checkbox
    const checkbox = page.getByLabel('Group similar time entries');

    // Get initial value and verify it is checked (default is true)
    const initialValue = await checkbox.isChecked();
    await expect(checkbox).toBeChecked();

    // Toggle the checkbox
    await checkbox.click();

    // Reload
    await page.reload();

    // Verify the value is toggled
    const afterValue = await page.getByLabel('Group similar time entries').isChecked();
    expect(afterValue).toBe(!initialValue);

    // Verify localStorage persists the setting
    const storedValue = await page.evaluate(() =>
        localStorage.getItem('group-similar-time-entries')
    );
    expect(storedValue).toBe(String(!initialValue));
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
