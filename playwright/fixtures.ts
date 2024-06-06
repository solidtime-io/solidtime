import { test as baseTest } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from './config';

export * from '@playwright/test';
export const test = baseTest.extend<object, { workerStorageState: string }>({
    // Use the same storage state for all tests in this worker.
    page: async ({ page }, use) => {
        // Perform authentication steps. Replace these actions with your own.
        await page.goto(PLAYWRIGHT_BASE_URL + '/register');
        await page.getByLabel('Name').fill('John Doe');
        await page
            .getByLabel('Email')
            .fill(`john+${Math.round(Math.random() * 1000000)}@doe.com`);
        await page
            .getByLabel('Password', { exact: true })
            .fill('amazingpassword123');
        await page.getByLabel('Confirm Password').fill('amazingpassword123');
        await page.getByLabel('I agree to the Terms of').click();
        await page.getByRole('button', { name: 'Register' }).click();

        // Wait until the page receives the cookies.
        //
        // Sometimes login flow sets cookies in the process of several redirects.
        // Wait for the final URL to ensure that the cookies are actually set.
        await page.waitForURL(PLAYWRIGHT_BASE_URL + '/dashboard');

        // End of authentication steps.

        await use(page);
    },
});
