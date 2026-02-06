import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import { createProject, createBillableProject, createBareTimeEntry } from './utils/reporting';

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
}) => {
    const billableProjectName = 'Billable Cal Project ' + Math.floor(1 + Math.random() * 10000);

    await createBillableProject(page, billableProjectName);
    await createBareTimeEntry(page, 'Test billable calendar', '1h');

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
}) => {
    const billableProjectName = 'Billable Cal Rev Project ' + Math.floor(1 + Math.random() * 10000);
    const nonBillableProjectName =
        'NonBillable Cal Rev Project ' + Math.floor(1 + Math.random() * 10000);

    await createBillableProject(page, billableProjectName);
    await createProject(page, nonBillableProjectName);
    await createBareTimeEntry(page, 'Test billable cal reverse', '1h');

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
}) => {
    const billableProjectName =
        'Billable Cal Persist Project ' + Math.floor(1 + Math.random() * 10000);

    await createBillableProject(page, billableProjectName);
    await createBareTimeEntry(page, 'Test cal persist override', '1h');

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
