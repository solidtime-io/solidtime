import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../../playwright/config';

// ──────────────────────────────────────────────────
// Navigation
// ──────────────────────────────────────────────────

export async function goToReporting(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/reporting');
}

export async function goToReportingDetailed(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/reporting/detailed');
}

// ──────────────────────────────────────────────────
// Entity creation
// ──────────────────────────────────────────────────

export async function createProject(page: Page, projectName: string) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/projects');
    await expect(page.getByRole('button', { name: 'Create Project' })).toBeVisible();
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project name').fill(projectName);
    await Promise.all([
        page.getByRole('dialog').getByRole('button', { name: 'Create Project' }).click(),
        page.waitForResponse(
            (response) =>
                response.url().includes('/projects') &&
                response.request().method() === 'POST' &&
                response.status() === 201
        ),
    ]);
    await expect(page.getByText(projectName)).toBeVisible();
}

export async function createClient(page: Page, clientName: string) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/clients');
    await expect(page.getByRole('button', { name: 'Create Client' })).toBeVisible();
    await page.getByRole('button', { name: 'Create Client' }).click();
    await page.getByPlaceholder('Client Name').fill(clientName);
    await Promise.all([
        page.getByRole('button', { name: 'Create Client' }).click(),
        page.waitForResponse(
            (response) =>
                response.url().includes('/clients') &&
                response.request().method() === 'POST' &&
                response.status() === 201
        ),
    ]);
    await expect(page.getByText(clientName)).toBeVisible();
}

export async function createProjectWithClient(page: Page, projectName: string, clientName: string) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/projects');
    await expect(page.getByRole('button', { name: 'Create Project' })).toBeVisible();
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project name').fill(projectName);

    // Select client in the project create modal
    await page.getByRole('dialog').getByRole('button', { name: 'No Client' }).click();
    await page.getByRole('option', { name: clientName }).click();

    await Promise.all([
        page.getByRole('dialog').getByRole('button', { name: 'Create Project' }).click(),
        page.waitForResponse(
            (response) =>
                response.url().includes('/projects') &&
                response.request().method() === 'POST' &&
                response.status() === 201
        ),
    ]);
    await expect(page.getByText(projectName)).toBeVisible();
}

export async function createTask(page: Page, projectName: string, taskName: string) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/projects');
    await expect(page.getByText(projectName)).toBeVisible();
    await page.getByText(projectName).click();
    await page.getByRole('button', { name: 'Create Task' }).click();
    await page.getByPlaceholder('Task Name').fill(taskName);
    await Promise.all([
        page.getByRole('button', { name: 'Create Task' }).click(),
        page.waitForResponse(
            (response) =>
                response.url().includes('/tasks') &&
                response.request().method() === 'POST' &&
                response.status() === 201
        ),
    ]);
    await expect(page.getByText(taskName)).toBeVisible();
}

// ──────────────────────────────────────────────────
// Time entry creation
// ──────────────────────────────────────────────────

export async function createTimeEntryWithProject(
    page: Page,
    projectName: string,
    duration: string
) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/time');
    await expect(page.getByRole('button', { name: 'Time entry actions' })).toBeVisible();
    await page.getByRole('button', { name: 'Time entry actions' }).click();
    await page.getByRole('menuitem', { name: 'Manual time entry' }).click();

    await page
        .getByRole('dialog')
        .getByRole('textbox', { name: 'Description' })
        .fill(`Entry for ${projectName}`);

    await page.getByRole('button', { name: 'No Project' }).click();
    await page.getByRole('option').filter({ hasText: projectName }).click();

    await page.locator('[role="dialog"] input[name="Duration"]').fill(duration);
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');

    await Promise.all([
        page.getByRole('button', { name: 'Create Time Entry' }).click(),
        page.waitForResponse(
            (response) => response.url().includes('/time-entries') && response.status() === 201
        ),
    ]);
}

export async function createTimeEntryWithProjectAndTask(
    page: Page,
    projectName: string,
    taskName: string,
    duration: string
) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/time');
    await expect(page.getByRole('button', { name: 'Time entry actions' })).toBeVisible();
    await page.getByRole('button', { name: 'Time entry actions' }).click();
    await page.getByRole('menuitem', { name: 'Manual time entry' }).click();

    await page
        .getByRole('dialog')
        .getByRole('textbox', { name: 'Description' })
        .fill(`Entry for ${projectName} - ${taskName}`);

    // Open the project/task dropdown
    await page.getByRole('button', { name: 'No Project' }).click();

    // Expand the project's tasks by clicking the "Tasks" button
    const projectOption = page.getByRole('option').filter({ hasText: projectName });
    await projectOption.getByText(/Tasks/).click();

    // Select the task (this also selects the project and closes the dropdown)
    await page.getByText(taskName, { exact: true }).click();

    await page.locator('[role="dialog"] input[name="Duration"]').fill(duration);
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');

    await Promise.all([
        page.getByRole('button', { name: 'Create Time Entry' }).click(),
        page.waitForResponse(
            (response) => response.url().includes('/time-entries') && response.status() === 201
        ),
    ]);
}

