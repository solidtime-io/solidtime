import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import {
    createBillableProjectViaApi,
    createProjectViaApi,
    createBareTimeEntryViaApi,
} from './utils/api';

async function goToCalendar(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/calendar');
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
