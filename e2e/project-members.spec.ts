import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import { formatCentsWithOrganizationDefaults } from './utils/money';
import { createProjectViaApi, createProjectMemberViaApi, type TestContext } from './utils/api';

async function createProjectWithMemberViaApi(ctx: TestContext, page: Page, projectName: string) {
    const project = await createProjectViaApi(ctx, { name: projectName });
    await createProjectMemberViaApi(ctx, project.id, { member_id: ctx.memberId });

    // Navigate to the project detail page
    await page.goto(PLAYWRIGHT_BASE_URL + '/projects/' + project.id);
    await expect(page.getByTestId('project_member_table').getByRole('row').first()).toBeVisible();
    return project;
}

test('test that updating project member billable rate works for existing time entries', async ({
    page,
    ctx,
}) => {
    const newProjectName = 'New Project ' + Math.floor(1 + Math.random() * 10000);
    const newBillableRate = Math.round(Math.random() * 10000);
    await createProjectWithMemberViaApi(ctx, page, newProjectName);

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

test('test that project member edit modal can be cancelled without sending API request', async ({
    page,
    ctx,
}) => {
    const projectName = 'Cancel Test ' + Math.floor(1 + Math.random() * 10000);

    await createProjectWithMemberViaApi(ctx, page, projectName);

    // Open the edit modal
    await page
        .getByTestId('project_member_table')
        .getByRole('row')
        .first()
        .getByRole('button')
        .click();
    await page.getByRole('menuitem', { name: 'Edit Project Member' }).click();

    // Verify the modal is open and shows the member name
    await expect(page.getByRole('heading', { name: 'Edit Project Member' })).toBeVisible();
    await expect(page.getByRole('dialog').getByText('John Doe')).toBeVisible();

    // Enter a new billable rate
    await page.getByLabel('Billable Rate').fill('999');

    // Set up listener to verify no PUT request is sent
    let putRequestSent = false;
    page.on('request', (request) => {
        if (request.url().includes('/project-members/') && request.method() === 'PUT') {
            putRequestSent = true;
        }
    });

    // Click Cancel
    await page.getByRole('button', { name: 'Cancel' }).click();

    // Verify the modal is closed
    await expect(page.getByRole('heading', { name: 'Edit Project Member' })).not.toBeVisible();

    // Verify no PUT request was sent
    expect(putRequestSent).toBe(false);
});

test('test that project member update without billable rate change skips confirmation and completes', async ({
    page,
    ctx,
}) => {
    const projectName = 'No Change ' + Math.floor(1 + Math.random() * 10000);

    await createProjectWithMemberViaApi(ctx, page, projectName);

    // Open the edit modal
    await page
        .getByTestId('project_member_table')
        .getByRole('row')
        .first()
        .getByRole('button')
        .click();
    await page.getByRole('menuitem', { name: 'Edit Project Member' }).click();

    // Click Update without changing anything - no confirmation modal since rate didn't change
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/project-members/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page.getByRole('button', { name: 'Update Project Member' }).click(),
    ]);

    // Verify the edit modal is closed (confirmation modal was skipped)
    await expect(page.getByRole('heading', { name: 'Edit Project Member' })).not.toBeVisible();
});

test('test that billable rate confirmation modal can be cancelled without sending API request', async ({
    page,
    ctx,
}) => {
    const projectName = 'Rate Cancel ' + Math.floor(1 + Math.random() * 10000);
    const newBillableRate = Math.round(Math.random() * 10000);

    await createProjectWithMemberViaApi(ctx, page, projectName);

    // Open the edit modal
    await page
        .getByTestId('project_member_table')
        .getByRole('row')
        .first()
        .getByRole('button')
        .click();
    await page.getByRole('menuitem', { name: 'Edit Project Member' }).click();

    // Change the billable rate
    await page.getByLabel('Billable Rate').fill(newBillableRate.toString());

    // Set up listener to verify no PUT request is sent
    let putRequestSent = false;
    page.on('request', (request) => {
        if (request.url().includes('/project-members/') && request.method() === 'PUT') {
            putRequestSent = true;
        }
    });

    // Click Update - this should show the confirmation modal
    await page.getByRole('button', { name: 'Update Project Member' }).click();

    // Verify the confirmation modal is shown
    await expect(page.getByText('update all existing time entries')).toBeVisible();

    // Click Cancel to close the confirmation modal without updating
    await page.getByRole('button', { name: 'Cancel' }).click();

    // Verify the confirmation modal is closed but edit modal is still open
    await expect(page.getByText('update all existing time entries')).not.toBeVisible();
    await expect(page.getByRole('heading', { name: 'Edit Project Member' })).toBeVisible();

    // Close the edit modal
    await page.getByRole('dialog').getByRole('button', { name: 'Cancel' }).click();

    // Verify the edit modal is closed
    await expect(page.getByRole('heading', { name: 'Edit Project Member' })).not.toBeVisible();

    // Verify no PUT request was sent
    expect(putRequestSent).toBe(false);
});

test('test that clearing billable rate reverts to project default', async ({ page, ctx }) => {
    const projectName = 'Revert Default ' + Math.floor(1 + Math.random() * 10000);
    const customRate = Math.round(100 + Math.random() * 10000);

    await createProjectWithMemberViaApi(ctx, page, projectName);

    // Verify the billable rate shows "--" (project default) initially
    await expect(
        page.getByTestId('project_member_table').getByRole('row').first().getByText('--')
    ).toBeVisible();

    // Set a custom billable rate
    await page
        .getByTestId('project_member_table')
        .getByRole('row')
        .first()
        .getByRole('button')
        .click();
    await page.getByRole('menuitem', { name: 'Edit Project Member' }).click();
    await page.getByLabel('Billable Rate').fill(customRate.toString());
    await page.getByRole('button', { name: 'Update Project Member' }).click();

    // Confirm the billable rate update
    await Promise.all([
        page.getByRole('button', { name: 'Yes, update existing time' }).click(),
        page.waitForResponse(
            (response) =>
                response.url().includes('/project-members/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
    ]);

    // Verify the custom rate is shown in the table (not "--")
    await expect(
        page.getByTestId('project_member_table').getByRole('row').first().getByText('--')
    ).not.toBeVisible();

    // Now clear the billable rate to revert to project default
    await page
        .getByTestId('project_member_table')
        .getByRole('row')
        .first()
        .getByRole('button')
        .click();
    await page.getByRole('menuitem', { name: 'Edit Project Member' }).click();

    // Set billable rate to 0 to revert to project default
    await page.getByLabel('Billable Rate').fill('0');
    await page.getByRole('button', { name: 'Update Project Member' }).click();

    // Confirm the billable rate update
    await Promise.all([
        page.getByRole('button', { name: 'Yes, update existing time' }).click(),
        page.waitForResponse(
            (response) =>
                response.url().includes('/project-members/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
    ]);

    // Verify the billable rate shows "--" again (project default)
    await expect(
        page.getByTestId('project_member_table').getByRole('row').first().getByText('--')
    ).toBeVisible();
});
