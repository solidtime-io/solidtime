import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import { createProjectViaApi, createTaskViaApi, createClientViaApi } from './utils/api';

async function goToProjectsOverview(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/projects');
}

test('test that creating and deleting a new task in a new project works', async ({ page }) => {
    const newProjectName = 'New Project ' + Math.floor(1 + Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(newProjectName);
    await Promise.all([
        page.getByRole('button', { name: 'Create Project' }).click(),
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

    await expect(page.getByTestId('project_table')).toContainText(newProjectName);
    await page.getByText(newProjectName).click();

    const newTaskName = 'New Task ' + Math.floor(1 + Math.random() * 10000);
    await page.getByRole('button', { name: 'Create Task' }).click();
    await page.getByPlaceholder('Task Name').fill(newTaskName);

    await Promise.all([
        page.getByRole('button', { name: 'Create Task' }).click(),
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

    const taskMoreButton = page.locator("[aria-label='Actions for Task " + newTaskName + "']");
    taskMoreButton.click();
    const taskDeleteButton = page.locator("[aria-label='Delete Task " + newTaskName + "']");

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

    const moreButton = page.locator("[aria-label='Actions for Project " + newProjectName + "']");
    moreButton.click();
    const deleteButton = page.locator("[aria-label='Delete Project " + newProjectName + "']");

    await Promise.all([
        deleteButton.click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects') &&
                response.request().method() === 'DELETE' &&
                response.status() === 204
        ),
    ]);
    await expect(page.getByTestId('project_table')).not.toContainText(newProjectName);
});

test('test that archiving and unarchiving tasks works', async ({ page, ctx }) => {
    const newProjectName = 'New Project ' + Math.floor(1 + Math.random() * 10000);
    const newTaskName = 'New Task ' + Math.floor(1 + Math.random() * 10000);

    const project = await createProjectViaApi(ctx, { name: newProjectName });
    await createTaskViaApi(ctx, { name: newTaskName, project_id: project.id });

    await page.goto(PLAYWRIGHT_BASE_URL + '/projects/' + project.id);
    await expect(page.getByRole('table')).toContainText(newTaskName);

    await page.getByRole('row').first().getByRole('button').click();
    await Promise.all([
        page.getByRole('menuitem').getByText('Mark as done').first().click(),
        expect(page.getByText(newTaskName)).not.toBeVisible(),
    ]);
    await Promise.all([
        page.getByRole('tab', { name: 'Done' }).click(),
        expect(page.getByText(newTaskName)).toBeVisible(),
    ]);

    await page.getByRole('row').first().getByRole('button').click();
    await Promise.all([
        page.getByRole('menuitem').getByText('Mark as active').first().click(),
        expect(page.getByText(newTaskName)).not.toBeVisible(),
    ]);
    await Promise.all([
        page.getByRole('tab', { name: 'Active' }).click(),
        expect(page.getByText(newTaskName)).toBeVisible(),
    ]);
});

test('test that editing a task name works', async ({ page, ctx }) => {
    const projectName = 'TaskEdit Project ' + Math.floor(1 + Math.random() * 10000);
    const originalTaskName = 'Original Task ' + Math.floor(1 + Math.random() * 10000);
    const updatedTaskName = 'Updated Task ' + Math.floor(1 + Math.random() * 10000);

    const project = await createProjectViaApi(ctx, { name: projectName });
    await createTaskViaApi(ctx, { name: originalTaskName, project_id: project.id });

    await page.goto(PLAYWRIGHT_BASE_URL + '/projects/' + project.id);
    await expect(page.getByTestId('task_table')).toContainText(originalTaskName);

    // Open actions menu and click Edit
    const moreButton = page.locator("[aria-label='Actions for Task " + originalTaskName + "']");
    await moreButton.click();
    await page.getByRole('menuitem').getByText('Edit').click();

    // Update the task name
    await expect(page.getByRole('dialog')).toBeVisible();
    await page.getByPlaceholder('Task Name').fill(updatedTaskName);
    await Promise.all([
        page.getByRole('button', { name: 'Update Task' }).click(),
        page.waitForResponse(
            (response) =>
                response.url().includes('/tasks') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
    ]);

    await expect(page.getByTestId('task_table')).toContainText(updatedTaskName);
    await expect(page.getByTestId('task_table')).not.toContainText(originalTaskName);
});

test('test that creating a project with an existing client works', async ({ page, ctx }) => {
    const clientName = 'Existing Client ' + Math.floor(1 + Math.random() * 10000);
    const projectName = 'Project With Client ' + Math.floor(1 + Math.random() * 10000);

    await createClientViaApi(ctx, { name: clientName });

    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(projectName);

    // Select the existing client
    await page.getByRole('dialog').getByRole('button', { name: 'No Client' }).click();
    await page.getByRole('option', { name: clientName }).click();

    await Promise.all([
        page.getByRole('dialog').getByRole('button', { name: 'Create Project' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects') &&
                response.request().method() === 'POST' &&
                response.status() === 201 &&
                (await response.json()).data.client_id !== null
        ),
    ]);

    await expect(page.getByTestId('project_table')).toContainText(projectName);
    await expect(page.getByTestId('project_table')).toContainText(clientName);
});

test('test that multiple tasks are displayed on project detail page', async ({ page, ctx }) => {
    const projectName = 'TaskCount Project ' + Math.floor(1 + Math.random() * 10000);
    const taskName1 = 'CountTask A ' + Math.floor(1 + Math.random() * 10000);
    const taskName2 = 'CountTask B ' + Math.floor(1 + Math.random() * 10000);

    const project = await createProjectViaApi(ctx, { name: projectName });
    await createTaskViaApi(ctx, { name: taskName1, project_id: project.id });
    await createTaskViaApi(ctx, { name: taskName2, project_id: project.id });

    await page.goto(PLAYWRIGHT_BASE_URL + '/projects/' + project.id);
    await expect(page.getByText(taskName1)).toBeVisible();
    await expect(page.getByText(taskName2)).toBeVisible();
});
