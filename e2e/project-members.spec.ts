import { expect, Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import { formatCents } from '../resources/js/utils/money';

async function goToProjectsOverview(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/projects');
}

test('test that updating project member billable rate works', async ({
    page,
}) => {
    const newProjectName =
        'New Project ' + Math.floor(1 + Math.random() * 10000);
    const newBillableRate = Math.round(Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(newProjectName);

    await page.getByRole('button', { name: 'Create Project' }).nth(1).click();
    await expect(page.getByText(newProjectName)).toBeVisible();

    await page.getByText(newProjectName).click();
    await page.getByRole('button', { name: 'Add Member' }).click();

    await expect(page.getByText('Add Project Member').first()).toBeVisible();
    await page.keyboard.press('Enter');
    await page.getByRole('button', { name: 'Add Project Member' }).click();

    await page
        .getByTestId('project_member_table')
        .getByRole('row')
        .first()
        .getByRole('button')
        .click();
    await page.getByRole('button', { name: 'Edit' }).first().click();
    await page.getByLabel('Billable Rate').fill(newBillableRate.toString());
    await page.getByRole('button', { name: 'Update Project Member' }).click();

    await Promise.all([
        page
            .getByRole('button', { name: 'No, only for new time entries' })
            .click(),
        page.waitForRequest(
            async (request) =>
                request.url().includes('/project-members/') &&
                request.method() === 'PUT' &&
                request.postDataJSON().billable_rate ===
                    newBillableRate * 100 &&
                request.postDataJSON().billable_rate_update_time_entries ===
                    false
        ),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/project-members/') &&
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
            .getByText(formatCents(newBillableRate * 100))
    ).toBeVisible();
});

test('test that updating project member billable rate works for existing time entries', async ({
    page,
}) => {
    const newProjectName =
        'New Project ' + Math.floor(1 + Math.random() * 10000);
    const newBillableRate = Math.round(Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(newProjectName);

    await page.getByRole('button', { name: 'Create Project' }).nth(1).click();
    await expect(page.getByText(newProjectName)).toBeVisible();

    await page.getByText(newProjectName).click();
    await page.getByRole('button', { name: 'Add Member' }).click();

    await expect(page.getByText('Add Project Member').first()).toBeVisible();
    await page.keyboard.press('Enter');
    await page.getByRole('button', { name: 'Add Project Member' }).click();

    await page
        .getByTestId('project_member_table')
        .getByRole('row')
        .first()
        .getByRole('button')
        .click();
    await page.getByRole('button', { name: 'Edit' }).first().click();
    await page.getByLabel('Billable Rate').fill(newBillableRate.toString());
    await page.getByRole('button', { name: 'Update Project Member' }).click();

    await Promise.all([
        page.getByRole('button', { name: 'Yes, update existing time' }).click(),
        page.waitForRequest(
            async (request) =>
                request.url().includes('/project-members/') &&
                request.method() === 'PUT' &&
                request.postDataJSON().billable_rate ===
                    newBillableRate * 100 &&
                request.postDataJSON().billable_rate_update_time_entries ===
                    true
        ),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/project-members/') &&
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
            .getByText(formatCents(newBillableRate * 100))
    ).toBeVisible();
});
