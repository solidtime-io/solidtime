import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import {
    createBillableProjectViaApi,
    createProjectViaApi,
    createBareTimeEntryViaApi,
    createTimeEntryViaApi,
    createRunningTimeEntryViaApi,
} from './utils/api';

async function goToCalendar(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/calendar');
}

async function openContextMenu(page: Page, description: string) {
    const event = page.locator('.fc-event').filter({ hasText: description }).first();
    await expect(event).toBeVisible();
    await event.click({ button: 'right' });
    await expect(page.getByRole('menu')).toBeVisible();
}

/**
 * These tests verify that changing the project on a time entry via the calendar
 * updates the billable status to match the new project's is_billable setting.
 *
 * Issue: https://github.com/solidtime-io/solidtime/issues/981
 */

test('test that changing project in calendar edit modal from non-billable to billable updates billable status', async ({
    page,
    ctx,
}) => {
    const billableProjectName = 'Billable Cal Project ' + Math.floor(1 + Math.random() * 10000);

    await createBillableProjectViaApi(ctx, { name: billableProjectName });
    await createBareTimeEntryViaApi(ctx, 'Test billable calendar', '1h');

    await goToCalendar(page);

    // Click on the time entry event in the calendar
    await page.locator('.fc-event').filter({ hasText: 'Test billable calendar' }).first().click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // Verify initially non-billable
    await expect(
        page.getByRole('dialog').getByRole('combobox').filter({ hasText: 'Non-Billable' })
    ).toBeVisible();

    // Select the billable project
    await page.getByRole('dialog').getByRole('button', { name: 'No Project' }).click();
    await page.getByRole('option', { name: billableProjectName }).click();

    // Verify the billable dropdown updated to Billable
    await expect(
        page.getByRole('dialog').getByRole('combobox').filter({ hasText: 'Billable' })
    ).toBeVisible();

    // Save and verify
    const [updateResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page.getByRole('button', { name: 'Update Time Entry' }).click(),
    ]);
    const responseBody = await updateResponse.json();
    expect(responseBody.data.billable).toBe(true);
});

test('test that changing project in calendar edit modal from billable to non-billable updates billable status', async ({
    page,
    ctx,
}) => {
    const billableProjectName = 'Billable Cal Rev Project ' + Math.floor(1 + Math.random() * 10000);
    const nonBillableProjectName =
        'NonBillable Cal Rev Project ' + Math.floor(1 + Math.random() * 10000);

    await createBillableProjectViaApi(ctx, { name: billableProjectName });
    await createProjectViaApi(ctx, { name: nonBillableProjectName });
    await createBareTimeEntryViaApi(ctx, 'Test billable cal reverse', '1h');

    await goToCalendar(page);

    // Click on the time entry event in the calendar
    await page
        .locator('.fc-event')
        .filter({ hasText: 'Test billable cal reverse' })
        .first()
        .click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // First assign the billable project
    await page.getByRole('dialog').getByRole('button', { name: 'No Project' }).click();
    await page.getByRole('option', { name: billableProjectName }).click();

    // Verify billable status flipped to Billable
    await expect(
        page.getByRole('dialog').getByRole('combobox').filter({ hasText: 'Billable' })
    ).toBeVisible();

    // Now switch to the non-billable project
    await page.getByRole('dialog').getByRole('button', { name: billableProjectName }).click();
    await page.getByRole('option', { name: nonBillableProjectName }).click();

    // Verify billable status reverted to Non-Billable
    await expect(
        page.getByRole('dialog').getByRole('combobox').filter({ hasText: 'Non-Billable' })
    ).toBeVisible();

    // Save and verify
    const [updateResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page.getByRole('button', { name: 'Update Time Entry' }).click(),
    ]);
    const responseBody = await updateResponse.json();
    expect(responseBody.data.billable).toBe(false);
});

