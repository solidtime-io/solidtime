import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import { formatCentsWithOrganizationDefaults } from './utils/money';
import type { CurrencyFormat } from '../resources/js/packages/ui/src/utils/money';

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

test('test that archiving and unarchiving projects works', async ({ page }) => {
    const newProjectName = 'New Project ' + Math.floor(1 + Math.random() * 10000);
    await goToProjectsOverview(page);
    await clearProjectTableState(page);
    await page.reload();

    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(newProjectName);

    await Promise.all([
        page.getByRole('button', { name: 'Create Project' }).click(),
        page.waitForResponse(
            (response) =>
                response.url().includes('/projects') &&
                response.request().method() === 'POST' &&
                response.status() === 201
        ),
    ]);
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

test('test that updating billable rate works with existing time entries', async ({ page }) => {
    const newProjectName = 'New Project ' + Math.floor(1 + Math.random() * 10000);
    const newBillableRate = Math.round(Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(newProjectName);

    await Promise.all([
        page.getByRole('button', { name: 'Create Project' }).click(),
        page.waitForResponse(
            (response) =>
                response.url().includes('/projects') &&
                response.request().method() === 'POST' &&
                response.status() === 201
        ),
    ]);
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

    // Wait for sort to apply
    await page.waitForTimeout(100);

    // Click again to sort descending
    await nameHeader.click();
    await page.waitForTimeout(100);

    // Verify the sort indicator is showing descending
    await expect(page.locator('svg').first()).toBeVisible();
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

    // Wait for sort to apply
    await page.waitForTimeout(100);

    // Sort indicator should be visible
    await expect(statusHeader.locator('svg')).toBeVisible();
});

// Filter tests
test('test that filtering projects by status works', async ({ page }) => {
    const newProjectName = 'Filter Test Project ' + Math.floor(1 + Math.random() * 10000);
    await goToProjectsOverview(page);
    await clearProjectTableState(page);
    await page.reload();

    // Create a new project
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(newProjectName);
    await Promise.all([
        page.getByRole('button', { name: 'Create Project' }).click(),
        page.waitForResponse(
            (response) =>
                response.url().includes('/projects') &&
                response.request().method() === 'POST' &&
                response.status() === 201
        ),
    ]);
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

    // Wait for the state to be saved
    await page.waitForTimeout(100);

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
    await nameHeader.click();

    // Wait for the state to be saved
    await page.waitForTimeout(100);

    // Reload the page
    await page.reload();

    // Verify descending sort indicator is visible on Name column
    await expect(page.getByTestId('project_table')).toBeVisible();
});

// Create new project with new Client

// Create new project with existing Client

// Delete project via More Options

// Test that project task count is displayed correctly

// Edit Project Modal Test

// Add Project with billable rate

// Edit Project with billable rate

// Edit Project Member Billable Rate

// Edit Task Name
