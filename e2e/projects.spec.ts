import { expect, Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import { formatCents, getOrganizationCurrencySymbol } from '../resources/js/packages/ui/src/utils/money';
import type { CurrencyFormat } from '../resources/js/packages/ui/src/utils/money';

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

test('test that archiving and unarchiving projects works', async ({ page }) => {
    const newProjectName =
        'New Project ' + Math.floor(1 + Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(newProjectName);

    await page.getByRole('button', { name: 'Create Project' }).click();
    await expect(page.getByText(newProjectName)).toBeVisible();

    await page.getByRole('row').first().getByRole('button').click();
    await Promise.all([
        page.getByRole('menuitem').getByText('Archive').first().click(),
        expect(page.getByText(newProjectName)).not.toBeVisible(),
    ]);
    await Promise.all([
        page.getByRole('tab', { name: 'Archived' }).click(),
        expect(page.getByText(newProjectName)).toBeVisible(),
    ]);

    await page.getByRole('row').first().getByRole('button').click();
    await Promise.all([
        page.getByRole('menuitem').getByText('Unarchive').first().click(),
        expect(page.getByText(newProjectName)).not.toBeVisible(),
    ]);
    await Promise.all([
        page.getByRole('tab', { name: 'Active' }).click(),
        expect(page.getByText(newProjectName)).toBeVisible(),
    ]);
});

test('test that updating billable rate works with existing time entries', async ({
    page,
}) => {
    const newProjectName =
        'New Project ' + Math.floor(1 + Math.random() * 10000);
    const newBillableRate = Math.round(Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(newProjectName);

    await page.getByRole('button', { name: 'Create Project' }).click();
    await expect(page.getByText(newProjectName)).toBeVisible();

    await page.getByRole('row').first().getByRole('button').click();
    await page.getByRole('menuitem').getByText('Edit').first().click();
        await page.getByText('Non-Billable').click();
    await page.getByText('Custom Rate').click();
    await page
        .getByPlaceholder('Billable Rate')
        .fill(newBillableRate.toString());
    await page.getByRole('button', { name: 'Update Project' }).click();

    await Promise.all([
        page
            .getByRole('button', { name: 'Yes, update existing time entries' })
            .click(),
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
                (await response.json()).data.billable_rate ===
                    newBillableRate * 100
        ),
    ]);
    await expect(
        page
            .getByRole('row')
            .first()
            .getByText(formatCents(
                newBillableRate * 100,
                'EUR',
                'symbol-before' as CurrencyFormat,
                '€',
                'space-point'
            ))
    ).toBeVisible();
});

// Create new project with new Client

// Create new project with existing Client

// Delete project via More Options

// Test that project task count is displayed correctly

// Test that active / archive / all filter works (once implemented)

// Edit Project Modal Test

// Add Project with billable rate

// Edit Project with billable rate

// Edit Project Member Billable Rate

// Edit Task Name
