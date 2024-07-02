import { expect, Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';

async function goToProjectsOverview(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/projects');
}

// Create new project via modal
test('test that creating and deleting a new tag in a new project works', async ({
    page,
}) => {
    const newProjectName =
        'New Project ' + Math.floor(1 + Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(newProjectName);
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

    await page.getByText(newProjectName).click();

    const newTaskName = 'New Project ' + Math.floor(1 + Math.random() * 10000);

    await page.getByRole('button', { name: 'Create Task' }).click();
    await page.getByPlaceholder('Task Name').fill(newTaskName);

    await Promise.all([
        page.getByRole('button', { name: 'Create Task' }).nth(1).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/tasks') &&
                response.request().method() === 'POST' &&
                response.status() === 201 &&
                (await response.json()).data.id !== null &&
                (await response.json()).data.project_id !== null &&
                (await response.json()).data.name === newTaskName
        ),
    ]);

    await expect(page.getByTestId('task_table')).toContainText(newTaskName);

    const taskMoreButton = page.locator(
        "[aria-label='Actions for Task " + newTaskName + "']"
    );
    taskMoreButton.click();
    const taskDeleteButton = page.locator(
        "[aria-label='Delete Task " + newTaskName + "']"
    );

    await Promise.all([
        taskDeleteButton.click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/tasks') &&
                response.request().method() === 'DELETE' &&
                response.status() === 204
        ),
    ]);
    await expect(page.getByTestId('task_table')).not.toContainText(newTaskName);

    await goToProjectsOverview(page);

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

test('test that archiving and unarchiving tasks works', async ({ page }) => {
    const newProjectName =
        'New Project ' + Math.floor(1 + Math.random() * 10000);
    const newTaskName = 'New Project ' + Math.floor(1 + Math.random() * 10000);

    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(newProjectName);

    await page.getByRole('button', { name: 'Create Project' }).nth(1).click();
    await expect(page.getByText(newProjectName)).toBeVisible();

    await page.getByText(newProjectName).click();

    await page.getByRole('button', { name: 'Create Task' }).click();
    await page.getByPlaceholder('Task Name').fill(newTaskName);
    await page.getByRole('button', { name: 'Create Task' }).nth(1).click();

    await expect(page.getByRole('table')).toContainText(newTaskName);

    await page.getByRole('row').first().getByRole('button').click();
    await Promise.all([
        page.getByRole('button').getByText('Mark as done').first().click(),
        expect(page.getByText(newTaskName)).not.toBeVisible(),
    ]);
    await Promise.all([
        page.getByRole('tab', { name: 'Done' }).click(),
        expect(page.getByText(newTaskName)).toBeVisible(),
    ]);

    await page.getByRole('row').first().getByRole('button').click();
    await Promise.all([
        page.getByRole('button').getByText('Mark as active').first().click(),
        expect(page.getByText(newTaskName)).not.toBeVisible(),
    ]);
    await Promise.all([
        page.getByRole('tab', { name: 'Active' }).click(),
        expect(page.getByText(newTaskName)).toBeVisible(),
    ]);
});

// Create new project with new Client

// Create new project with existing Client

// Delete project via More Options

// Test that project task count is displayed correctly

// Test that active / archive / all filter works (once implemented)

// Test update task name