test('test that opening calendar edit modal for a time entry with manually overridden billable status preserves that status', async ({
    page,
    ctx,
}) => {
    const billableProjectName =
        'Billable Cal Persist Project ' + Math.floor(1 + Math.random() * 10000);

    await createBillableProjectViaApi(ctx, { name: billableProjectName });
    await createBareTimeEntryViaApi(ctx, 'Test cal persist override', '1h');

    await goToCalendar(page);

    // Click on the time entry event in the calendar
    await page
        .locator('.fc-event')
        .filter({ hasText: 'Test cal persist override' })
        .first()
        .click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // Assign the billable project
    await page.getByRole('dialog').getByRole('button', { name: 'No Project' }).click();
    await page.getByRole('option', { name: billableProjectName }).click();

    // Verify it auto-set to Billable
    await expect(
        page.getByRole('dialog').getByRole('combobox').filter({ hasText: 'Billable' })
    ).toBeVisible();

    // Now manually override billable to Non-Billable via the dropdown
    await page.getByRole('dialog').getByRole('combobox').filter({ hasText: 'Billable' }).click();
    await page.getByRole('option', { name: 'Non Billable' }).click();

    // Verify it shows Non-Billable now
    await expect(
        page.getByRole('dialog').getByRole('combobox').filter({ hasText: 'Non-Billable' })
    ).toBeVisible();

    // Save
    const [firstSaveResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page.getByRole('button', { name: 'Update Time Entry' }).click(),
    ]);
    const firstBody = await firstSaveResponse.json();
    expect(firstBody.data.billable).toBe(false);

    // Re-open the edit modal from the calendar — the project_id watcher should NOT override billable
    await page
        .locator('.fc-event')
        .filter({ hasText: 'Test cal persist override' })
        .first()
        .click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // The billable dropdown should still show Non-Billable
    await expect(
        page.getByRole('dialog').getByRole('combobox').filter({ hasText: 'Non-Billable' })
    ).toBeVisible();

    // Save without changes and verify the response still has billable=false
    const [updateResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page.getByRole('button', { name: 'Update Time Entry' }).click(),
    ]);
    const responseBody = await updateResponse.json();
    expect(responseBody.data.billable).toBe(false);
});

test('test that calendar page loads and displays time entries', async ({ page, ctx }) => {
    await createBareTimeEntryViaApi(ctx, 'Calendar display test', '1h');

    await goToCalendar(page);

    // Calendar container should be visible
    await expect(page.locator('.fc')).toBeVisible();

    // The time entry should appear as a calendar event
    await expect(
        page.locator('.fc-event').filter({ hasText: 'Calendar display test' }).first()
    ).toBeVisible();
});

test('test that calendar navigation buttons work', async ({ page }) => {
    await goToCalendar(page);
    await expect(page.locator('.fc')).toBeVisible();

    // Click the "next" button to navigate forward
    await page.locator('button.fc-next-button').click();
    await expect(page.locator('.fc')).toBeVisible();

    // Click the "prev" button to navigate back
    await page.locator('button.fc-prev-button').click();
    await expect(page.locator('.fc')).toBeVisible();

    // Navigate forward first so "today" button becomes enabled, then click it
    await page.locator('button.fc-next-button').click();
    await page.locator('button.fc-today-button').click();
    await expect(page.locator('.fc')).toBeVisible();
});

test('test that editing time entry description via calendar modal works', async ({ page, ctx }) => {
    const originalDescription = 'Edit me in calendar ' + Math.floor(1 + Math.random() * 10000);
    const updatedDescription = 'Updated in calendar ' + Math.floor(1 + Math.random() * 10000);
    await createBareTimeEntryViaApi(ctx, originalDescription, '1h');

    await goToCalendar(page);

    // Click on the time entry event
    await page.locator('.fc-event').filter({ hasText: originalDescription }).first().click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // Update the description (edit modal uses placeholder, not data-testid)
    const descriptionInput = page.getByRole('dialog').getByPlaceholder('What did you work on?');
    await descriptionInput.fill(updatedDescription);

    // Save and verify
    const [editResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page.getByRole('button', { name: 'Update Time Entry' }).click(),
    ]);
    const editBody = await editResponse.json();
    expect(editBody.data.description).toBe(updatedDescription);

    // Verify the updated description is shown in the calendar UI
    await expect(
        page.locator('.fc-event').filter({ hasText: updatedDescription }).first()
    ).toBeVisible();
    // Verify the old description is no longer shown
    await expect(
        page.locator('.fc-event').filter({ hasText: originalDescription })
    ).not.toBeVisible();
});

