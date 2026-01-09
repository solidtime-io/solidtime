import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import { formatCentsWithOrganizationDefaults } from './utils/money';

async function goToProjectsOverview(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/projects');
}

test('test that updating project member billable rate works for existing time entries', async ({
    page,
}) => {
    const newProjectName = 'New Project ' + Math.floor(1 + Math.random() * 10000);
    const newBillableRate = Math.round(Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(newProjectName);

    await page.getByRole('button', { name: 'Create Project' }).click();
    await expect(page.getByText(newProjectName)).toBeVisible();

    await page.getByText(newProjectName).click();
    await page.getByRole('button', { name: 'Add Member' }).click();

    await expect(page.getByText('Add Project Member').first()).toBeVisible();
    await page.getByRole('button', { name: 'Select a member' }).click();
    await page.keyboard.press('Enter');
    await page.getByRole('button', { name: 'Add Project Member' }).click();

    await page
        .getByTestId('project_member_table')
        .getByRole('row')
        .first()
        .getByRole('button')
        .click();
    await page.getByRole('menuitem', { name: 'Edit Project Member' }).click();
    await page.getByLabel('Billable Rate').fill(newBillableRate.toString());
    await page.getByRole('button', { name: 'Update Project Member' }).click();

    await Promise.all([
        page.getByRole('button', { name: 'Yes, update existing time' }).click(),
        page.waitForRequest(
            async (request) =>
                request.url().includes('/project-members/') &&
                request.method() === 'PUT' &&
                request.postDataJSON().billable_rate === newBillableRate * 100
        ),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/project-members/') &&
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
