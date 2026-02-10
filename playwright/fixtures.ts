import { test as baseTest } from '@playwright/test';
import type { Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL, TEST_USER_PASSWORD } from './config';
import { type TestContext, setupTestContext } from '../e2e/utils/api';
import { setupEmployeeUser } from '../e2e/utils/members';

export * from '@playwright/test';
export type { TestContext };

export interface EmployeeFixture {
    page: Page;
    memberId: string;
}

/**
 * API-based authentication fixture - creates a new user via HTTP requests instead of UI interactions.
 * This is ~10-25x faster than UI-based authentication (~100-200ms vs ~3-5s).
 *
 * Uses page.context().request() to ensure cookies are shared between the API request and page.
 */
export const test = baseTest.extend<
    { ctx: TestContext; employee: EmployeeFixture },
    { workerStorageState: string }
>({
    page: async ({ page }, use) => {
        // Generate unique email for this test
        const email = `john+${Date.now()}_${Math.floor(Math.random() * 10000)}@doe.com`;
        const password = TEST_USER_PASSWORD;
        const name = 'John Doe';

        // Use page.context().request() so cookies are automatically shared with the page
        const request = page.context().request;

        // Step 1: Visit the register page to get CSRF token and initial session
        const csrfResponse = await request.get(`${PLAYWRIGHT_BASE_URL}/register`, {
            maxRedirects: 0,
        });

        // Extract XSRF-TOKEN from cookies
        const cookies = csrfResponse.headers()['set-cookie'];
        let xsrfToken = '';
        if (cookies) {
            const xsrfMatch = cookies.match(/XSRF-TOKEN=([^;]+)/);
            if (xsrfMatch) {
                xsrfToken = decodeURIComponent(xsrfMatch[1]);
            }
        }

        // Step 2: Register via API (Laravel Fortify web routes)
        const registerResponse = await request.post(`${PLAYWRIGHT_BASE_URL}/register`, {
            headers: {
                'X-XSRF-TOKEN': xsrfToken,
                'Content-Type': 'application/x-www-form-urlencoded',
                'Accept': 'text/html',
            },
            form: {
                name,
                email,
                password,
                password_confirmation: password,
                terms: 'on',
            },
            maxRedirects: 0,
        });

        // Check if registration was successful (should redirect to dashboard)
        if (registerResponse.status() !== 302) {
            console.error('API registration failed, falling back to UI-based registration');

            // Fall back to UI-based registration
            await page.goto(`${PLAYWRIGHT_BASE_URL}/register`);
            await page.getByLabel('Name').fill(name);
            await page.getByLabel('Email').fill(email);
            await page.getByLabel('Password', { exact: true }).fill(password);
            await page.getByLabel('Confirm Password').fill(password);
            await page.getByLabel('I agree to the Terms of').click();
            await page.getByRole('button', { name: 'Register' }).click();
            await page.waitForURL(`${PLAYWRIGHT_BASE_URL}/dashboard`);
        } else {
            // Registration succeeded - cookies are already set in the context from the request
            // Just navigate to dashboard to verify
            await page.goto(`${PLAYWRIGHT_BASE_URL}/dashboard`);
            await page.waitForLoadState('domcontentloaded');
        }

        await use(page);
    },

    ctx: async ({ page }, use) => {
        const ctx = await setupTestContext(page);
        await use(ctx);
    },

    employee: async ({ page, ctx, browser }, use) => {
        const { employeePage, employeeMemberId, closeEmployee } = await setupEmployeeUser(
            page,
            ctx,
            browser
        );
        await use({ page: employeePage, memberId: employeeMemberId });
        await closeEmployee();
    },
});
