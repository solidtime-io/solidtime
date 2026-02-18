import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import { createTagViaApi } from './utils/api';
import { getTableRowNames } from './utils/table';

async function goToTagsOverview(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/tags');
}

test('test that creating and deleting a new tag via the modal works', async ({ page }) => {
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

test('test that editing a tag name works', async ({ page, ctx }) => {
    const originalTagName = 'Original Tag ' + Math.floor(1 + Math.random() * 10000);
    const updatedTagName = 'Updated Tag ' + Math.floor(1 + Math.random() * 10000);

    await createTagViaApi(ctx, { name: originalTagName });

    await goToTagsOverview(page);
    await expect(page.getByTestId('tag_table')).toContainText(originalTagName);

    // Open actions menu and click Edit
    const moreButton = page.locator("[aria-label='Actions for Tag " + originalTagName + "']");
    await moreButton.click();
    await page.getByRole('menuitem').getByText('Edit').click();

    // Update the tag name in the edit modal
    await expect(page.getByRole('dialog')).toBeVisible();
    await page.getByPlaceholder('Tag Name').fill(updatedTagName);
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/tags/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page.getByRole('button', { name: 'Update Tag' }).click(),
    ]);

    // Verify the table shows the updated name
    await expect(page.getByTestId('tag_table')).toContainText(updatedTagName);
    await expect(page.getByTestId('tag_table')).not.toContainText(originalTagName);
});

test('test that multiple tags can be created via API and displayed in the table', async ({
    page,
    ctx,
}) => {
    const tagName1 = 'TagA ' + Math.floor(1 + Math.random() * 10000);
    const tagName2 = 'TagB ' + Math.floor(1 + Math.random() * 10000);

    await createTagViaApi(ctx, { name: tagName1 });
    await createTagViaApi(ctx, { name: tagName2 });

    await goToTagsOverview(page);
    await expect(page.getByTestId('tag_table')).toContainText(tagName1);
    await expect(page.getByTestId('tag_table')).toContainText(tagName2);
});

// =============================================
// Sorting Tests
// =============================================

async function clearTagTableState(page: Page) {
    await page.evaluate(() => {
        localStorage.removeItem('tag-table-state');
    });
}

test('test that sorting tags by name works', async ({ page, ctx }) => {
    await createTagViaApi(ctx, { name: 'AAA SortTag' });
    await createTagViaApi(ctx, { name: 'ZZZ SortTag' });

    await goToTagsOverview(page);
    await clearTagTableState(page);
    await page.reload();

    const table = page.getByTestId('tag_table');
    await expect(table).toBeVisible();

    // Default is name asc
    let names = await getTableRowNames(table);
    expect(names.indexOf('AAA SortTag')).toBeLessThan(names.indexOf('ZZZ SortTag'));

    const nameHeader = table.getByText('Name').first();
    await nameHeader.click(); // toggle to desc
    names = await getTableRowNames(table);
    expect(names.indexOf('ZZZ SortTag')).toBeLessThan(names.indexOf('AAA SortTag'));
});

test('test that tag sort state persists after page reload', async ({ page }) => {
    await goToTagsOverview(page);
    await clearTagTableState(page);
    await page.reload();

    const table = page.getByTestId('tag_table');
    await expect(table).toBeVisible();

    const nameHeader = table.getByText('Name').first();
    await nameHeader.click(); // toggle to desc
    await expect(nameHeader.locator('svg')).toBeVisible();

    await page.reload();

    await expect(page.getByTestId('tag_table')).toBeVisible();
    await expect(
        page.getByTestId('tag_table').getByText('Name').first().locator('svg')
    ).toBeVisible();
});

// =============================================
// Employee Permission Tests
// =============================================

test.describe('Employee Tags Restrictions', () => {
    test('employee can view tags but cannot create', async ({ ctx, employee }) => {
        const tagName = 'EmpViewTag ' + Math.floor(Math.random() * 10000);
        await createTagViaApi(ctx, { name: tagName });

        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/tags');
        await expect(employee.page.getByTestId('tags_view')).toBeVisible({ timeout: 10000 });

        // Employee can see the tag (tags are visible to all members with tags:view)
        await expect(employee.page.getByText(tagName)).toBeVisible({ timeout: 10000 });

        // Employee cannot see Create Tag button
        await expect(employee.page.getByRole('button', { name: 'Create Tag' })).not.toBeVisible();
    });

    test('employee cannot see edit/delete actions on tags', async ({ ctx, employee }) => {
        const tagName = 'EmpActionsTag ' + Math.floor(Math.random() * 10000);
        await createTagViaApi(ctx, { name: tagName });

        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/tags');
        await expect(employee.page.getByText(tagName)).toBeVisible({ timeout: 10000 });

        // Actions button should not be visible for employee
        const actionsButton = employee.page.locator(`[aria-label='Actions for Tag ${tagName}']`);
        await expect(actionsButton).not.toBeVisible();
    });
});
