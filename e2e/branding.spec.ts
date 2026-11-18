import { test as guestTest, expect } from '@playwright/test';
import type { APIRequestContext } from '@playwright/test';
import { test } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL, TEST_USER_PASSWORD, isExtensionEnabled } from '../playwright/config';
import { searchEmails } from './utils/mailpit';
import { MAILPIT_BASE_URL } from '../playwright/config';

/**
 * The default solidtime branding: name, logo, favicon, web manifest and emails.
 * The Whitelabel extension replaces the branding, its own e2e specs cover that.
 */
const whitelabelEnabled = isExtensionEnabled('Whitelabel');

guestTest.describe('Branding (guest)', () => {
    guestTest.skip(whitelabelEnabled, 'The Whitelabel extension replaces the default branding');

    guestTest('login page shows the solidtime name and logo', async ({ page }) => {
        await page.goto(PLAYWRIGHT_BASE_URL + '/login');

        await expect(page).toHaveTitle('Log in - solidtime');
        const logoLink = page.locator('a[href="/"]').first();
        await expect(logoLink.locator('svg')).toBeVisible();
        await expect(logoLink.locator('img')).toHaveCount(0);
    });

    guestTest('page head uses the solidtime favicons and theme color', async ({ page }) => {
        await page.goto(PLAYWRIGHT_BASE_URL + '/login');

        await expect(page.locator('link[rel="icon"][sizes="32x32"]')).toHaveAttribute(
            'href',
            '/favicons/favicon-32x32.png'
        );
        await expect(page.locator('meta[name="theme-color"]')).toHaveAttribute(
            'content',
            '#000000'
        );
        await expect(page.locator('link[rel="manifest"]')).toHaveAttribute(
            'href',
            '/site.webmanifest'
        );
    });

    guestTest('web manifest uses the solidtime name and icons', async ({ request }) => {
        const response = await request.get(PLAYWRIGHT_BASE_URL + '/site.webmanifest');

        expect(response.status()).toBe(200);
        const manifest = await response.json();
        expect(manifest.name).toBe('solidtime');
        expect(manifest.short_name).toBe('solidtime');
        expect(manifest.theme_color).toBe('#000000');
        expect(manifest.icons[0].src).toBe('/favicons/android-chrome-192x192.png');
    });

    guestTest('favicon.ico serves the solidtime favicon directly', async ({ request }) => {
        // Bots request /favicon.ico directly and do not always follow redirects
        const response = await request.get(PLAYWRIGHT_BASE_URL + '/favicon.ico', {
            maxRedirects: 0,
        });

        expect(response.status()).toBe(200);
        expect(response.headers()['content-type']).toBe('image/x-icon');
        expect((await response.body()).length).toBeGreaterThan(0);
    });

    guestTest('emails show the solidtime logo and name', async ({ page, request }) => {
        // Register a user and request a password reset email for it
        const email = `branding+${Date.now()}_${Math.floor(Math.random() * 10000)}@doe.com`;
        await page.goto(PLAYWRIGHT_BASE_URL + '/register');
        await page.getByLabel('Name').fill('John Doe');
        await page.getByLabel('Email').fill(email);
        await page.getByLabel('Password', { exact: true }).fill(TEST_USER_PASSWORD);
        await page.getByLabel('Confirm Password').fill(TEST_USER_PASSWORD);
        await page.getByLabel('I agree to the Terms of').click();
        await page.getByRole('button', { name: 'Register' }).click();
        await expect(page.getByTestId('dashboard_view')).toBeVisible();
        await page.context().clearCookies();
        await page.goto(PLAYWRIGHT_BASE_URL + '/forgot-password');
        await page.getByLabel('Email').fill(email);
        await page.getByRole('button', { name: 'Email Password Reset Link' }).click();
        await expect(page.getByText('We have emailed your password reset link.')).toBeVisible();

        const message = await waitForMessage(request, email);

        expect(message.HTML).toContain('images/solidtime-logo.png');
        expect(message.HTML).toContain('images/solidtime-logo.svg');
        expect(message.HTML).toMatch(/Regards,<br>\s*solidtime/);
        expect(message.From.Name).toBe('solidtime');
    });
});

test.describe('Branding (logged in)', () => {
    test.skip(whitelabelEnabled, 'The Whitelabel extension replaces the default branding');

    test('page titles keep the solidtime name across Inertia visits', async ({ page }) => {
        await page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
        await expect(page).toHaveTitle('Dashboard - solidtime');

        // An Inertia visit does not load the once prop with the name again, the client keeps it
        await page.getByRole('link', { name: 'Projects' }).click();
        await expect(page.getByTestId('projects_view')).toBeVisible();
        await expect(page).toHaveTitle('Projects - solidtime');
    });
});

async function waitForMessage(
    request: APIRequestContext,
    recipientEmail: string
): Promise<{ HTML: string; From: { Name: string; Address: string } }> {
    for (let attempt = 0; attempt < 10; attempt++) {
        const result = await searchEmails(request, `to:${encodeURIComponent(recipientEmail)}`);
        if (result.messages.length > 0) {
            const response = await request.get(
                `${MAILPIT_BASE_URL}/api/v1/message/${result.messages[0].ID}`
            );
            return response.json();
        }
        await new Promise((resolve) => setTimeout(resolve, 500));
    }
    throw new Error(`No email to ${recipientEmail} arrived`);
}
