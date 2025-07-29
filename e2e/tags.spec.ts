import { expect, Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';

async function goToTagsOverview(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/tags');
}

// Create new project via modal
test('test that creating and deleting a new client via the modal works', async ({ page }) => {
    const newTagName = 'New Tag ' + Math.floor(1 + Math.random() * 10000);
    await goToTagsOverview(page);
    await page.getByRole('button', { name: 'Create Tag' }).click();
    await page.getByPlaceholder('Tag Name').fill(newTagName);
    await Promise.all([
        page.getByRole('button', { name: 'Create Tag' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/tags') &&
                response.request().method() === 'POST' &&
                response.status() === 201 &&
                (await response.json()).data.id !== null &&
                (await response.json()).data.name === newTagName
        ),
    ]);

    await expect(page.getByTestId('tag_table')).toContainText(newTagName);
    const moreButton = page.locator("[aria-label='Actions for Tag " + newTagName + "']");
    moreButton.click();
    const deleteButton = page.locator("[aria-label='Delete Tag " + newTagName + "']");

    await Promise.all([
        deleteButton.click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/tags') &&
                response.request().method() === 'DELETE' &&
                response.status() === 204
        ),
    ]);
    await expect(page.getByTestId('tag_table')).not.toContainText(newTagName);
});
