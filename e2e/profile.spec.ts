import { test, expect } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';

test('test that user name can be updated', async ({ page }) => {
    await page.goto(PLAYWRIGHT_BASE_URL + '/user/profile');
    await page.getByLabel('Name').fill('NEW NAME');
    await Promise.all([
        page.getByRole('button', { name: 'Save' }).first().click(),
        page.waitForResponse('**/user/profile-information'),
    ]);
    await page.reload();
    await expect(page.getByLabel('Name')).toHaveValue('NEW NAME');
});

test.skip('test that user email can be updated', async ({ page }) => {
    // this does not work because of email verification currently
    await page.goto(PLAYWRIGHT_BASE_URL + '/user/profile');
    const emailId = Math.round(Math.random() * 10000);
    await page.getByLabel('Email').fill(`newemail+${emailId}@test.com`);
    await page.getByRole('button', { name: 'Save' }).first().click();
    await page.reload();
    await expect(page.getByLabel('Email')).toHaveValue(
        `newemail+${emailId}@test.com`
    );
});
