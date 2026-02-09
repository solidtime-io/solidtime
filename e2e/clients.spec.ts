import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import { createClientViaApi } from './utils/api';

async function goToClientsOverview(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/clients');
}

// Create new client via modal
test('test that creating and deleting a new client via the modal works', async ({ page }) => {
    const newClientName = 'New Project ' + Math.floor(1 + Math.random() * 10000);
    await goToClientsOverview(page);
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
    await moreButton.click();
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

test('test that archiving and unarchiving clients works', async ({ page, ctx }) => {
    const newClientName = 'New Client ' + Math.floor(1 + Math.random() * 10000);
    await createClientViaApi(ctx, { name: newClientName });

    await goToClientsOverview(page);
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

test('test that editing a client name works', async ({ page, ctx }) => {
    const originalName = 'Original Client ' + Math.floor(1 + Math.random() * 10000);
    const updatedName = 'Updated Client ' + Math.floor(1 + Math.random() * 10000);
    await createClientViaApi(ctx, { name: originalName });

    await goToClientsOverview(page);
    await expect(page.getByText(originalName)).toBeVisible();

    // Open edit modal via actions menu
    const moreButton = page.locator("[aria-label='Actions for Client " + originalName + "']");
    await moreButton.click();
    await page.getByTestId('client_edit').click();

    // Update the client name
    await page.getByPlaceholder('Client Name').fill(updatedName);
    await Promise.all([
        page.getByRole('button', { name: 'Update Client' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/clients') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
    ]);

    // Verify updated name is shown and old name is gone
    await expect(page.getByTestId('client_table')).toContainText(updatedName);
    await expect(page.getByTestId('client_table')).not.toContainText(originalName);
});

test('test that deleting a client via actions menu works', async ({ page, ctx }) => {
    const clientName = 'DeleteMe Client ' + Math.floor(1 + Math.random() * 10000);

    await createClientViaApi(ctx, { name: clientName });

    await goToClientsOverview(page);
    await expect(page.getByTestId('client_table')).toContainText(clientName);

    const moreButton = page.locator("[aria-label='Actions for Client " + clientName + "']");
    await moreButton.click();
    const deleteButton = page.locator("[aria-label='Delete Client " + clientName + "']");

    await Promise.all([
        deleteButton.click(),
        page.waitForResponse(
            (response) =>
                response.url().includes('/clients') &&
                response.request().method() === 'DELETE' &&
                response.status() === 204
        ),
    ]);

    await expect(page.getByTestId('client_table')).not.toContainText(clientName);
});
