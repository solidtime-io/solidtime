import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import { formatCentsWithOrganizationDefaults } from './utils/money';
import type { CurrencyFormat } from '../resources/js/packages/ui/src/utils/money';
import {
    createProjectViaApi,
    createPublicProjectViaApi,
    createTaskViaApi,
    updateOrganizationSettingViaApi,
} from './utils/api';

async function goToProjectsOverview(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/projects');
}

// Helper to clear localStorage before tests that check persistence
async function clearProjectTableState(page: Page) {
    await page.evaluate(() => {
        localStorage.removeItem('project-table-state');
    });
}

// Create new project via modal
test('test that creating and deleting a new project via the modal works', async ({ page }) => {
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
    const moreButton = page.locator("[aria-label='Actions for Project " + newProjectName + "']");
    await moreButton.click();
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

// Helper to select a status filter using the new dropdown UI
async function selectStatusFilter(page: Page, status: 'Active' | 'Archived') {
    // Click the Filter button to open the dropdown
    await page.getByRole('button', { name: 'Filter projects' }).click();
    // Click on Status submenu
    await page.getByRole('menuitem', { name: 'Status' }).click();
    // Select the status option
    await page.getByRole('menuitem', { name: status }).click();
}

// Helper to remove status filter by clicking the X on the badge
async function removeStatusFilter(page: Page) {
    const statusBadge = page.getByTestId('status-filter-badge');
    // Click the remove button (second button in the badge, contains XMarkIcon)
    await statusBadge.locator('button').last().click();
}

test('test that archiving and unarchiving projects works', async ({ page, ctx }) => {
    const newProjectName = 'New Project ' + Math.floor(1 + Math.random() * 10000);
    await createProjectViaApi(ctx, { name: newProjectName });

    await goToProjectsOverview(page);
    await clearProjectTableState(page);
    await page.reload();
    await expect(page.getByText(newProjectName)).toBeVisible({ timeout: 10000 });

    // Archive the project
    await page.getByRole('row').first().getByRole('button').click();
    await page.getByRole('menuitem').getByText('Archive').first().click();

    // Project should still be visible since default is "all" (no filter)
    await expect(page.getByText(newProjectName)).toBeVisible();

    // Apply Active filter - archived project should disappear
    await selectStatusFilter(page, 'Active');
    await expect(page.getByText(newProjectName)).not.toBeVisible();

    // Remove Active filter and apply Archived filter
    await removeStatusFilter(page);
    await selectStatusFilter(page, 'Archived');
    await expect(page.getByText(newProjectName)).toBeVisible();

    // Unarchive the project
    await page.getByRole('row').first().getByRole('button').click();
    await page.getByRole('menuitem').getByText('Unarchive').first().click();

    // Project should disappear from Archived view
    await expect(page.getByText(newProjectName)).not.toBeVisible();

    // Remove Archived filter and apply Active filter to see the project
    await removeStatusFilter(page);
    await selectStatusFilter(page, 'Active');
    await expect(page.getByText(newProjectName)).toBeVisible();
});

test('test that updating billable rate works with existing time entries', async ({ page, ctx }) => {
    const newProjectName = 'New Project ' + Math.floor(1 + Math.random() * 10000);
    const newBillableRate = Math.round(Math.random() * 10000);
    await createProjectViaApi(ctx, { name: newProjectName });

    await goToProjectsOverview(page);
    await expect(page.getByText(newProjectName)).toBeVisible({ timeout: 10000 });

    await page.getByRole('row').first().getByRole('button').click();
    await page.getByRole('menuitem').getByText('Edit').first().click();
    await page.getByText('Non-Billable').click();
    await page.getByText('Custom Rate').click();
    await page.getByPlaceholder('Billable Rate').fill(newBillableRate.toString());
    await page.getByRole('button', { name: 'Update Project' }).click();

    await Promise.all([
        page.locator('button').filter({ hasText: 'Yes, update existing time' }).click(),
        page.waitForRequest(
            async (request) =>
                request.url().includes('/projects/') &&
                request.method() === 'PUT' &&
                request.postDataJSON().billable_rate === newBillableRate * 100
        ),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200 &&
                (await response.json()).data.billable_rate === newBillableRate * 100
        ),
    ]);
    await expect(
        page
            .getByRole('row')
            .first()
            .getByText(formatCentsWithOrganizationDefaults(newBillableRate * 100))
    ).toBeVisible();
});

// Sorting tests
test('test that sorting projects by name works', async ({ page }) => {
    await goToProjectsOverview(page);
    await clearProjectTableState(page);
    await page.reload();

    // Wait for the table to load
    await expect(page.getByTestId('project_table')).toBeVisible();

    // Get initial project names
    const getProjectNames = async () => {
        const rows = page
            .getByTestId('project_table')
            .locator('[data-testid="project_table"] > div')
            .filter({ hasNot: page.locator('.border-t') });
        const names: string[] = [];
        const count = await page.getByTestId('project_table').getByRole('row').count();
        for (let i = 0; i < count; i++) {
            const row = page.getByTestId('project_table').getByRole('row').nth(i);
            const nameCell = row.locator('div').first();
            const text = await nameCell.textContent();
            if (text) {
                names.push(text.trim());
            }
        }
        return names;
    };

    // Click on Name header to sort ascending (default should already be ascending)
    const nameHeader = page.getByText('Name').first();
    await nameHeader.click();

    // Wait for sort indicator to appear
    await expect(nameHeader.locator('svg')).toBeVisible();

    // Click again to sort descending
    await nameHeader.click();

    // Verify the sort indicator is still visible (showing descending)
    await expect(nameHeader.locator('svg')).toBeVisible();
});

test('test that sorting projects by status works', async ({ page }) => {
    await goToProjectsOverview(page);
    await clearProjectTableState(page);
    await page.reload();

    // Default is "all" so no filter needed - Wait for the table to load
    await expect(page.getByTestId('project_table')).toBeVisible();

    // Click on Status header to sort
    const statusHeader = page.getByText('Status').first();
    await statusHeader.click();

    // Sort indicator should be visible
    await expect(statusHeader.locator('svg')).toBeVisible();
});

// Filter tests
test('test that filtering projects by status works', async ({ page, ctx }) => {
    const newProjectName = 'Filter Test Project ' + Math.floor(1 + Math.random() * 10000);
    await createProjectViaApi(ctx, { name: newProjectName });

    await goToProjectsOverview(page);
    await clearProjectTableState(page);
    await page.reload();
    await expect(page.getByText(newProjectName)).toBeVisible({ timeout: 10000 });

    // Archive the project
    await page.getByRole('row').first().getByRole('button').click();
    await page.getByRole('menuitem').getByText('Archive').first().click();

    // Project should still be visible (default is "all" - no filter)
    await expect(page.getByText(newProjectName)).toBeVisible();

    // Apply Active filter - archived project should disappear
    await selectStatusFilter(page, 'Active');
    await expect(page.getByText(newProjectName)).not.toBeVisible();

    // Remove Active filter - project should reappear (back to "all")
    await removeStatusFilter(page);
    await expect(page.getByText(newProjectName)).toBeVisible();

    // Apply Archived filter - project should still be visible
    await selectStatusFilter(page, 'Archived');
    await expect(page.getByText(newProjectName)).toBeVisible();

    // Remove Archived filter and apply Active filter - project should not be visible
    await removeStatusFilter(page);
    await selectStatusFilter(page, 'Active');
    await expect(page.getByText(newProjectName)).not.toBeVisible();
});

test('test that filter state persists after page reload', async ({ page }) => {
    await goToProjectsOverview(page);
    await clearProjectTableState(page);
    await page.reload();

    // Apply Active status filter
    await selectStatusFilter(page, 'Active');

    // Verify the filter badge is visible
    await expect(page.getByTestId('status-filter-badge')).toBeVisible();

    // Reload the page
    await page.reload();

    // Verify the filter badge is still visible after reload
    await expect(page.getByTestId('status-filter-badge')).toBeVisible();
});

test('test that sort state persists after page reload', async ({ page }) => {
    await goToProjectsOverview(page);
    await clearProjectTableState(page);
    await page.reload();

    // Click on Name header twice to sort descending
    const nameHeader = page.getByText('Name').first();
    await nameHeader.click();
    await expect(nameHeader.locator('svg')).toBeVisible();
    await nameHeader.click();

    // Reload the page
    await page.reload();

    // Verify descending sort indicator is visible on Name column
    await expect(page.getByTestId('project_table')).toBeVisible();
});

test('test that custom billable rate is displayed correctly on project detail page', async ({
    page,
    ctx,
}) => {
    const newProjectName = 'Billable Rate Project ' + Math.floor(1 + Math.random() * 10000);
    const newBillableRate = Math.round(10 + Math.random() * 1000);
    await createProjectViaApi(ctx, { name: newProjectName });

    await goToProjectsOverview(page);
    await expect(page.getByText(newProjectName)).toBeVisible({ timeout: 10000 });

    // Edit the project to set a custom billable rate
    await page.getByRole('row').first().getByRole('button').click();
    await page.getByRole('menuitem').getByText('Edit').first().click();
    await page.getByText('Non-Billable').click();
    await page.getByText('Custom Rate').click();
    await page.getByPlaceholder('Billable Rate').fill(newBillableRate.toString());
    await page.getByRole('button', { name: 'Update Project' }).click();

    await Promise.all([
        page.locator('button').filter({ hasText: 'Yes, update existing time' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
    ]);

    // Navigate to the project detail page by clicking the project name
    await page.getByText(newProjectName).first().click();
    await page.waitForURL(/\/projects\/[a-f0-9-]+/);

    // Verify the badge displays the correctly formatted billable rate
    const expectedFormattedRate = formatCentsWithOrganizationDefaults(newBillableRate * 100);
    await expect(page.locator('nav[aria-label="Breadcrumb"]').locator('..')).toContainText(
        expectedFormattedRate
    );
});

// Tests for estimated time input (Issue #460)
test('test that creating a project with estimated time in human-readable format works', async ({
    page,
}) => {
    const newProjectName = 'Estimated Time Project ' + Math.floor(1 + Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(newProjectName);

    // Fill in estimated time using human-readable format
    const estimatedTimeInput = page.getByPlaceholder('e.g. 2h 30m or 1.5');
    await estimatedTimeInput.fill('2h 30m');
    await estimatedTimeInput.press('Tab');

    await Promise.all([
        page.getByRole('button', { name: 'Create Project' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects') &&
                response.request().method() === 'POST' &&
                response.status() === 201 &&
                // 2h 30m = 9000 seconds
                (await response.json()).data.estimated_time === 9000
        ),
    ]);

    await expect(page.getByTestId('project_table')).toContainText(newProjectName);
});

test('test that creating a project with estimated time using decimal notation works', async ({
    page,
}) => {
    const newProjectName = 'Decimal Estimated Project ' + Math.floor(1 + Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(newProjectName);

    // Fill in estimated time using decimal notation (1.5 hours = 1h 30m)
    const estimatedTimeInput = page.getByPlaceholder('e.g. 2h 30m or 1.5');
    await estimatedTimeInput.fill('1.5');
    await estimatedTimeInput.press('Tab');

    await Promise.all([
        page.getByRole('button', { name: 'Create Project' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects') &&
                response.request().method() === 'POST' &&
                response.status() === 201 &&
                // 1.5 hours = 5400 seconds
                (await response.json()).data.estimated_time === 5400
        ),
    ]);

    await expect(page.getByTestId('project_table')).toContainText(newProjectName);
});

test('test that creating a project with estimated time using comma decimal notation works', async ({
    page,
}) => {
    const newProjectName = 'Comma Decimal Project ' + Math.floor(1 + Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(newProjectName);

    // Fill in estimated time using comma decimal notation (2,5 hours = 2h 30m)
    const estimatedTimeInput = page.getByPlaceholder('e.g. 2h 30m or 1.5');
    await estimatedTimeInput.fill('2,5');
    await estimatedTimeInput.press('Tab');

    await Promise.all([
        page.getByRole('button', { name: 'Create Project' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects') &&
                response.request().method() === 'POST' &&
                response.status() === 201 &&
                // 2.5 hours = 9000 seconds
                (await response.json()).data.estimated_time === 9000
        ),
    ]);

    await expect(page.getByTestId('project_table')).toContainText(newProjectName);
});

test('test that updating estimated time on existing project works', async ({ page, ctx }) => {
    const newProjectName = 'Update Estimated Project ' + Math.floor(1 + Math.random() * 10000);
    await createProjectViaApi(ctx, { name: newProjectName });

    await goToProjectsOverview(page);
    await expect(page.getByText(newProjectName)).toBeVisible({ timeout: 10000 });

    // Edit the project to add estimated time
    await page.getByRole('row').first().getByRole('button').click();
    await page.getByRole('menuitem').getByText('Edit').first().click();

    // Fill in estimated time
    const estimatedTimeInput = page.getByPlaceholder('e.g. 2h 30m or 1.5');
    await estimatedTimeInput.fill('4h 15m');
    await estimatedTimeInput.press('Tab');

    await Promise.all([
        page.getByRole('button', { name: 'Update Project' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200 &&
                // 4h 15m = 15300 seconds
                (await response.json()).data.estimated_time === 15300
        ),
    ]);
});

test('test that estimated time input displays formatted value after blur', async ({ page }) => {
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();

    const estimatedTimeInput = page.getByPlaceholder('e.g. 2h 30m or 1.5');

    // Enter time in various formats and check the displayed value
    await estimatedTimeInput.fill('90');
    await estimatedTimeInput.press('Tab');
    // 90 hours should be displayed as "90h 00min" (default format)
    await expect(estimatedTimeInput).toHaveValue(/90h/);

    await estimatedTimeInput.fill('1:30');
    await estimatedTimeInput.press('Tab');
    // 1:30 should be displayed as "1h 30min"
    await expect(estimatedTimeInput).toHaveValue(/1h.*30/);
});

// Create new project with new Client

// Create new project with existing Client

// Delete project via More Options

// Test that project task count is displayed correctly

// Edit Project Modal Test

// Add Project with billable rate

// Edit Project with billable rate

// Edit Project Member Billable Rate

test('test that editing a task name on the project detail page works', async ({ page, ctx }) => {
    const projectName = 'Task Edit Project ' + Math.floor(1 + Math.random() * 10000);
    const originalTaskName = 'Original Task ' + Math.floor(1 + Math.random() * 10000);
    const updatedTaskName = 'Updated Task ' + Math.floor(1 + Math.random() * 10000);
    const project = await createProjectViaApi(ctx, { name: projectName });
    await createTaskViaApi(ctx, { name: originalTaskName, project_id: project.id });

    // Navigate to the project detail page
    await goToProjectsOverview(page);
    await expect(page.getByText(projectName)).toBeVisible({ timeout: 10000 });
    await page.getByText(projectName).first().click();
    await page.waitForURL(/\/projects\/[a-f0-9-]+/);

    // Verify task is visible
    await expect(page.getByTestId('task_table')).toContainText(originalTaskName);

    // Open edit modal via actions menu
    const moreButton = page.locator("[aria-label='Actions for Task " + originalTaskName + "']");
    await moreButton.click();
    await page.getByTestId('task_edit').click();

    // Update the task name
    await page.locator('#taskName').fill(updatedTaskName);
    await Promise.all([
        page.getByRole('button', { name: 'Update Task' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/tasks') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
    ]);

    // Verify updated name is shown and old name is gone
    await expect(page.getByTestId('task_table')).toContainText(updatedTaskName);
    await expect(page.getByTestId('task_table')).not.toContainText(originalTaskName);
});

// =============================================
// Employee Permission Tests
// =============================================

test.describe('Employee Projects Restrictions', () => {
    test('employee can view public projects but cannot create', async ({ ctx, employee }) => {
        const projectName = 'EmpViewProj ' + Math.floor(Math.random() * 10000);
        await createPublicProjectViaApi(ctx, { name: projectName });

        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/projects');
        await expect(employee.page.getByTestId('projects_view')).toBeVisible({
            timeout: 10000,
        });

        // Employee can see the public project
        await expect(employee.page.getByText(projectName)).toBeVisible({ timeout: 10000 });

        // Employee cannot see Create Project button
        await expect(
            employee.page.getByRole('button', { name: 'Create Project' })
        ).not.toBeVisible();
    });

    test('employee cannot see edit/delete/archive actions on projects', async ({
        ctx,
        employee,
    }) => {
        const projectName = 'EmpActionsProj ' + Math.floor(Math.random() * 10000);
        await createPublicProjectViaApi(ctx, { name: projectName });

        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/projects');
        await expect(employee.page.getByText(projectName)).toBeVisible({ timeout: 10000 });

        // Click the actions dropdown trigger to open the menu
        const actionsButton = employee.page.locator(
            `[aria-label='Actions for Project ${projectName}']`
        );
        await actionsButton.click();

        // The dropdown menu items (Edit, Archive, Delete) should NOT be visible
        await expect(
            employee.page.locator(`[aria-label='Edit Project ${projectName}']`)
        ).not.toBeVisible();
        await expect(
            employee.page.locator(`[aria-label='Archive Project ${projectName}']`)
        ).not.toBeVisible();
        await expect(
            employee.page.locator(`[aria-label='Delete Project ${projectName}']`)
        ).not.toBeVisible();
    });
});

test.describe('Employee Billable Rate Visibility', () => {
    test('employee cannot see billable rate column by default', async ({ ctx, employee }) => {
        const projectName = 'EmpBillableProj ' + Math.floor(Math.random() * 10000);
        await createPublicProjectViaApi(ctx, {
            name: projectName,
            is_billable: true,
            billable_rate: 15000,
        });

        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/projects');
        await expect(employee.page.getByText(projectName)).toBeVisible({ timeout: 10000 });

        // Billable Rate column should not be visible to employee by default
        await expect(employee.page.getByText('Billable Rate')).not.toBeVisible();
    });

    test('employee can see billable rate column when employees_can_see_billable_rates is enabled', async ({
        ctx,
        employee,
    }) => {
        await updateOrganizationSettingViaApi(ctx, { employees_can_see_billable_rates: true });

        const projectName = 'EmpBillableVisProj ' + Math.floor(Math.random() * 10000);
        await createPublicProjectViaApi(ctx, {
            name: projectName,
            is_billable: true,
            billable_rate: 20000,
        });

        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/projects');
        await expect(employee.page.getByText(projectName)).toBeVisible({ timeout: 10000 });

        // Billable Rate column header should be visible
        await expect(employee.page.getByText('Billable Rate')).toBeVisible();

        // The project row should show the formatted billable rate
        const projectRow = employee.page.getByRole('row').filter({ hasText: projectName });
        await expect(projectRow).toContainText('200');
    });
});