export async function createTimeEntryWithTag(page: Page, tagName: string, duration: string) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/time');
    await expect(page.getByRole('button', { name: 'Time entry actions' })).toBeVisible();
    await page.getByRole('button', { name: 'Time entry actions' }).click();
    await page.getByRole('menuitem', { name: 'Manual time entry' }).click();

    await page
        .getByRole('dialog')
        .getByRole('textbox', { name: 'Description' })
        .fill(`Entry with tag ${tagName}`);

    // Add tag
    await page.getByRole('button', { name: 'Tags' }).click();
    await page.getByText('Create new tag').click();
    await page.getByPlaceholder('Tag Name').fill(tagName);
    await Promise.all([
        page.getByRole('button', { name: 'Create Tag' }).click(),
        page.waitForResponse(
            (response) => response.url().includes('/tags') && response.status() === 201
        ),
    ]);

    await page.locator('[role="dialog"] input[name="Duration"]').fill(duration);
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');

    await Promise.all([
        page.getByRole('button', { name: 'Create Time Entry' }).click(),
        page.waitForResponse(
            (response) => response.url().includes('/time-entries') && response.status() === 201
        ),
    ]);
}

export async function createBareTimeEntry(page: Page, description: string, duration: string) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/time');
    await expect(page.getByRole('button', { name: 'Time entry actions' })).toBeVisible();
    await page.getByRole('button', { name: 'Time entry actions' }).click();
    await page.getByRole('menuitem', { name: 'Manual time entry' }).click();

    await page.getByRole('dialog').getByRole('textbox', { name: 'Description' }).fill(description);

    await page.locator('[role="dialog"] input[name="Duration"]').fill(duration);
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');

    await Promise.all([
        page.getByRole('button', { name: 'Create Time Entry' }).click(),
        page.waitForResponse(
            (response) => response.url().includes('/time-entries') && response.status() === 201
        ),
    ]);
}

export async function createTimeEntryWithBillableStatus(
    page: Page,
    isBillable: boolean,
    duration: string
) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/time');
    await expect(page.getByRole('button', { name: 'Time entry actions' })).toBeVisible();
    await page.getByRole('button', { name: 'Time entry actions' }).click();
    await page.getByRole('menuitem', { name: 'Manual time entry' }).click();

    await page
        .getByRole('dialog')
        .getByRole('textbox', { name: 'Description' })
        .fill(`Time entry ${isBillable ? 'billable' : 'non-billable'}`);

    if (isBillable) {
        await page
            .getByRole('dialog')
            .getByRole('combobox')
            .filter({ hasText: 'Non-Billable' })
            .click();
        await page.getByRole('option', { name: 'Billable', exact: true }).click();
    }

    await page.locator('[role="dialog"] input[name="Duration"]').fill(duration);
    await page.locator('[role="dialog"] input[name="Duration"]').press('Tab');

    await Promise.all([
        page.getByRole('button', { name: 'Create Time Entry' }).click(),
        page.waitForResponse(
            (response) => response.url().includes('/time-entries') && response.status() === 201
        ),
    ]);
}

// ──────────────────────────────────────────────────
// Wait helpers
// ──────────────────────────────────────────────────

export async function waitForReportingUpdate(page: Page) {
    await page.waitForResponse(
        (response) =>
            response.url().includes('/time-entries/aggregate') && response.status() === 200
    );
}

export async function waitForDetailedReportingUpdate(page: Page) {
    await page.waitForResponse(
        (response) =>
            response.url().includes('/time-entries') &&
            !response.url().includes('/aggregate') &&
            response.request().method() === 'GET' &&
            response.status() === 200
    );
}

// ──────────────────────────────────────────────────
// Shared report helpers
// ──────────────────────────────────────────────────

export async function goToReportingShared(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/reporting/shared');
}

export async function saveAsSharedReport(
    page: Page,
    reportName: string
): Promise<{ shareableLink: string }> {
    await page.getByRole('button', { name: 'Save Report' }).click();
    await page.getByLabel('Name').fill(reportName);
    // "Public" checkbox is checked by default
    const [response] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/reports') &&
                response.request().method() === 'POST' &&
                response.status() === 201
        ),
        page.getByRole('dialog').getByRole('button', { name: 'Create Report' }).click(),
    ]);
    const responseBody = await response.json();
    return { shareableLink: responseBody.data.shareable_link };
}
