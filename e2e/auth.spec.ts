import { expect, test } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { getPasswordResetUrl } from './utils/mailpit';

async function registerNewUser(page, email, password) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/register');
    await page.getByLabel('Name').fill('John Doe');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByLabel('Confirm Password').fill(password);
    await page.getByLabel('I agree to the Terms of').click();
    await page.getByRole('button', { name: 'Register' }).click();
    await expect(page.getByTestId('dashboard_view')).toBeVisible();
}

test('can register, logout and log back in', async ({ page }) => {
    await page.goto(PLAYWRIGHT_BASE_URL);
    const email = `john+${Math.round(Math.random() * 10000)}@doe.com`;
    const password = 'suchagreatpassword123';
    await registerNewUser(page, email, password);
    await expect(page.getByTestId('dashboard_view')).toBeVisible();
    await page.getByTestId('current_user_button').click();
    await page.getByText('Log Out').click();
    await page.waitForURL(PLAYWRIGHT_BASE_URL + '/login');
    await page.goto(PLAYWRIGHT_BASE_URL + '/login');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page.getByTestId('dashboard_view')).toBeVisible();
});

test('can register and delete account', async ({ page }) => {
    await page.goto(PLAYWRIGHT_BASE_URL);
    const email = `john+${Math.round(Math.random() * 10000)}@doe.com`;
    const password = 'suchagreatpassword123';
    await registerNewUser(page, email, password);
    await page.goto(PLAYWRIGHT_BASE_URL + '/user/profile');
    await page.getByRole('button', { name: 'Delete Account' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();
    await page.getByPlaceholder('Password').fill(password);
    await page.getByRole('dialog').getByRole('button', { name: 'Delete Account' }).click();
    await page.waitForURL(PLAYWRIGHT_BASE_URL + '/login');
    await page.goto(PLAYWRIGHT_BASE_URL + '/login');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page.getByRole('alert')).toContainText(
        'These credentials do not match our records.'
    );
});

test('shows error for invalid email on forgot password', async ({ page }) => {
    await page.goto(PLAYWRIGHT_BASE_URL + '/forgot-password');

    // Request password reset with non-existent email
    await page.getByLabel('Email').fill('nonexistent@example.com');
    await page.getByRole('button', { name: 'Email Password Reset Link' }).click();

    // Should show error message
    await expect(page.getByText("We can't find a user with that email address.")).toBeVisible();
});

test('shows browser validation for invalid email format on forgot password', async ({ page }) => {
    await page.goto(PLAYWRIGHT_BASE_URL + '/forgot-password');

    // Request password reset with invalid email format
    const emailInput = page.getByLabel('Email');
    await emailInput.fill('notanemail');

    // Check for browser validation - the input should be invalid
    const isInvalid = await emailInput.evaluate((el: HTMLInputElement) => !el.validity.valid);
    expect(isInvalid).toBe(true);
});

test('shows browser validation for empty email on forgot password', async ({ page }) => {
    await page.goto(PLAYWRIGHT_BASE_URL + '/forgot-password');

    // The email input is required, so it should be invalid when empty
    const emailInput = page.getByLabel('Email');

    // Check for browser validation - the input should be invalid because it's required and empty
    const isInvalid = await emailInput.evaluate((el: HTMLInputElement) => el.validity.valueMissing);
    expect(isInvalid).toBe(true);
});

test('can reset password via email link', async ({ page, request }) => {
    // First register a new user
    const email = `john+${Math.round(Math.random() * 10000)}@doe.com`;
    const originalPassword = 'suchagreatpassword123';
    const newPassword = 'mynewsecurepassword456';
    await registerNewUser(page, email, originalPassword);

    // Log out
    await page.getByTestId('current_user_button').click();
    await page.getByText('Log Out').click();
    await page.waitForURL(PLAYWRIGHT_BASE_URL + '/login');

    // Request password reset
    await page.goto(PLAYWRIGHT_BASE_URL + '/forgot-password');
    await page.getByLabel('Email').fill(email);
    await page.getByRole('button', { name: 'Email Password Reset Link' }).click();
    await expect(page.getByText('We have emailed your password reset link.')).toBeVisible();

    // Get password reset URL from email
    const resetUrl = await getPasswordResetUrl(request, email);

    // Navigate to reset page
    await page.goto(resetUrl);

    // Fill in new password
    await page.getByLabel('Password', { exact: true }).fill(newPassword);
    await page.getByLabel('Confirm Password').fill(newPassword);
    await page.getByRole('button', { name: 'Reset Password' }).click();

    // Should redirect to login page after successful reset
    await page.waitForURL(PLAYWRIGHT_BASE_URL + '/login');

    // Try logging in with new password
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Password').fill(newPassword);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page.getByTestId('dashboard_view')).toBeVisible();
});

test('shows validation error for password mismatch on reset', async ({ page, request }) => {
    // First register a new user
    const email = `john+${Math.round(Math.random() * 10000)}@doe.com`;
    const originalPassword = 'suchagreatpassword123';
    await registerNewUser(page, email, originalPassword);

    // Log out
    await page.getByTestId('current_user_button').click();
    await page.getByText('Log Out').click();
    await page.waitForURL(PLAYWRIGHT_BASE_URL + '/login');

    // Request password reset
    await page.goto(PLAYWRIGHT_BASE_URL + '/forgot-password');
    await page.getByLabel('Email').fill(email);
    await page.getByRole('button', { name: 'Email Password Reset Link' }).click();
    await expect(page.getByText('We have emailed your password reset link.')).toBeVisible();

    // Get password reset URL from email
    const resetUrl = await getPasswordResetUrl(request, email);

    // Navigate to reset page
    await page.goto(resetUrl);

    // Fill in mismatched passwords
    await page.getByLabel('Password', { exact: true }).fill('newpassword123');
    await page.getByLabel('Confirm Password').fill('differentpassword456');
    await page.getByRole('button', { name: 'Reset Password' }).click();

    // Should show validation error
    await expect(page.getByText('The password field confirmation does not match.')).toBeVisible();
});

test('shows validation error for short password on reset', async ({ page, request }) => {
    // First register a new user
    const email = `john+${Math.round(Math.random() * 10000)}@doe.com`;
    const originalPassword = 'suchagreatpassword123';
    await registerNewUser(page, email, originalPassword);

    // Log out
    await page.getByTestId('current_user_button').click();
    await page.getByText('Log Out').click();
    await page.waitForURL(PLAYWRIGHT_BASE_URL + '/login');

    // Request password reset
    await page.goto(PLAYWRIGHT_BASE_URL + '/forgot-password');
    await page.getByLabel('Email').fill(email);
    await page.getByRole('button', { name: 'Email Password Reset Link' }).click();
    await expect(page.getByText('We have emailed your password reset link.')).toBeVisible();

    // Get password reset URL from email
    const resetUrl = await getPasswordResetUrl(request, email);

    // Navigate to reset page
    await page.goto(resetUrl);

    // Fill in short password
    await page.getByLabel('Password', { exact: true }).fill('short');
    await page.getByLabel('Confirm Password').fill('short');
    await page.getByRole('button', { name: 'Reset Password' }).click();

    // Should show validation error about minimum length
    await expect(page.getByText('must be at least')).toBeVisible();
});

test('shows error for invalid login credentials', async ({ page }) => {
    await page.goto(PLAYWRIGHT_BASE_URL + '/login');
    await page.getByLabel('Email').fill('nonexistent@example.com');
    await page.getByLabel('Password').fill('wrongpassword123');
    await page.getByRole('button', { name: 'Log in' }).click();

    await expect(
        page.getByText('These credentials do not match our records.')
    ).toBeVisible();
});

test('shows error when registering with existing email', async ({ page }) => {
    const email = `john+${Math.round(Math.random() * 10000)}@doe.com`;
    const password = 'suchagreatpassword123';

    // Register first user
    await registerNewUser(page, email, password);

    // Log out
    await page.getByTestId('current_user_button').click();
    await page.getByText('Log Out').click();
    await page.waitForURL(PLAYWRIGHT_BASE_URL + '/login');

    // Try to register with the same email
    await page.goto(PLAYWRIGHT_BASE_URL + '/register');
    await page.getByLabel('Name').fill('Another User');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByLabel('Confirm Password').fill(password);
    await page.getByLabel('I agree to the Terms of').click();
    await page.getByRole('button', { name: 'Register' }).click();

    // Should show error about email already taken
    await expect(page.getByText('The resource already exists.')).toBeVisible();
});

test('shows validation error for weak password on registration', async ({ page }) => {
    await page.goto(PLAYWRIGHT_BASE_URL + '/register');
    await page.getByLabel('Name').fill('Weak Password User');
    await page.getByLabel('Email').fill(`weak+${Math.round(Math.random() * 10000)}@test.com`);
    await page.getByLabel('Password', { exact: true }).fill('short');
    await page.getByLabel('Confirm Password').fill('short');
    await page.getByLabel('I agree to the Terms of').click();
    await page.getByRole('button', { name: 'Register' }).click();

    await expect(page.getByText('must be at least')).toBeVisible();
});