test('test that deleting time entry from calendar modal works', async ({ page, ctx }) => {
    const description = 'Delete me from calendar ' + Math.floor(1 + Math.random() * 10000);
    await createBareTimeEntryViaApi(ctx, description, '1h');

    await goToCalendar(page);

    // Click on the time entry event
    await page.locator('.fc-event').filter({ hasText: description }).first().click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // Click the delete button
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/') &&
                response.request().method() === 'DELETE' &&
                response.status() === 204
        ),
        page.getByRole('dialog').getByRole('button', { name: 'Delete' }).click(),
    ]);

    // Verify the event is removed from the calendar
    await expect(page.locator('.fc-event').filter({ hasText: description })).not.toBeVisible();
});

// =============================================
// Context Menu Tests
// =============================================

test('test that context menu edit opens the edit modal', async ({ page, ctx }) => {
    const description = 'Context edit test ' + Math.floor(1 + Math.random() * 10000);
    await createBareTimeEntryViaApi(ctx, description, '1h');

    await goToCalendar(page);
    await openContextMenu(page, description);

    await page.getByRole('menuitem', { name: 'Edit' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();
    await expect(page.getByRole('dialog').getByPlaceholder('What did you work on?')).toHaveValue(
        description
    );
});

test('test that context menu duplicate preserves project and billable status', async ({
    page,
    ctx,
}) => {
    const description = 'Context dup test ' + Math.floor(1 + Math.random() * 10000);
    const project = await createProjectViaApi(ctx, {
        name: 'Dup Project ' + Math.floor(1 + Math.random() * 10000),
        is_billable: true,
    });
    await createTimeEntryViaApi(ctx, {
        description,
        duration: '1h',
        projectId: project.id,
        billable: true,
    });

    await goToCalendar(page);
    await expect(page.locator('.fc-event').filter({ hasText: description })).toHaveCount(1);
    await openContextMenu(page, description);

    const [createResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries') &&
                response.request().method() === 'POST' &&
                response.status() === 201
        ),
        page.getByRole('menuitem', { name: 'Duplicate' }).click(),
    ]);

    const body = await createResponse.json();
    expect(body.data.description).toBe(description);
    expect(body.data.project_id).toBe(project.id);
    expect(body.data.billable).toBe(true);
    await expect(page.locator('.fc-event').filter({ hasText: description })).toHaveCount(2);
});

test('test that context menu delete removes the time entry', async ({ page, ctx }) => {
    const description = 'Context delete test ' + Math.floor(1 + Math.random() * 10000);
    await createBareTimeEntryViaApi(ctx, description, '1h');

    await goToCalendar(page);
    await openContextMenu(page, description);

    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/') &&
                response.request().method() === 'DELETE' &&
                response.status() === 204
        ),
        page.getByRole('menuitem', { name: 'Delete' }).click(),
    ]);

    await expect(page.locator('.fc-event').filter({ hasText: description })).not.toBeVisible();
});

test('test that context menu split divides time entry into two', async ({ page, ctx }) => {
    const description = 'Context split test ' + Math.floor(1 + Math.random() * 10000);
    await createBareTimeEntryViaApi(ctx, description, '2h');

    await goToCalendar(page);
    await expect(page.locator('.fc-event').filter({ hasText: description })).toHaveCount(1);
    await openContextMenu(page, description);

    const [updateResponse, createResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries') &&
                response.request().method() === 'POST' &&
                response.status() === 201
        ),
        page.getByRole('menuitem', { name: 'Split' }).click(),
    ]);

    const updateBody = await updateResponse.json();
    const createBody = await createResponse.json();
    expect(updateBody.data.end).toBe(createBody.data.start);
    await expect(page.locator('.fc-event').filter({ hasText: description })).toHaveCount(2);
});

