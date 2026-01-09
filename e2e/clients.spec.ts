import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';

async function goToProjectsOverview(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/clients');
}

// Create new project via modal
test('test that creating and deleting a new client via the modal works', async ({ page }) => {
    const newClientName = 'New Project ' + Math.floor(1 + Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Client' }).click();
    await page.getByPlaceholder('Client Name').fill(newClientName);
    await Promise.all([
        page.getByRole('button', { name: 'Create Client' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/clients') &&
                response.request().method() === 'POST' &&
                response.status() === 201 &&
                (await response.json()).data.id !== null &&
                (await response.json()).data.name === newClientName
        ),
    ]);

    await expect(page.getByTestId('client_table')).toContainText(newClientName);
    const moreButton = page.locator("[aria-label='Actions for Client " + newClientName + "']");
    moreButton.click();
    const deleteButton = page.locator("[aria-label='Delete Client " + newClientName + "']");

    await Promise.all([
        deleteButton.click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/clients') &&
                response.request().method() === 'DELETE' &&
                response.status() === 204
        ),
    ]);
    await expect(page.getByTestId('client_table')).not.toContainText(newClientName);
});

test('test that archiving and unarchiving clients works', async ({ page }) => {
    const newClientName = 'New Client ' + Math.floor(1 + Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Client' }).click();
    await page.getByLabel('Client Name').fill(newClientName);

    await page.getByRole('button', { name: 'Create Client' }).click();
    await expect(page.getByText(newClientName)).toBeVisible();

    await page.getByRole('row').first().getByRole('button').click();
    await Promise.all([
        page.getByRole('menuitem').getByText('Archive').click(),
        expect(page.getByText(newClientName)).not.toBeVisible(),
    ]);
    await Promise.all([
        page.getByRole('tab', { name: 'Archived' }).click(),
        expect(page.getByText(newClientName)).toBeVisible(),
    ]);

    await page.getByRole('row').first().getByRole('button').click();
    await Promise.all([
        page.getByRole('menuitem').getByText('Unarchive').click(),
        expect(page.getByText(newClientName)).not.toBeVisible(),
    ]);
    await Promise.all([
        page.getByRole('tab', { name: 'Active' }).click(),
        expect(page.getByText(newClientName)).toBeVisible(),
    ]);
});

// TODO: Add Name Update Test
