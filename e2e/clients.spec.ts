import { expect, Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';

async function goToProjectsOverview(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/clients');
}

// Create new project via modal
test('test that creating and deleting a new client via the modal works', async ({
    page,
}) => {
    const newClientName =
        'New Project ' + Math.floor(1 + Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Client' }).click();
    await page.getByPlaceholder('Client Name').fill(newClientName);
    await Promise.all([
        page.getByRole('button', { name: 'Create Client' }).nth(1).click(),
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
    const moreButton = page.locator(
        "[aria-label='Actions for Client " + newClientName + "']"
    );
    moreButton.click();
    const deleteButton = page.locator(
        "[aria-label='Delete Client " + newClientName + "']"
    );

    await Promise.all([
        deleteButton.click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/clients') &&
                response.request().method() === 'DELETE' &&
                response.status() === 204
        ),
    ]);
    await expect(page.getByTestId('client_table')).not.toContainText(
        newClientName
    );
});

// TODO: Add Name Update Test
