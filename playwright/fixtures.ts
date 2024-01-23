import { expect, test as baseTest } from '@playwright/test';
import fs from 'fs';
import path from 'path';
import { PLAYWRIGHT_BASE_URL } from './config';

export * from '@playwright/test';
export const test = baseTest.extend<object, { workerStorageState: string }>({
    // Use the same storage state for all tests in this worker.
    storageState: ({ workerStorageState }, use) => use(workerStorageState),

    // Authenticate once per worker with a worker-scoped fixture.
    workerStorageState: [
        async ({ browser }, use) => {
            // Use parallelIndex as a unique identifier for each worker.
            const id = test.info().parallelIndex;
            const fileName = path.resolve(
                test.info().project.outputDir,
                `.auth/${id}.json`
            );

            if (fs.existsSync(fileName)) {
                // Reuse existing authentication state if any.
                await use(fileName);
                return;
            }

            // Important: make sure we authenticate in a clean environment by unsetting storage state.
            const page = await browser.newPage({ storageState: undefined });

            // Acquire a unique account, for example create a new one.
            // Alternatively, you can have a list of precreated accounts for testing.
            // Make sure that accounts are unique, so that multiple team members
            // can run tests at the same time without interference.
            // const account = await acquireAccount(id);

            // TODO: Use Seeder Accounts instead of creating new ones

            // Perform authentication steps. Replace these actions with your own.
            await page.goto(PLAYWRIGHT_BASE_URL + '/register');
            await page.getByLabel('Name').fill('John Doe');
            await page
                .getByLabel('Email')
                .fill(`john+${Math.round(Math.random() * 10000)}@doe.com`);
            await page
                .getByLabel('Password', { exact: true })
                .fill('amazingpassword123');
            await page
                .getByLabel('Confirm Password')
                .fill('amazingpassword123');
            await page.getByRole('button', { name: 'Register' }).click();

            // Wait until the page receives the cookies.
            //
            // Sometimes login flow sets cookies in the process of several redirects.
            // Wait for the final URL to ensure that the cookies are actually set.
            await page.waitForURL(PLAYWRIGHT_BASE_URL + '/dashboard');

            // Alternatively, you can wait until the page reaches a state where all cookies are set.
            await expect(
                page.getByRole('heading', { name: 'Dashboard' })
            ).toBeVisible();

            // End of authentication steps.

            await page.context().storageState({ path: fileName });
            await page.close();
            await use(fileName);
        },
        { scope: 'worker' },
    ],
});