test('test that context menu create time entry opens the create modal', async ({ page }) => {
    await goToCalendar(page);
    await expect(page.locator('.fc')).toBeVisible();

    const slotLane = page.locator('.fc-timegrid-slot-lane').first();
    await expect(slotLane).toBeVisible();
    await slotLane.click({ button: 'right' });

    await expect(page.getByRole('menu')).toBeVisible();
    await page.getByRole('menuitem', { name: 'Create Time Entry' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();
});

test('test that context menu for running entry shows stop and discard options', async ({
    page,
    ctx,
}) => {
    const description = 'Running ctx menu test ' + Math.floor(1 + Math.random() * 10000);
    await createRunningTimeEntryViaApi(ctx, description);

    await goToCalendar(page);
    await openContextMenu(page, description);

    // Running entry should show Stop and Discard, not Edit/Duplicate/Split/Delete
    await expect(page.getByRole('menuitem', { name: 'Stop' })).toBeVisible();
    await expect(page.getByRole('menuitem', { name: 'Discard' })).toBeVisible();
    await expect(page.getByRole('menuitem', { name: 'Edit' })).not.toBeVisible();
    await expect(page.getByRole('menuitem', { name: 'Duplicate' })).not.toBeVisible();
    await expect(page.getByRole('menuitem', { name: 'Split' })).not.toBeVisible();
});

test('test that context menu stop on running entry sets end time', async ({ page, ctx }) => {
    const description = 'Running stop test ' + Math.floor(1 + Math.random() * 10000);
    await createRunningTimeEntryViaApi(ctx, description);

    await goToCalendar(page);
    await openContextMenu(page, description);

    const [updateResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
        page.getByRole('menuitem', { name: 'Stop' }).click(),
    ]);

    const body = await updateResponse.json();
    expect(body.data.end).not.toBeNull();
    expect(body.data.description).toBe(description);
});

test('test that context menu discard on running entry deletes it', async ({ page, ctx }) => {
    const description = 'Running discard test ' + Math.floor(1 + Math.random() * 10000);
    await createRunningTimeEntryViaApi(ctx, description);

    await goToCalendar(page);
    await openContextMenu(page, description);

    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/') &&
                response.request().method() === 'DELETE' &&
                response.status() === 204
        ),
        page.getByRole('menuitem', { name: 'Discard' }).click(),
    ]);

    await expect(page.locator('.fc-event').filter({ hasText: description })).not.toBeVisible();
});

// =============================================
// Employee Permission Tests
// =============================================

test.describe('Employee Calendar Isolation', () => {
    test('employee can only see their own time entries on the calendar', async ({
        ctx,
        employee,
    }) => {
        // Owner creates a time entry for today
        const ownerDescription = 'OwnerCalEntry ' + Math.floor(Math.random() * 10000);
        await createBareTimeEntryViaApi(ctx, ownerDescription, '1h');

        // Create a time entry for the employee for today
        const employeeDescription = 'EmpCalEntry ' + Math.floor(Math.random() * 10000);
        await createTimeEntryViaApi(
            { ...ctx, memberId: employee.memberId },
            { description: employeeDescription, duration: '30min' }
        );

        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/calendar');
        await expect(employee.page.locator('.fc')).toBeVisible({ timeout: 10000 });

        // Employee's event IS visible
        await expect(
            employee.page.locator('.fc-event').filter({ hasText: employeeDescription }).first()
        ).toBeVisible({ timeout: 10000 });

        // Owner's event is NOT visible
        await expect(
            employee.page.locator('.fc-event').filter({ hasText: ownerDescription })
        ).not.toBeVisible();
    });
});
