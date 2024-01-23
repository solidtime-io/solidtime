import { expect, test } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';

async function registerNewUser(page, email, password) {
    await page.getByRole('link', { name: 'Register' }).click();
    await page.getByLabel('Name').fill('John Doe');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Password', { exact: true }).fill(password);
    await page.getByLabel('Confirm Password').fill(password);
    await page.getByRole('button', { name: 'Register' }).click();
    await expect(
        page.getByRole('heading', { name: 'Dashboard' })
    ).toBeVisible();
}

test('can register, logout and log back in', async ({ page }) => {
    await page.goto(PLAYWRIGHT_BASE_URL);
    const email = `john+${Math.round(Math.random() * 10000)}@doe.com`;
    const password = 'suchagreatpassword123';
    await registerNewUser(page, email, password);
    await expect(
        page.getByRole('button', { name: "John's Organization" })
    ).toBeVisible();
    await page.locator('#currentUserButton').click();

    await Promise.all([
        page.getByRole('button', { name: 'Log Out' }).click(),
        page.waitForURL(PLAYWRIGHT_BASE_URL + '/'),
    ]);
    await page.goto(PLAYWRIGHT_BASE_URL + '/login');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(
        page.getByRole('heading', { name: 'Dashboard' })
    ).toBeVisible();
});

test('can register and delete account', async ({ page }) => {
    await page.goto(PLAYWRIGHT_BASE_URL);
    const email = `john+${Math.round(Math.random() * 10000)}@doe.com`;
    const password = 'suchagreatpassword123';
    await registerNewUser(page, email, password);
    await page.goto(PLAYWRIGHT_BASE_URL + '/user/profile');
    await page.getByRole('button', { name: 'Delete Account' }).click();
    await page.getByPlaceholder('Password').fill(password);
    await Promise.all([
        page.getByRole('button', { name: 'Delete Account' }).nth(1).click(),
        page.waitForURL(PLAYWRIGHT_BASE_URL + '/'),
    ]);
    await page.goto(PLAYWRIGHT_BASE_URL + '/login');
    await page.getByLabel('Email').fill(email);
    await page.getByLabel('Password').fill(password);
    await page.getByRole('button', { name: 'Log in' }).click();
    await expect(page.getByRole('paragraph')).toContainText(
        'These credentials do not match our records.'
    );
});
