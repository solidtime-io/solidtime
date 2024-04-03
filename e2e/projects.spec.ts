import { expect, Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';

async function goToProjectsOverview(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/projects');
}

// Create new project via modal
test('test that creating and deleting a new project via the modal works', async ({
    page,
}) => {
    const newProjectName =
        'New Project ' + Math.floor(1 + Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByPlaceholder('Project Name').fill(newProjectName);
    await Promise.all([
        page.getByRole('button', { name: 'Create Project' }).nth(1).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects') &&
                response.request().method() === 'POST' &&
                response.status() === 201 &&
                (await response.json()).data.id !== null &&
                (await response.json()).data.color !== null &&
                (await response.json()).data.client_id === null &&
                (await response.json()).data.name === newProjectName
        ),
    ]);

    await expect(page.getByTestId('project_table')).toContainText(
        newProjectName
    );
    const moreButton = page.locator(
        "[aria-label='Actions for Project " + newProjectName + "']"
    );
    moreButton.click();
    const deleteButton = page.locator(
        "[aria-label='Delete Project " + newProjectName + "']"
    );

    await Promise.all([
        deleteButton.click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects') &&
                response.request().method() === 'DELETE' &&
                response.status() === 204
        ),
    ]);
    await expect(page.getByTestId('project_table')).not.toContainText(
        newProjectName
    );
});

// Create new project with new Client

// Create new project with existing Client

// Delete project via More Options

// Test that project task count is displayed correctly

// Test that active / archive / all filter works (once implemented)
