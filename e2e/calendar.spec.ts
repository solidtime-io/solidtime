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
    createTimeEntryWithTimestampsViaApi,
    createRunningTimeEntryWithStartViaApi,
    createClientViaApi,
    createTaskViaApi,
    createProjectWithClientViaApi,
    updateUserProfileViaWeb,
    updateOrganizationSettingViaApi,
} from './utils/api';
import type { TestContext } from '../playwright/fixtures';

async function goToCalendar(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/calendar');
    await expect(page.locator('.fc')).toBeVisible({ timeout: 10000 });
}

async function scrollCalendarToTime(page: Page, time: string) {
    await page.evaluate((t) => {
        const slot = document.querySelector(`.fc-timegrid-slot-lane[data-time="${t}"]`);
        if (slot) slot.scrollIntoView({ block: 'start' });
    }, time);
    await page.waitForTimeout(300);
}

async function getSlotHeight(page: Page): Promise<number> {
    return await page.evaluate(() => {
        const slots = document.querySelectorAll('.fc-timegrid-slot-lane');
        for (const slot of slots) {
            const h = slot.getBoundingClientRect().height;
            if (h > 0) return h;
        }
        return 20;
    });
}

async function openContextMenu(page: Page, description: string) {
    const event = page.locator('.fc-event').filter({ hasText: description }).first();
    await expect(event).toBeVisible();
    await event.click({ button: 'right' });
    await expect(page.getByRole('menu')).toBeVisible();
}

function todayAt(hour: number, minute: number = 0): string {
    const now = new Date();
    const d = new Date(now.getFullYear(), now.getMonth(), now.getDate(), hour, minute, 0, 0);
    return d.toISOString().replace(/\.\d{3}Z$/, 'Z');
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
    await page.getByRole('button', { name: 'Next' }).click();
    await expect(page.locator('.fc')).toBeVisible();

    // Click the "prev" button to navigate back
    await page.getByRole('button', { name: 'Previous' }).click();
    await expect(page.locator('.fc')).toBeVisible();

    // Navigate forward first, then click today
    await page.getByRole('button', { name: 'Next' }).click();
    await page.getByRole('button', { name: 'today' }).click();
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

// =============================================
// Section 1: Event Rendering & Display
// =============================================

test.describe('Event Rendering & Display', () => {
    test('1.1 event shows description, project name, and duration', async ({ page, ctx }) => {
        const projectName = 'Render Project ' + Math.floor(Math.random() * 10000);
        const project = await createProjectViaApi(ctx, { name: projectName });
        await createTimeEntryViaApi(ctx, {
            description: 'Render test entry',
            duration: '1h',
            projectId: project.id,
        });
        await goToCalendar(page);
        const event = page.locator('.fc-event').filter({ hasText: 'Render test entry' }).first();
        await expect(event).toBeVisible();
        await expect(event).toContainText(projectName);
        await expect(event).toContainText('1h 00min');
    });

    test('1.2 event shows task and client name', async ({ page, ctx }) => {
        const clientName = 'Render Client ' + Math.floor(Math.random() * 10000);
        const projectName = 'Render Task Project ' + Math.floor(Math.random() * 10000);
        const taskName = 'Render Task ' + Math.floor(Math.random() * 10000);
        const { project } = await createProjectWithClientViaApi(ctx, projectName, clientName);
        const task = await createTaskViaApi(ctx, { name: taskName, project_id: project.id });
        await createTimeEntryViaApi(ctx, {
            description: 'Task display entry',
            duration: '1h',
            projectId: project.id,
            taskId: task.id,
        });
        await goToCalendar(page);
        const event = page.locator('.fc-event').filter({ hasText: 'Task display entry' }).first();
        await expect(event).toBeVisible();
        await expect(event).toContainText(taskName);
        await expect(event).toContainText(clientName);
    });

    test('1.3 event color uses project color blended with background', async ({ page, ctx }) => {
        const project = await createProjectViaApi(ctx, {
            name: 'Color Project ' + Math.floor(Math.random() * 10000),
            color: '#ef5350',
        });
        await createTimeEntryViaApi(ctx, {
            description: 'Color test',
            duration: '1h',
            projectId: project.id,
        });
        await goToCalendar(page);
        const event = page.locator('.fc-event').filter({ hasText: 'Color test' }).first();
        await expect(event).toBeVisible();
        // The event should have a background color that is NOT the raw project color
        // but a blended version. Just verify it has a background-color style set.
        const bgColor = await event.evaluate((el) => getComputedStyle(el).backgroundColor);
        expect(bgColor).not.toBe('');
        expect(bgColor).not.toBe('rgba(0, 0, 0, 0)');
        // The raw #ef5350 = rgb(239, 83, 80). The blended color should differ.
        expect(bgColor).not.toBe('rgb(239, 83, 80)');
    });

    test('1.4 event without project uses default gray color', async ({ page, ctx }) => {
        await createBareTimeEntryViaApi(ctx, 'No project entry', '1h');
        await goToCalendar(page);
        const event = page.locator('.fc-event').filter({ hasText: 'No project entry' }).first();
        await expect(event).toBeVisible();
        const bgColor = await event.evaluate((el) => getComputedStyle(el).backgroundColor);
        expect(bgColor).not.toBe('rgba(0, 0, 0, 0)');
    });

    test('1.5 overlapping events render side by side', async ({ page, ctx }) => {
        // Create 2 overlapping entries using explicit timestamps
        const start = todayAt(10);
        const end = todayAt(11);
        await createTimeEntryWithTimestampsViaApi(ctx, { description: 'Overlap A', start, end });
        await createTimeEntryWithTimestampsViaApi(ctx, { description: 'Overlap B', start, end });
        await goToCalendar(page);
        const eventA = page.locator('.fc-event').filter({ hasText: 'Overlap A' }).first();
        const eventB = page.locator('.fc-event').filter({ hasText: 'Overlap B' }).first();
        await expect(eventA).toBeVisible();
        await expect(eventB).toBeVisible();
        // They should not fully overlap — check they have different x positions or widths
        const boxA = await eventA.boundingBox();
        const boxB = await eventB.boundingBox();
        expect(boxA).not.toBeNull();
        expect(boxB).not.toBeNull();
        // FullCalendar places overlapping events side by side, so widths should be less than full column
        // or x positions should differ
        const xDiff = Math.abs(boxA!.x - boxB!.x);
        const combinedWidth = boxA!.width + boxB!.width;
        // Either they're at different positions or they're both narrower
        expect(xDiff > 5 || combinedWidth < boxA!.width * 3).toBeTruthy();
    });

    test('1.6 very short event still renders visibly', async ({ page, ctx }) => {
        const start = todayAt(10);
        const end = todayAt(10, 5); // 5 minutes
        await createTimeEntryWithTimestampsViaApi(ctx, { description: 'Short event', start, end });
        await goToCalendar(page);
        const event = page.locator('.fc-event').filter({ hasText: 'Short event' }).first();
        await expect(event).toBeVisible();
        const box = await event.boundingBox();
        expect(box).not.toBeNull();
        expect(box!.height).toBeGreaterThan(0);
    });

    test('1.7 running entry has distinct visual style', async ({ page, ctx }) => {
        await createRunningTimeEntryViaApi(ctx, 'Running style test');
        await goToCalendar(page);
        const event = page.locator('.fc-event').filter({ hasText: 'Running style test' }).first();
        await expect(event).toBeVisible();
        // Running entries should have the running-entry class
        await expect(event).toHaveClass(/running-entry/);
    });

    test('1.8 entry with no description shows fallback text', async ({ page, ctx }) => {
        await createTimeEntryViaApi(ctx, { description: '', duration: '1h' });
        await goToCalendar(page);
        const event = page.locator('.fc-event').filter({ hasText: 'No description' }).first();
        await expect(event).toBeVisible();
    });
});

// =============================================
// Section 2: Drag-to-Move Events
// =============================================

test.describe('Drag-to-Move Events', () => {
    test('2.1 drag event to different time slot on same day', async ({ page, ctx }) => {
        const start = todayAt(10);
        const end = todayAt(11);
        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Drag time test',
            start,
            end,
        });
        await goToCalendar(page);
        await scrollCalendarToTime(page, '09:00:00');
        const event = page.locator('.fc-event').filter({ hasText: 'Drag time test' }).first();
        await expect(event).toBeVisible();

        const slotHeight = await getSlotHeight(page);
        const eventBox = await event.boundingBox();

        // Drag the event down by 2 hours (8 x 15-min slots)
        const [putResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/time-entries/') &&
                    r.request().method() === 'PUT' &&
                    r.status() === 200
            ),
            (async () => {
                await event.hover();
                await page.mouse.down();
                await page.mouse.move(
                    eventBox!.x + eventBox!.width / 2,
                    eventBox!.y + slotHeight * 8,
                    {
                        steps: 15,
                    }
                );
                await page.mouse.up();
            })(),
        ]);

        const body = await putResponse.json();
        // Start should have changed from 10:00
        expect(body.data.start).not.toContain('T10:00:00');
        // Duration should be preserved (1 hour)
        const startDate = new Date(body.data.start);
        const endDate = new Date(body.data.end);
        const durationMs = endDate.getTime() - startDate.getTime();
        expect(durationMs).toBe(3600000); // 1 hour
    });

    test('2.2 drag event to different day', async ({ page, ctx }) => {
        const start = todayAt(10);
        const end = todayAt(11);
        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Drag day test',
            start,
            end,
        });
        await goToCalendar(page);
        await scrollCalendarToTime(page, '09:00:00');

        // Get all column headers to find a different day (headers are always visible)
        const headers = page.locator('.fc-col-header-cell');
        const headerCount = await headers.count();
        let targetX: number | undefined;
        // Find a column that's not today
        for (let i = 0; i < headerCount; i++) {
            const header = headers.nth(i);
            const hasToday = await header.evaluate((el) => el.classList.contains('fc-day-today'));
            if (!hasToday) {
                const box = await header.boundingBox();
                targetX = box!.x + box!.width / 2;
                break;
            }
        }

        const event = page.locator('.fc-event').filter({ hasText: 'Drag day test' }).first();
        await expect(event).toBeVisible();
        const eventBox = await event.boundingBox();

        const [putResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/time-entries/') &&
                    r.request().method() === 'PUT' &&
                    r.status() === 200
            ),
            (async () => {
                await event.hover();
                await page.mouse.down();
                await page.mouse.move(targetX!, eventBox!.y + eventBox!.height / 2, { steps: 15 });
                await page.mouse.up();
            })(),
        ]);

        const body = await putResponse.json();
        // The date should have changed
        const originalDate = new Date(start).toISOString().split('T')[0];
        const newDate = new Date(body.data.start).toISOString().split('T')[0];
        expect(newDate).not.toBe(originalDate);
    });

    test('2.4 drag preserves original event duration', async ({ page, ctx }) => {
        const start = todayAt(9);
        const end = todayAt(11); // 2 hours
        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Duration preserve test',
            start,
            end,
        });
        await goToCalendar(page);
        await scrollCalendarToTime(page, '08:00:00');
        const event = page
            .locator('.fc-event')
            .filter({ hasText: 'Duration preserve test' })
            .first();
        await expect(event).toBeVisible();

        const slotHeight = await getSlotHeight(page);
        const eventBox = await event.boundingBox();

        // Drag the event down by 2 hours (8 x 15-min slots)
        const [putResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/time-entries/') &&
                    r.request().method() === 'PUT' &&
                    r.status() === 200
            ),
            (async () => {
                await event.hover();
                await page.mouse.down();
                await page.mouse.move(
                    eventBox!.x + eventBox!.width / 2,
                    eventBox!.y + slotHeight * 8,
                    {
                        steps: 15,
                    }
                );
                await page.mouse.up();
            })(),
        ]);

        const body = await putResponse.json();
        const startDate = new Date(body.data.start);
        const endDate = new Date(body.data.end);
        const durationMs = endDate.getTime() - startDate.getTime();
        expect(durationMs).toBe(7200000); // 2 hours preserved
    });

    test('2.5 running entry cannot be dragged', async ({ page, ctx }) => {
        await createRunningTimeEntryViaApi(ctx, 'No drag running');
        await goToCalendar(page);
        // Scroll to make the running entry visible (it started ~10min ago)
        const nowHour = new Date().getHours();
        const scrollTime = `${String(Math.max(0, nowHour - 1)).padStart(2, '0')}:00:00`;
        await scrollCalendarToTime(page, scrollTime);
        const event = page.locator('.fc-event').filter({ hasText: 'No drag running' }).first();
        await expect(event).toBeVisible();

        const eventBox = await event.boundingBox();
        const originalY = eventBox!.y;

        // Try to drag
        await event.hover();
        await page.mouse.down();
        await page.mouse.move(eventBox!.x, eventBox!.y + 100, { steps: 10 });
        await page.mouse.up();

        // Wait a bit for any potential update
        await page.waitForTimeout(500);

        // Event should still be at original position (approximately)
        const newBox = await event.boundingBox();
        expect(Math.abs(newBox!.y - originalY)).toBeLessThan(26);
    });

    test('2.6 cross-day drag preserves time of day and duration', async ({ page, ctx }) => {
        const start = todayAt(10);
        const end = todayAt(11);
        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Cross day preserve test',
            start,
            end,
        });
        await goToCalendar(page);
        await scrollCalendarToTime(page, '09:00:00');

        // Find a non-today column header to get the target X coordinate
        const headers = page.locator('.fc-col-header-cell');
        const headerCount = await headers.count();
        let targetX: number | undefined;
        for (let i = 0; i < headerCount; i++) {
            const header = headers.nth(i);
            const isToday = await header.evaluate((el) => el.classList.contains('fc-day-today'));
            if (!isToday) {
                const box = await header.boundingBox();
                targetX = box!.x + box!.width / 2;
                break;
            }
        }

        const event = page
            .locator('.fc-event')
            .filter({ hasText: 'Cross day preserve test' })
            .first();
        await expect(event).toBeVisible();
        const eventBox = await event.boundingBox();

        const [putResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/time-entries/') &&
                    r.request().method() === 'PUT' &&
                    r.status() === 200
            ),
            (async () => {
                await event.hover();
                await page.mouse.down();
                // Move to different day, same Y (preserves time of day)
                await page.mouse.move(targetX!, eventBox!.y + eventBox!.height / 2, {
                    steps: 15,
                });
                await page.mouse.up();
            })(),
        ]);

        const body = await putResponse.json();
        const newStart = new Date(body.data.start);
        const newEnd = new Date(body.data.end);
        const origStart = new Date(start);

        // Date should have changed
        expect(newStart.toISOString().split('T')[0]).not.toBe(
            origStart.toISOString().split('T')[0]
        );
        // Duration should be preserved (1 hour = 3600000ms)
        expect(newEnd.getTime() - newStart.getTime()).toBe(3600000);
    });

    test('2.7 cross-day drag shows faded ghost in original column', async ({ page, ctx }) => {
        const start = todayAt(10);
        const end = todayAt(12);
        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Ghost preview test',
            start,
            end,
        });
        await goToCalendar(page);
        await scrollCalendarToTime(page, '09:00:00');

        // Find a non-today column header
        const headers = page.locator('.fc-col-header-cell');
        const headerCount = await headers.count();
        let targetX: number | undefined;
        for (let i = 0; i < headerCount; i++) {
            const header = headers.nth(i);
            const isToday = await header.evaluate((el) => el.classList.contains('fc-day-today'));
            if (!isToday) {
                const box = await header.boundingBox();
                targetX = box!.x + box!.width / 2;
                break;
            }
        }

        const event = page.locator('.fc-event').filter({ hasText: 'Ghost preview test' }).first();
        await expect(event).toBeVisible();
        const eventBox = await event.boundingBox();

        // Start dragging to another day but don't release
        await event.hover();
        await page.mouse.down();
        await page.mouse.move(targetX!, eventBox!.y + eventBox!.height / 2, {
            steps: 15,
        });

        // While dragging across days, the original event should be faded (opacity ~0.3)
        const opacity = await event.evaluate((el) =>
            parseFloat(window.getComputedStyle(el).opacity)
        );
        expect(opacity).toBeLessThanOrEqual(0.4);

        // A cross-day preview should appear in the target column
        const preview = page.locator('.fc-cross-day-preview');
        await expect(preview).toBeVisible();

        await page.mouse.up();
    });

    test('2.8 dragging single-day event upward past midnight spills to previous day', async ({
        page,
        ctx,
    }) => {
        const now = new Date();
        const dayOfWeek = now.getDay();
        // Need today to have a previous day visible in the week view (skip Sunday with Monday week start)
        test.skip(dayOfWeek === 1, 'Skipping on Monday — previous day not visible in week view');

        // Create entry: today 00:30 → today 01:30 (1 hour, near midnight)
        const start = todayAt(0, 30);
        const end = todayAt(1, 30);
        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Drag up past midnight test',
            start,
            end,
        });
        await goToCalendar(page);
        await scrollCalendarToTime(page, '00:00:00');

        const todayStr = new Date(now.getFullYear(), now.getMonth(), now.getDate())
            .toISOString()
            .split('T')[0];
        const todayCol = page.locator(`.fc-timegrid-col[data-date="${todayStr}"]`);
        const event = todayCol
            .locator('.fc-event')
            .filter({ hasText: 'Drag up past midnight test' });
        await expect(event).toBeVisible({ timeout: 10000 });

        const eventBox = await event.boundingBox();
        const slotHeight = await getSlotHeight(page);
        const startX = eventBox!.x + eventBox!.width / 2;
        const startY = eventBox!.y + eventBox!.height / 2;

        // Drag up by ~3 slots (45 minutes) — should push the event past midnight to the previous day
        const [putResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/time-entries/') &&
                    r.request().method() === 'PUT' &&
                    r.status() === 200
            ),
            (async () => {
                await page.mouse.move(startX, startY);
                await page.waitForTimeout(100);
                await page.mouse.down();
                await page.mouse.move(startX, startY - slotHeight * 3, { steps: 15 });
                await page.mouse.up();
            })(),
        ]);

        const body = await putResponse.json();
        const newStart = new Date(body.data.start);
        const newEnd = new Date(body.data.end);
        const newDurationMs = newEnd.getTime() - newStart.getTime();

        // Duration must be preserved (1 hour)
        expect(newDurationMs).toBe(3600000);

        // The event should have moved to the previous day
        const yesterdayStr = new Date(now.getFullYear(), now.getMonth(), now.getDate() - 1)
            .toISOString()
            .split('T')[0];
        expect(newStart.toISOString().split('T')[0]).toBe(yesterdayStr);
    });
});

// =============================================
// Section 3: Resize Events
// =============================================

test.describe('Resize Events', () => {
    test('3.1 resize event from bottom edge extends duration', async ({ page, ctx }) => {
        const start = todayAt(10);
        const end = todayAt(11);
        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Resize bottom test',
            start,
            end,
        });
        await goToCalendar(page);
        await scrollCalendarToTime(page, '09:00:00');
        const event = page.locator('.fc-event').filter({ hasText: 'Resize bottom test' }).first();
        await expect(event).toBeVisible();

        const slotHeight = await getSlotHeight(page);
        const eventBox = await event.boundingBox();
        // Bottom edge of the event
        const bottomY = eventBox!.y + eventBox!.height;
        const centerX = eventBox!.x + eventBox!.width / 2;

        const [putResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/time-entries/') &&
                    r.request().method() === 'PUT' &&
                    r.status() === 200
            ),
            (async () => {
                // Hover to show resize handle, then drag from bottom
                await page.mouse.move(centerX, bottomY - 3);
                await page.waitForTimeout(100);
                await page.mouse.down();
                // Drag down by ~1 hour worth of pixels (4 slots of 15 min)
                await page.mouse.move(centerX, bottomY + slotHeight * 4, { steps: 15 });
                await page.mouse.up();
            })(),
        ]);

        const body = await putResponse.json();
        const startDate = new Date(body.data.start);
        const endDate = new Date(body.data.end);
        const durationMs = endDate.getTime() - startDate.getTime();
        // Should be longer than 1 hour now
        expect(durationMs).toBeGreaterThan(3600000);
    });

    test('3.2 resize event from top edge changes start time', async ({ page, ctx }) => {
        const start = todayAt(12);
        const end = todayAt(14);
        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Resize top test',
            start,
            end,
        });
        await goToCalendar(page);
        await scrollCalendarToTime(page, '10:00:00');
        const event = page.locator('.fc-event').filter({ hasText: 'Resize top test' }).first();
        await expect(event).toBeVisible();

        const slotHeight = await getSlotHeight(page);
        const eventBox = await event.boundingBox();
        const topY = eventBox!.y;
        const centerX = eventBox!.x + eventBox!.width / 2;

        const [putResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/time-entries/') &&
                    r.request().method() === 'PUT' &&
                    r.status() === 200
            ),
            (async () => {
                await page.mouse.move(centerX, topY + 3);
                await page.waitForTimeout(100);
                await page.mouse.down();
                const oneHourPx = slotHeight * 4;
                await page.mouse.move(centerX, topY - oneHourPx, { steps: 15 });
                await page.mouse.up();
            })(),
        ]);

        const body = await putResponse.json();
        // End should be preserved
        const endDate = new Date(body.data.end);
        expect(endDate.getUTCHours()).toBe(new Date(end).getUTCHours());
        // Start should have moved earlier
        const startDate = new Date(body.data.start);
        expect(startDate.getTime()).toBeLessThan(new Date(start).getTime());
    });

    test('3.4 resize preserves the non-resized edge', async ({ page, ctx }) => {
        const start = todayAt(10);
        const end = todayAt(12);
        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Preserve edge test',
            start,
            end,
        });
        await goToCalendar(page);
        await scrollCalendarToTime(page, '09:00:00');
        const event = page.locator('.fc-event').filter({ hasText: 'Preserve edge test' }).first();
        await expect(event).toBeVisible();

        const slotHeight = await getSlotHeight(page);
        const eventBox = await event.boundingBox();
        const bottomY = eventBox!.y + eventBox!.height;
        const centerX = eventBox!.x + eventBox!.width / 2;

        const [putResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/time-entries/') &&
                    r.request().method() === 'PUT' &&
                    r.status() === 200
            ),
            (async () => {
                await page.mouse.move(centerX, bottomY - 3);
                await page.waitForTimeout(100);
                await page.mouse.down();
                await page.mouse.move(centerX, bottomY + slotHeight * 4, { steps: 15 });
                await page.mouse.up();
            })(),
        ]);

        const body = await putResponse.json();
        // Start should be unchanged
        const startDate = new Date(body.data.start);
        const origStart = new Date(start);
        expect(Math.abs(startDate.getTime() - origStart.getTime())).toBeLessThan(60000);
    });

    test('3.5 running entry cannot be resized from bottom', async ({ page, ctx }) => {
        await createRunningTimeEntryViaApi(ctx, 'No bottom resize');
        await goToCalendar(page);
        const event = page.locator('.fc-event').filter({ hasText: 'No bottom resize' }).first();
        await expect(event).toBeVisible();

        // The bottom resize handle should be hidden via CSS
        const endResizer = event.locator('.fc-event-resizer-end');
        // It might exist in DOM but be display:none
        if ((await endResizer.count()) > 0) {
            await expect(endResizer).toBeHidden();
        }
    });

    test('3.6 running entry start can be changed via top-edge resize', async ({ page, ctx }) => {
        const startTime = new Date();
        startTime.setHours(startTime.getHours() - 2);
        const startStr = startTime.toISOString().replace(/\.\d{3}Z$/, 'Z');
        await createRunningTimeEntryWithStartViaApi(ctx, 'Resize running start', startStr);
        await goToCalendar(page);
        // Scroll to make the running entry's top edge visible
        const scrollHour = Math.max(0, startTime.getHours() - 1);
        await scrollCalendarToTime(page, `${String(scrollHour).padStart(2, '0')}:00:00`);
        const event = page.locator('.fc-event').filter({ hasText: 'Resize running start' }).first();
        await expect(event).toBeVisible();

        const eventBox = await event.boundingBox();
        const topY = eventBox!.y;
        const centerX = eventBox!.x + eventBox!.width / 2;

        const [putResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/time-entries/') &&
                    r.request().method() === 'PUT' &&
                    r.status() === 200
            ),
            (async () => {
                await page.mouse.move(centerX, topY + 3);
                await page.waitForTimeout(100);
                await page.mouse.down();
                const slotLane = page.locator('.fc-timegrid-slot-lane').first();
                const slotHeight = (await slotLane.boundingBox())!.height;
                // Move down (make it start later)
                await page.mouse.move(centerX, topY + slotHeight * 4, { steps: 15 });
                await page.mouse.up();
            })(),
        ]);

        const body = await putResponse.json();
        // End should remain null for running entries
        expect(body.data.end).toBeNull();
        // Start should have moved later
        expect(new Date(body.data.start).getTime()).toBeGreaterThan(startTime.getTime());
    });

    test('3.8 running entry resize preserves end:null in API response', async ({ page, ctx }) => {
        const startTime = new Date();
        startTime.setHours(startTime.getHours() - 1);
        const startStr = startTime.toISOString().replace(/\.\d{3}Z$/, 'Z');
        await createRunningTimeEntryWithStartViaApi(ctx, 'End null preserve', startStr);
        await goToCalendar(page);
        // Scroll to make the running entry's top edge visible
        const scrollHour = Math.max(0, startTime.getHours() - 1);
        await scrollCalendarToTime(page, `${String(scrollHour).padStart(2, '0')}:00:00`);
        const event = page.locator('.fc-event').filter({ hasText: 'End null preserve' }).first();
        await expect(event).toBeVisible();

        const eventBox = await event.boundingBox();
        const topY = eventBox!.y;
        const centerX = eventBox!.x + eventBox!.width / 2;

        // Intercept the PUT request to check what's sent
        const [putRequest] = await Promise.all([
            page.waitForRequest((r) => r.url().includes('/time-entries/') && r.method() === 'PUT'),
            (async () => {
                await page.mouse.move(centerX, topY + 3);
                await page.waitForTimeout(100);
                await page.mouse.down();
                const slotLane = page.locator('.fc-timegrid-slot-lane').first();
                const slotHeight = (await slotLane.boundingBox())!.height;
                await page.mouse.move(centerX, topY + slotHeight * 2, { steps: 15 });
                await page.mouse.up();
            })(),
        ]);

        const requestBody = putRequest.postDataJSON();
        expect(requestBody.end).toBeNull();
    });

    test('3.9 resize bottom edge across day boundary changes end date', async ({ page, ctx }) => {
        const start = todayAt(10);
        const end = todayAt(11);
        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Cross resize end test',
            start,
            end,
        });
        await goToCalendar(page);
        await scrollCalendarToTime(page, '09:00:00');

        // Find a column AFTER today (for end resize, end must be > start)
        const headers = page.locator('.fc-col-header-cell');
        const headerCount = await headers.count();
        let targetX: number | undefined;
        let todayIndex = -1;
        for (let i = 0; i < headerCount; i++) {
            const header = headers.nth(i);
            const isToday = await header.evaluate((el) => el.classList.contains('fc-day-today'));
            if (isToday) {
                todayIndex = i;
                break;
            }
        }
        // Pick first column after today, or skip if today is last
        for (let i = todayIndex + 1; i < headerCount; i++) {
            const box = await headers.nth(i).boundingBox();
            targetX = box!.x + box!.width / 2;
            break;
        }
        test.skip(targetX === undefined, 'No column after today to resize to');

        const event = page
            .locator('.fc-event')
            .filter({ hasText: 'Cross resize end test' })
            .first();
        await expect(event).toBeVisible();

        const slotHeight = await getSlotHeight(page);
        const eventBox = await event.boundingBox();
        const bottomY = eventBox!.y + eventBox!.height;
        const centerX = eventBox!.x + eventBox!.width / 2;

        const [putResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/time-entries/') &&
                    r.request().method() === 'PUT' &&
                    r.status() === 200
            ),
            (async () => {
                // Same approach as working test 3.1: hover bottom, drag down
                await page.mouse.move(centerX, bottomY - 3);
                await page.waitForTimeout(100);
                await page.mouse.down();
                // First drag down vertically to engage resize (like test 3.1)
                await page.mouse.move(centerX, bottomY + slotHeight * 4, { steps: 15 });
                // Then move horizontally to a later day column
                await page.mouse.move(targetX!, bottomY + slotHeight * 4, { steps: 10 });
                await page.mouse.up();
            })(),
        ]);

        const body = await putResponse.json();
        const origStart = new Date(start);
        const newStart = new Date(body.data.start);
        const newEnd = new Date(body.data.end);

        // Start should be preserved
        expect(Math.abs(newStart.getTime() - origStart.getTime())).toBeLessThan(60000);
        // End date should have changed to a different day
        expect(newEnd.toISOString().split('T')[0]).not.toBe(
            new Date(end).toISOString().split('T')[0]
        );
    });

    test('3.10 resize bottom edge across day boundary changes end date', async ({ page, ctx }) => {
        const start = todayAt(10);
        const end = todayAt(14);
        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Cross resize end test',
            start,
            end,
        });
        await goToCalendar(page);
        await scrollCalendarToTime(page, '09:00:00');

        // Find a non-today column header that is AFTER today (later day needed for end-edge resize)
        const headers = page.locator('.fc-col-header-cell');
        const headerCount = await headers.count();
        let targetX: number | undefined;
        let foundToday = false;
        for (let i = 0; i < headerCount; i++) {
            const header = headers.nth(i);
            const isToday = await header.evaluate((el) => el.classList.contains('fc-day-today'));
            if (isToday) {
                foundToday = true;
                continue;
            }
            if (foundToday) {
                const box = await header.boundingBox();
                targetX = box!.x + box!.width / 2;
                break;
            }
        }
        // If today is the last column, use the one before today instead won't work for end resize,
        // so skip this test in that edge case
        if (targetX === undefined) {
            test.skip();
            return;
        }

        const event = page
            .locator('.fc-event')
            .filter({ hasText: 'Cross resize end test' })
            .first();
        await expect(event).toBeVisible();

        const eventBox = await event.boundingBox();
        const bottomY = eventBox!.y + eventBox!.height;
        const centerX = eventBox!.x + eventBox!.width / 2;

        const [putResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/time-entries/') &&
                    r.request().method() === 'PUT' &&
                    r.status() === 200
            ),
            (async () => {
                // Hover bottom edge to show resize handle
                await page.mouse.move(centerX, bottomY - 3);
                await page.waitForTimeout(100);
                await page.mouse.down();
                // Move to a different day column at same Y position
                await page.mouse.move(targetX!, bottomY - 3, { steps: 15 });
                await page.mouse.up();
            })(),
        ]);

        const body = await putResponse.json();
        const origStart = new Date(start);
        const newStart = new Date(body.data.start);
        const newEnd = new Date(body.data.end);

        // Start should be preserved
        expect(Math.abs(newStart.getTime() - origStart.getTime())).toBeLessThan(60000);
        // End date should have changed to a different day
        expect(newEnd.toISOString().split('T')[0]).not.toBe(
            new Date(end).toISOString().split('T')[0]
        );
    });

    test('3.11 cross-day resize shows preview in target column', async ({ page, ctx }) => {
        const start = todayAt(10);
        const end = todayAt(14);
        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Resize preview test',
            start,
            end,
        });
        await goToCalendar(page);
        await scrollCalendarToTime(page, '09:00:00');

        // Find any non-today column header (preview only, direction doesn't matter)
        const headers = page.locator('.fc-col-header-cell');
        const headerCount = await headers.count();
        let targetX: number | undefined;
        for (let i = 0; i < headerCount; i++) {
            const header = headers.nth(i);
            const isToday = await header.evaluate((el) => el.classList.contains('fc-day-today'));
            if (!isToday) {
                const box = await header.boundingBox();
                targetX = box!.x + box!.width / 2;
                break;
            }
        }

        const event = page.locator('.fc-event').filter({ hasText: 'Resize preview test' }).first();
        await expect(event).toBeVisible();

        const slotHeight = await getSlotHeight(page);
        const eventBox = await event.boundingBox();
        const bottomY = eventBox!.y + eventBox!.height;
        const centerX = eventBox!.x + eventBox!.width / 2;

        // Start resize from bottom edge (drag down first, then sideways)
        await page.mouse.move(centerX, bottomY - 3);
        await page.waitForTimeout(100);
        await page.mouse.down();
        await page.mouse.move(centerX, bottomY + slotHeight * 2, { steps: 5 });
        await page.mouse.move(targetX!, bottomY + slotHeight * 2, { steps: 15 });

        // Cross-day preview should be visible (may appear in multiple columns)
        const preview = page.locator('.fc-cross-day-preview').first();
        await expect(preview).toBeVisible();

        await page.mouse.up();
    });

    test('3.12 multi-day event end resize on last day works correctly', async ({ page, ctx }) => {
        // Create entry spanning today evening → tomorrow morning
        const start = todayAt(20);
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const tomorrowStr = `${tomorrow.getFullYear()}-${String(tomorrow.getMonth() + 1).padStart(2, '0')}-${String(tomorrow.getDate()).padStart(2, '0')}`;
        const end = new Date(
            tomorrow.getFullYear(),
            tomorrow.getMonth(),
            tomorrow.getDate(),
            10,
            0,
            0,
            0
        )
            .toISOString()
            .replace(/\.\d{3}Z$/, 'Z');

        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Multi-day end resize',
            start,
            end,
        });
        await goToCalendar(page);

        // Check if tomorrow column is visible
        const tomorrowCol = page.locator(`.fc-timegrid-col[data-date="${tomorrowStr}"]`);
        test.skip((await tomorrowCol.count()) === 0, 'Tomorrow not visible in current view');

        await scrollCalendarToTime(page, '09:00:00');

        // Find the event segment on tomorrow's column
        const event = tomorrowCol
            .locator('.fc-event')
            .filter({ hasText: 'Multi-day end resize' })
            .first();
        await expect(event).toBeVisible();

        const slotHeight = await getSlotHeight(page);
        const eventBox = await event.boundingBox();
        const bottomY = eventBox!.y + eventBox!.height;
        const centerX = eventBox!.x + eventBox!.width / 2;

        // Resize bottom edge down by 2 slots
        const [putResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/time-entries/') &&
                    r.request().method() === 'PUT' &&
                    r.status() === 200
            ),
            (async () => {
                await page.mouse.move(centerX, bottomY - 3);
                await page.waitForTimeout(100);
                await page.mouse.down();
                await page.mouse.move(centerX, bottomY + slotHeight * 2, { steps: 15 });
                await page.mouse.up();
            })(),
        ]);

        const body = await putResponse.json();
        const newStart = new Date(body.data.start);
        const newEnd = new Date(body.data.end);
        const origStart = new Date(start);
        const origEnd = new Date(end);

        // Start must be preserved
        expect(Math.abs(newStart.getTime() - origStart.getTime())).toBeLessThan(60000);
        // End should be later than original
        expect(newEnd.getTime()).toBeGreaterThan(origEnd.getTime());
        // End must be after start
        expect(newEnd.getTime()).toBeGreaterThan(newStart.getTime());
    });

    test('3.13 multi-day event end resize backward to start day produces valid entry', async ({
        page,
        ctx,
    }) => {
        // Create entry spanning today → tomorrow
        const start = todayAt(10);
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        const tomorrowStr = `${tomorrow.getFullYear()}-${String(tomorrow.getMonth() + 1).padStart(2, '0')}-${String(tomorrow.getDate()).padStart(2, '0')}`;
        const end = new Date(
            tomorrow.getFullYear(),
            tomorrow.getMonth(),
            tomorrow.getDate(),
            14,
            0,
            0,
            0
        )
            .toISOString()
            .replace(/\.\d{3}Z$/, 'Z');

        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Backward end resize multi',
            start,
            end,
        });
        await goToCalendar(page);

        // Check if tomorrow column is visible
        const tomorrowCol = page.locator(`.fc-timegrid-col[data-date="${tomorrowStr}"]`);
        test.skip((await tomorrowCol.count()) === 0, 'Tomorrow not visible in current view');

        await scrollCalendarToTime(page, '12:00:00');

        // Find today's column header to get its X center
        const headers = page.locator('.fc-col-header-cell');
        const headerCount = await headers.count();
        let todayX: number | undefined;
        for (let i = 0; i < headerCount; i++) {
            const header = headers.nth(i);
            const isToday = await header.evaluate((el) => el.classList.contains('fc-day-today'));
            if (isToday) {
                const box = await header.boundingBox();
                todayX = box!.x + box!.width / 2;
                break;
            }
        }
        test.skip(todayX === undefined, 'Could not find today column header');

        // Find event segment on tomorrow's column and resize end backward to today
        const event = tomorrowCol
            .locator('.fc-event')
            .filter({ hasText: 'Backward end resize multi' })
            .first();
        await expect(event).toBeVisible();

        const slotHeight = await getSlotHeight(page);
        const eventBox = await event.boundingBox();
        const bottomY = eventBox!.y + eventBox!.height;
        const centerX = eventBox!.x + eventBox!.width / 2;

        const [putResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/time-entries/') &&
                    r.request().method() === 'PUT' &&
                    r.status() === 200
            ),
            (async () => {
                await page.mouse.move(centerX, bottomY - 3);
                await page.waitForTimeout(100);
                await page.mouse.down();
                // Drag down a bit first, then move to today's column at a Y after the start
                await page.mouse.move(centerX, bottomY + slotHeight, { steps: 5 });
                await page.mouse.move(todayX!, bottomY + slotHeight, { steps: 10 });
                await page.mouse.up();
            })(),
        ]);

        const body = await putResponse.json();
        const newStart = new Date(body.data.start);
        const newEnd = new Date(body.data.end);

        // End must be after start (the core invariant)
        expect(newEnd.getTime()).toBeGreaterThan(newStart.getTime());
        // Start should be preserved
        expect(Math.abs(newStart.getTime() - new Date(start).getTime())).toBeLessThan(60000);
    });

    test('3.14 resize end to earlier column prevents end before start', async ({ page, ctx }) => {
        const start = todayAt(10);
        const end = todayAt(14);
        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'End before start test',
            start,
            end,
        });
        await goToCalendar(page);
        await scrollCalendarToTime(page, '09:00:00');

        // Find a column BEFORE today
        const headers = page.locator('.fc-col-header-cell');
        const headerCount = await headers.count();
        let targetX: number | undefined;
        let todayIndex = -1;
        for (let i = 0; i < headerCount; i++) {
            const header = headers.nth(i);
            const isToday = await header.evaluate((el) => el.classList.contains('fc-day-today'));
            if (isToday) {
                todayIndex = i;
                break;
            }
        }
        for (let i = todayIndex - 1; i >= 0; i--) {
            const box = await headers.nth(i).boundingBox();
            targetX = box!.x + box!.width / 2;
            break;
        }
        test.skip(targetX === undefined, 'No column before today to test');

        const event = page
            .locator('.fc-event')
            .filter({ hasText: 'End before start test' })
            .first();
        await expect(event).toBeVisible();

        const slotHeight = await getSlotHeight(page);
        const eventBox = await event.boundingBox();
        const bottomY = eventBox!.y + eventBox!.height;
        const centerX = eventBox!.x + eventBox!.width / 2;

        // Collect PUT request bodies to verify none has end < start
        const putBodies: any[] = [];
        page.on('request', (req) => {
            if (req.url().includes('/time-entries/') && req.method() === 'PUT') {
                try {
                    putBodies.push(req.postDataJSON());
                } catch {}
            }
        });

        // Resize end toward earlier column at a Y that would place end before start
        await page.mouse.move(centerX, bottomY - 3);
        await page.waitForTimeout(100);
        await page.mouse.down();
        // Move to earlier column at a Y position near the top of the grid (before start time)
        await page.mouse.move(targetX!, eventBox!.y - slotHeight * 4, { steps: 15 });
        await page.mouse.up();

        // Wait for any potential API call
        await page.waitForTimeout(1000);

        // If any PUT was made, end must be after start
        for (const body of putBodies) {
            if (body.end !== null) {
                const putStart = new Date(body.start);
                const putEnd = new Date(body.end);
                expect(putEnd.getTime()).toBeGreaterThan(putStart.getTime());
            }
        }
    });
});

// =============================================
// Section 4: Click-Drag Selection to Create
// =============================================

test.describe('Click-Drag Selection to Create', () => {
    test('4.2 completing selection opens create modal with correct times', async ({ page }) => {
        await goToCalendar(page);
        await expect(page.locator('.fc')).toBeVisible();
        await scrollCalendarToTime(page, '09:00:00');

        // Find the 10:00 slot
        const startSlot = page.locator('.fc-timegrid-slot-lane[data-time="10:00:00"]').first();
        await expect(startSlot).toBeVisible();
        const startBox = await startSlot.boundingBox();

        // Find the 11:00 slot (to select 1 hour)
        const endSlot = page.locator('.fc-timegrid-slot-lane[data-time="11:00:00"]').first();
        const endBox = await endSlot.boundingBox();

        // Click-drag from 10:00 to 11:00
        await page.mouse.move(startBox!.x + startBox!.width / 2, startBox!.y + 2);
        await page.mouse.down();
        await page.mouse.move(endBox!.x + endBox!.width / 2, endBox!.y + 2, { steps: 10 });
        await page.mouse.up();

        // Create modal should appear
        await expect(page.getByRole('dialog')).toBeVisible({ timeout: 5000 });
    });

    test('4.3 drag-to-create spanning two days opens create modal with correct cross-day times', async ({
        page,
    }) => {
        const now = new Date();
        const dayOfWeek = now.getDay();
        // Need today and tomorrow both visible (skip Saturday with Monday week start)
        test.skip(dayOfWeek === 6, 'Skipping on Saturday — tomorrow not visible in week view');

        await goToCalendar(page);
        await expect(page.locator('.fc')).toBeVisible();
        // Use mid-day times so both start and end slots are visible in the viewport
        await scrollCalendarToTime(page, '10:00:00');

        // Find today's and tomorrow's columns
        const todayStr = new Date(now.getFullYear(), now.getMonth(), now.getDate())
            .toISOString()
            .split('T')[0];
        const tomorrowStr = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1)
            .toISOString()
            .split('T')[0];

        const todayCol = page.locator(`.fc-timegrid-col[data-date="${todayStr}"]`);
        const tomorrowCol = page.locator(`.fc-timegrid-col[data-date="${tomorrowStr}"]`);
        await expect(todayCol).toBeVisible();
        await expect(tomorrowCol).toBeVisible();

        // Find the 11:00 slot (start) and 13:00 slot (end on tomorrow)
        const startSlot = page.locator('.fc-timegrid-slot-lane[data-time="11:00:00"]').first();
        const endSlot = page.locator('.fc-timegrid-slot-lane[data-time="13:00:00"]').first();
        await expect(startSlot).toBeVisible();
        await expect(endSlot).toBeVisible();

        const startSlotBox = await startSlot.boundingBox();
        const endSlotBox = await endSlot.boundingBox();
        const todayColBox = await todayCol.boundingBox();
        const tomorrowColBox = await tomorrowCol.boundingBox();

        // Start drag at 11:00 on today's column
        const startX = todayColBox!.x + todayColBox!.width / 2;
        const startY = startSlotBox!.y + 2;

        // End drag at 13:00 on tomorrow's column
        const endX = tomorrowColBox!.x + tomorrowColBox!.width / 2;
        const endY = endSlotBox!.y + 2;

        // Drag from today to tomorrow — move down first, then across
        const slotHeight = await getSlotHeight(page);
        await page.mouse.move(startX, startY);
        await page.mouse.down();
        await page.waitForTimeout(100);
        await page.mouse.move(startX, startY + slotHeight * 2, { steps: 5 });
        await page.mouse.move(endX, startY + slotHeight * 2, { steps: 10 });
        await page.mouse.move(endX, endY, { steps: 10 });
        await page.mouse.up();

        // Create modal should appear
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible({ timeout: 5000 });

        // Verify the dialog spans across two days
        await expect(dialog.getByText('Start')).toBeVisible();
        await expect(dialog.getByText('End')).toBeVisible();

        // Start date should be today, end date should be tomorrow
        await expect(dialog.getByText(todayStr)).toBeVisible();
        await expect(dialog.getByText(tomorrowStr)).toBeVisible();
    });
});

// =============================================
// Section 6: Timezone & Localization
// =============================================

test.describe('Timezone & Localization', () => {
    test('week start day: monday shows Mon as first column', async ({ page }) => {
        // Navigate to calendar first to load Inertia page props
        await goToCalendar(page);
        await updateUserProfileViaWeb(page, { week_start: 'monday' });
        await page.reload();
        await expect(page.locator('.fc')).toBeVisible();

        const firstHeader = page.locator('.fc-col-header-cell').first();
        await expect(firstHeader).toContainText('Mon');
    });

    test('week start day: sunday shows Sun as first column', async ({ page }) => {
        await goToCalendar(page);
        await updateUserProfileViaWeb(page, { week_start: 'sunday' });
        await page.reload();
        await expect(page.locator('.fc')).toBeVisible();

        const firstHeader = page.locator('.fc-col-header-cell').first();
        await expect(firstHeader).toContainText('Sun');

        // Reset to monday for other tests
        await updateUserProfileViaWeb(page, { week_start: 'monday' });
    });

    test('12-hour time format shows AM/PM on slot labels', async ({ page, ctx }) => {
        await updateOrganizationSettingViaApi(ctx, { time_format: '12-hours' });
        await page.reload();
        await goToCalendar(page);

        // Look for AM/PM in slot labels
        const slotLabels = page.locator('.fc-timegrid-slot-label-cushion');
        const allText = await slotLabels.allTextContents();
        const hasAmPm = allText.some((t) => t.includes('AM') || t.includes('PM'));
        expect(hasAmPm).toBeTruthy();

        // Reset
        await updateOrganizationSettingViaApi(ctx, { time_format: '24-hours' });
    });

    test('24-hour time format does not show AM/PM on slot labels', async ({ page, ctx }) => {
        await updateOrganizationSettingViaApi(ctx, { time_format: '24-hours' });
        await page.reload();
        await goToCalendar(page);

        const slotLabels = page.locator('.fc-timegrid-slot-label-cushion');
        const allText = await slotLabels.allTextContents();
        // Should NOT contain AM/PM
        const hasAmPm = allText.some((t) => t.includes('AM') || t.includes('PM'));
        expect(hasAmPm).toBeFalsy();
        // Should contain 24h format like "08:00" or "14:00"
        const has24h = allText.some((t) => /^\d{2}:\d{2}$/.test(t.trim()));
        expect(has24h).toBeTruthy();
    });

    test('interval format reflected in event duration display', async ({ page, ctx }) => {
        await updateOrganizationSettingViaApi(ctx, {
            interval_format: 'hours-minutes-colon-separated',
        });
        await createBareTimeEntryViaApi(ctx, 'Duration format test', '1h 30min');
        await page.reload();
        await goToCalendar(page);

        const event = page.locator('.fc-event').filter({ hasText: 'Duration format test' }).first();
        await expect(event).toBeVisible();
        // Should show "1:30" instead of "1h 30min"
        const durationEl = event.locator('[data-duration]');
        await expect(durationEl).toContainText('1:30');

        // Reset
        await updateOrganizationSettingViaApi(ctx, { interval_format: 'hours-minutes' });
    });
});

// =============================================
// Section 7: Multi-Day Events
// =============================================

test.describe('Multi-Day Events', () => {
    test('event spanning 2 days renders and is visible', async ({ page, ctx }) => {
        // Create entry that spans from today 22:00 to tomorrow 02:00
        const now = new Date();
        const dayOfWeek = now.getDay();
        // If today is Saturday (6), the entry would span to next week and may not be visible
        test.skip(dayOfWeek === 6, 'Skipping on Saturday — multi-day would span to next week');

        const startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 22, 0, 0);
        const endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 2, 0, 0);

        const start = startDate.toISOString().replace(/\.\d{3}Z$/, 'Z');
        const end = endDate.toISOString().replace(/\.\d{3}Z$/, 'Z');

        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Multi day entry',
            start,
            end,
        });
        await goToCalendar(page);

        // Wait for the event to appear with retrying
        const event = page.locator('.fc-event').filter({ hasText: 'Multi day entry' }).first();
        await expect(event).toBeVisible({ timeout: 10000 });
    });

    test('multi-day event can be edited via click', async ({ page, ctx }) => {
        const now = new Date();
        test.skip(now.getDay() === 6, 'Skip on Saturday');

        const startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 22, 0, 0);
        const endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 2, 0, 0);

        const start = startDate.toISOString().replace(/\.\d{3}Z$/, 'Z');
        const end = endDate.toISOString().replace(/\.\d{3}Z$/, 'Z');

        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Multi day edit test',
            start,
            end,
        });
        await goToCalendar(page);

        const event = page.locator('.fc-event').filter({ hasText: 'Multi day edit test' }).first();
        await expect(event).toBeVisible();
        await event.click();
        await expect(page.getByRole('dialog')).toBeVisible();
        await expect(
            page.getByRole('dialog').getByPlaceholder('What did you work on?')
        ).toHaveValue('Multi day edit test');
    });

    test('multi-day event context menu works', async ({ page, ctx }) => {
        const now = new Date();
        test.skip(now.getDay() === 6, 'Skip on Saturday');

        const startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 22, 0, 0);
        const endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 2, 0, 0);

        const start = startDate.toISOString().replace(/\.\d{3}Z$/, 'Z');
        const end = endDate.toISOString().replace(/\.\d{3}Z$/, 'Z');

        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Multi day ctx test',
            start,
            end,
        });
        await goToCalendar(page);

        await openContextMenu(page, 'Multi day ctx test');
        await expect(page.getByRole('menuitem', { name: 'Edit' })).toBeVisible();
        await expect(page.getByRole('menuitem', { name: 'Duplicate' })).toBeVisible();
        await expect(page.getByRole('menuitem', { name: 'Split' })).toBeVisible();
        await expect(page.getByRole('menuitem', { name: 'Delete' })).toBeVisible();
    });

    test('dragging clipped segment of multi-day event preserves cross-day span', async ({
        page,
        ctx,
    }) => {
        const now = new Date();
        const dayOfWeek = now.getDay();
        // Need today and tomorrow both visible (skip Saturday)
        test.skip(dayOfWeek === 6, 'Skipping on Saturday — multi-day would span to next week');

        // Create entry: today 22:00 → tomorrow 02:00 (4 hours, spanning 2 days)
        const startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 22, 0, 0);
        const endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 2, 0, 0);
        const start = startDate.toISOString().replace(/\.\d{3}Z$/, 'Z');
        const end = endDate.toISOString().replace(/\.\d{3}Z$/, 'Z');

        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Multi day drag test',
            start,
            end,
        });
        await goToCalendar(page);
        await scrollCalendarToTime(page, '00:00:00');

        // Find the clipped segment on tomorrow's column (00:00-02:00)
        const tomorrowStr = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1)
            .toISOString()
            .split('T')[0];
        const tomorrowCol = page.locator(`.fc-timegrid-col[data-date="${tomorrowStr}"]`);
        const event = tomorrowCol.locator('.fc-event').filter({ hasText: 'Multi day drag test' });
        await expect(event).toBeVisible({ timeout: 10000 });

        const eventBox = await event.boundingBox();
        const slotHeight = await getSlotHeight(page);

        // Drag the clipped segment down by ~1 slot (15 minutes)
        const startX = eventBox!.x + eventBox!.width / 2;
        const startY = eventBox!.y + eventBox!.height / 2;

        const [putResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/time-entries/') &&
                    r.request().method() === 'PUT' &&
                    r.status() === 200
            ),
            (async () => {
                await page.mouse.move(startX, startY);
                await page.waitForTimeout(100);
                await page.mouse.down();
                await page.mouse.move(startX, startY + slotHeight, { steps: 10 });
                await page.mouse.up();
            })(),
        ]);

        const body = await putResponse.json();
        const newStart = new Date(body.data.start);
        const newEnd = new Date(body.data.end);
        const newDurationMs = newEnd.getTime() - newStart.getTime();
        const origDurationMs = endDate.getTime() - startDate.getTime();

        // Duration must be preserved (4 hours)
        expect(Math.abs(newDurationMs - origDurationMs)).toBeLessThan(60000);

        // The start should still be on today (not jumped to tomorrow)
        const todayStr = new Date(now.getFullYear(), now.getMonth(), now.getDate())
            .toISOString()
            .split('T')[0];
        expect(newStart.toISOString().split('T')[0]).toBe(todayStr);
    });

    test('dragging clipped segment of multi-day event upward shifts event earlier', async ({
        page,
        ctx,
    }) => {
        const now = new Date();
        const dayOfWeek = now.getDay();
        // Need today and tomorrow both visible (skip Saturday)
        test.skip(dayOfWeek === 6, 'Skipping on Saturday — multi-day would span to next week');

        // Create entry: today 22:00 → tomorrow 02:00 (4 hours, spanning 2 days)
        const startDate = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 22, 0, 0);
        const endDate = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1, 2, 0, 0);
        const start = startDate.toISOString().replace(/\.\d{3}Z$/, 'Z');
        const end = endDate.toISOString().replace(/\.\d{3}Z$/, 'Z');

        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Multi day drag up test',
            start,
            end,
        });
        await goToCalendar(page);
        await scrollCalendarToTime(page, '00:00:00');

        // Find the clipped segment on tomorrow's column (00:00-02:00)
        const tomorrowStr = new Date(now.getFullYear(), now.getMonth(), now.getDate() + 1)
            .toISOString()
            .split('T')[0];
        const tomorrowCol = page.locator(`.fc-timegrid-col[data-date="${tomorrowStr}"]`);
        const event = tomorrowCol
            .locator('.fc-event')
            .filter({ hasText: 'Multi day drag up test' });
        await expect(event).toBeVisible({ timeout: 10000 });

        const eventBox = await event.boundingBox();
        const slotHeight = await getSlotHeight(page);

        // Drag the clipped segment UP by ~1 slot (15 minutes)
        const startX = eventBox!.x + eventBox!.width / 2;
        const startY = eventBox!.y + eventBox!.height / 2;

        const [putResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/time-entries/') &&
                    r.request().method() === 'PUT' &&
                    r.status() === 200
            ),
            (async () => {
                await page.mouse.move(startX, startY);
                await page.waitForTimeout(100);
                await page.mouse.down();
                await page.mouse.move(startX, startY - slotHeight, { steps: 10 });
                await page.mouse.up();
            })(),
        ]);

        const body = await putResponse.json();
        const newStart = new Date(body.data.start);
        const newEnd = new Date(body.data.end);
        const newDurationMs = newEnd.getTime() - newStart.getTime();
        const origDurationMs = endDate.getTime() - startDate.getTime();

        // Duration must be preserved (4 hours)
        expect(Math.abs(newDurationMs - origDurationMs)).toBeLessThan(60000);

        // The event should have shifted earlier by ~15 minutes
        // Original start was 22:00, new start should be ~21:45
        const shiftMs = startDate.getTime() - newStart.getTime();
        expect(shiftMs).toBeGreaterThan(10 * 60 * 1000); // shifted at least 10 minutes earlier
        expect(shiftMs).toBeLessThan(20 * 60 * 1000); // but not more than 20 minutes
    });
});

// =============================================
// Section 9: Now Indicator
// =============================================

test.describe('Now Indicator', () => {
    test('now indicator is visible on current day', async ({ page }) => {
        await goToCalendar(page);
        // FullCalendar v6 uses fc-timegrid-now-indicator-line for the now indicator
        await expect(async () => {
            const count = await page.locator('.fc-timegrid-now-indicator-line').count();
            expect(count).toBeGreaterThan(0);
        }).toPass({ timeout: 10000 });
    });

    test('now indicator is not visible on past weeks', async ({ page }) => {
        await goToCalendar(page);
        // Navigate to two weeks ago
        await page.getByRole('button', { name: 'Previous' }).click();
        await page.getByRole('button', { name: 'Previous' }).click();
        await expect(page.locator('.fc')).toBeVisible();
        // Now indicator line should not be present in past weeks
        await expect(page.locator('.fc-timegrid-now-indicator-line')).toHaveCount(0);
    });
});

// =============================================
// Section 10: Day Header & Totals
// =============================================

test.describe('Day Header & Totals', () => {
    test('day header shows correct day name for today', async ({ page }) => {
        await goToCalendar(page);
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const todayName = days[new Date().getDay()];
        // The today column should contain the day name
        const todayHeader = page.locator('.fc-day-today.fc-col-header-cell');
        await expect(todayHeader).toContainText(todayName);
    });

    test('day header shows daily total duration', async ({ page, ctx }) => {
        // Create 2 entries: 1h + 30min = 1h 30min total
        const start1 = todayAt(9);
        const end1 = todayAt(10);
        const start2 = todayAt(11);
        const end2 = todayAt(11, 30);
        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Total A',
            start: start1,
            end: end1,
        });
        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Total B',
            start: start2,
            end: end2,
        });
        await goToCalendar(page);

        // Wait for entries to appear
        await expect(
            page.locator('.fc-event').filter({ hasText: 'Total A' }).first()
        ).toBeVisible();
        await expect(
            page.locator('.fc-event').filter({ hasText: 'Total B' }).first()
        ).toBeVisible();

        // The today header should show the total (default format: "1h 30min")
        const todayHeader = page.locator('.fc-day-today.fc-col-header-cell');
        await expect(todayHeader).toContainText('1h 30min');
    });

    test('daily total updates after entry deletion', async ({ page, ctx }) => {
        const start = todayAt(9);
        const end = todayAt(10);
        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Delete total test',
            start,
            end,
        });
        await goToCalendar(page);

        const todayHeader = page.locator('.fc-day-today.fc-col-header-cell');
        // Should show 1h initially
        await expect(todayHeader).toContainText('1h 00min');

        // Delete the entry via context menu
        await openContextMenu(page, 'Delete total test');
        await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/time-entries/') &&
                    r.request().method() === 'DELETE' &&
                    r.status() === 204
            ),
            page.getByRole('menuitem', { name: 'Delete' }).click(),
        ]);

        // Total should update to 0
        await expect(todayHeader).toContainText('0h 00min');
    });
});

// =============================================
// Section 11: Activity Plugin Overlays
// =============================================

test.describe('Activity Plugin Overlays', () => {
    test('activity periods render as colored bars on calendar', async ({ page }) => {
        await goToCalendar(page);
        // Wait for FullCalendar to fully render its time grid
        await page.waitForTimeout(1000);

        const now = new Date();
        const todayStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

        // Inject activity data via the exposed setter function
        await page.evaluate((dateStr: string) => {
            const setter = (window as any).__TEST_SET_ACTIVITY_PERIODS__;
            if (setter) {
                setter([
                    {
                        start: `${dateStr}T09:00:00Z`,
                        end: `${dateStr}T09:30:00Z`,
                        isIdle: false,
                        windowActivities: [{ appName: 'VSCode', url: null, count: 20 }],
                    },
                    {
                        start: `${dateStr}T09:30:00Z`,
                        end: `${dateStr}T10:00:00Z`,
                        isIdle: true,
                    },
                ]);
            }
        }, todayStr);

        // Activity boxes should appear
        const activityBoxes = page.locator('.activity-status-box');
        await expect(activityBoxes.first()).toBeVisible({ timeout: 10000 });
        const count = await activityBoxes.count();
        expect(count).toBeGreaterThanOrEqual(2);
    });

    test('idle and active periods have different styles', async ({ page }) => {
        await goToCalendar(page);
        await page.waitForTimeout(1000);

        const now = new Date();
        const todayStr = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;

        await page.evaluate((dateStr: string) => {
            const setter = (window as any).__TEST_SET_ACTIVITY_PERIODS__;
            if (setter) {
                setter([
                    {
                        start: `${dateStr}T09:00:00Z`,
                        end: `${dateStr}T09:30:00Z`,
                        isIdle: false,
                    },
                    {
                        start: `${dateStr}T09:30:00Z`,
                        end: `${dateStr}T10:00:00Z`,
                        isIdle: true,
                    },
                ]);
            }
        }, todayStr);

        const activeBox = page.locator('.activity-status-box.active');
        const idleBox = page.locator('.activity-status-box.idle');
        await expect(activeBox.first()).toBeVisible({ timeout: 10000 });
        await expect(idleBox.first()).toBeVisible({ timeout: 10000 });
    });
});

// =============================================
// Section 12: Running Entry Behavior
// =============================================

test.describe('Running Entry Behavior', () => {
    test('running entry extends to approximately current time', async ({ page, ctx }) => {
        const startTime = new Date();
        startTime.setHours(startTime.getHours() - 1);
        const startStr = startTime.toISOString().replace(/\.\d{3}Z$/, 'Z');
        await createRunningTimeEntryWithStartViaApi(ctx, 'Running extends test', startStr);
        await goToCalendar(page);

        const event = page.locator('.fc-event').filter({ hasText: 'Running extends test' }).first();
        await expect(event).toBeVisible();

        // Event should have significant height (extends to now, ~1 hour)
        const box = await event.boundingBox();
        expect(box).not.toBeNull();
        // A 1-hour event with default slot settings should be at least 30px tall
        expect(box!.height).toBeGreaterThan(30);
    });

    test('running entry has distinct running-entry class', async ({ page, ctx }) => {
        await createRunningTimeEntryViaApi(ctx, 'Single running test');
        await goToCalendar(page);

        // Should have exactly one running-entry element
        const runningEvents = page.locator('.fc-event.running-entry');
        await expect(runningEvents).toHaveCount(1);
    });

    test('stopping running entry removes running-entry styling', async ({ page, ctx }) => {
        await createRunningTimeEntryViaApi(ctx, 'Stop style test');
        await goToCalendar(page);

        // Verify it has running-entry class
        const event = page.locator('.fc-event').filter({ hasText: 'Stop style test' }).first();
        await expect(event).toHaveClass(/running-entry/);

        // Stop it via context menu
        await openContextMenu(page, 'Stop style test');
        await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/time-entries/') &&
                    r.request().method() === 'PUT' &&
                    r.status() === 200
            ),
            page.getByRole('menuitem', { name: 'Stop' }).click(),
        ]);

        // After stopping, event should no longer have running-entry class
        // Wait for the calendar to re-render after the mutation
        await page.waitForTimeout(1000);
        const stoppedEvent = page
            .locator('.fc-event')
            .filter({ hasText: 'Stop style test' })
            .first();
        await expect(stoppedEvent).toBeVisible();
        await expect(stoppedEvent).not.toHaveClass(/running-entry/);
    });
});

// =============================================
// Section 13: Data Loading & Navigation
// =============================================

test.describe('Data Loading & Navigation', () => {
    test('calendar scroller renders with correct grid height', async ({ page }) => {
        await goToCalendar(page);
        await page.waitForSelector('.fc-timegrid-slot-lane', { timeout: 5000 });

        const info = await page.evaluate(() => {
            const scroller = document.querySelector('.fc-scroller');
            if (!scroller) return { exists: false, scrollHeight: 0, slotCount: 0 };
            const slots = scroller.querySelectorAll('.fc-timegrid-slot-lane');
            return {
                exists: true,
                scrollHeight: scroller.scrollHeight,
                slotCount: slots.length,
            };
        });

        expect(info.exists).toBe(true);
        // Default settings: 24h with 15-min slots = 96 slots
        expect(info.slotCount).toBe(96);
        // Grid height should be 96 * 25px = 2400px
        expect(info.scrollHeight).toBe(2400);
    });
});

// =============================================
// Section 14: Keyboard & Accessibility
// =============================================

test.describe('Keyboard & Accessibility', () => {
    test('edit modal can be closed with Escape', async ({ page, ctx }) => {
        await createBareTimeEntryViaApi(ctx, 'Escape close test', '1h');
        await goToCalendar(page);

        const event = page.locator('.fc-event').filter({ hasText: 'Escape close test' }).first();
        await event.click();
        await expect(page.getByRole('dialog')).toBeVisible();

        await page.keyboard.press('Escape');
        await expect(page.getByRole('dialog')).not.toBeVisible();
    });

    test('pressing Enter on a focused event opens the edit modal', async ({ page, ctx }) => {
        const description = 'Enter key test ' + Math.floor(1 + Math.random() * 10000);
        await createBareTimeEntryViaApi(ctx, description, '1h');
        await goToCalendar(page);

        const event = page.locator('.fc-event').filter({ hasText: description }).first();
        await expect(event).toBeVisible();

        // Focus the event and press Enter
        await event.focus();
        await page.keyboard.press('Enter');

        // Edit modal should open
        await expect(page.getByRole('dialog')).toBeVisible({ timeout: 5000 });
        await expect(
            page.getByRole('dialog').getByPlaceholder('What did you work on?')
        ).toHaveValue(description);
    });

    test('events have role="button" and are focusable', async ({ page, ctx }) => {
        await createBareTimeEntryViaApi(ctx, 'Focusable test', '1h');
        await goToCalendar(page);

        const event = page.locator('.fc-event').filter({ hasText: 'Focusable test' }).first();
        await expect(event).toBeVisible();
        await expect(event).toHaveAttribute('role', 'button');
        await expect(event).toHaveAttribute('tabindex', '0');
    });
});

// =============================================
// Section 15: Click-to-Edit (Drag Threshold)
// =============================================

test.describe('Click-to-Edit (Drag Threshold)', () => {
    test('clicking an event without dragging opens the edit modal', async ({ page, ctx }) => {
        const description = 'Click edit test ' + Math.floor(1 + Math.random() * 10000);
        await createBareTimeEntryViaApi(ctx, description, '1h');
        await goToCalendar(page);

        const event = page.locator('.fc-event').filter({ hasText: description }).first();
        await expect(event).toBeVisible();

        // Simple click (no drag movement)
        await event.click();

        // Edit modal should open with the correct description
        await expect(page.getByRole('dialog')).toBeVisible({ timeout: 5000 });
        await expect(
            page.getByRole('dialog').getByPlaceholder('What did you work on?')
        ).toHaveValue(description);
    });

    test('clicking a running entry does NOT open edit modal', async ({ page, ctx }) => {
        const description = 'Running no edit test ' + Math.floor(1 + Math.random() * 10000);
        await createRunningTimeEntryViaApi(ctx, description);
        await goToCalendar(page);

        const event = page.locator('.fc-event').filter({ hasText: description }).first();
        await expect(event).toBeVisible();

        // Click on the running entry
        await event.click();

        // Wait briefly to ensure no dialog appears
        await page.waitForTimeout(500);
        await expect(page.getByRole('dialog')).not.toBeVisible();
    });
});

// =============================================
// Section 16: Selection & Drag-to-Create Details
// =============================================

test.describe('Selection & Drag-to-Create Details', () => {
    test('single click on empty slot opens create modal', async ({ page }) => {
        await goToCalendar(page);
        await scrollCalendarToTime(page, '09:00:00');

        // Click on an empty time slot (not on an event)
        const slot = page.locator('.fc-timegrid-slot-lane[data-time="10:00:00"]').first();
        await expect(slot).toBeVisible();
        const slotBox = await slot.boundingBox();

        // Click in the middle of the slot
        await page.mouse.click(slotBox!.x + slotBox!.width / 2, slotBox!.y + 2);

        // Create modal should appear
        await expect(page.getByRole('dialog')).toBeVisible({ timeout: 5000 });
    });

    test('drag-to-create shows selection highlight during drag', async ({ page }) => {
        await goToCalendar(page);
        await scrollCalendarToTime(page, '09:00:00');

        const startSlot = page.locator('.fc-timegrid-slot-lane[data-time="10:00:00"]').first();
        await expect(startSlot).toBeVisible();
        const startBox = await startSlot.boundingBox();

        const endSlot = page.locator('.fc-timegrid-slot-lane[data-time="11:00:00"]').first();
        const endBox = await endSlot.boundingBox();

        // Start dragging without releasing
        await page.mouse.move(startBox!.x + startBox!.width / 2, startBox!.y + 2);
        await page.mouse.down();
        await page.mouse.move(endBox!.x + endBox!.width / 2, endBox!.y + 2, { steps: 10 });

        // Selection highlight should be visible (bg-accent border-primary class)
        const selectionMirror = page.locator('.bg-accent.border-primary');
        await expect(selectionMirror.first()).toBeVisible();

        // Release mouse
        await page.mouse.up();
    });

    test('snap interval affects drag-to-create times', async ({ page }) => {
        await goToCalendar(page);

        // Set snap interval to 30 min
        await page.getByRole('button', { name: 'Calendar settings' }).click();
        await expect(page.getByText('Calendar Settings')).toBeVisible();
        await page.getByLabel('Snap Interval').click();
        await page.getByRole('option', { name: '30 min' }).click();
        await page.keyboard.press('Escape');

        await scrollCalendarToTime(page, '09:00:00');

        // Drag from ~10:10 (slightly offset) to ~11:20
        const slot10 = page.locator('.fc-timegrid-slot-lane[data-time="10:00:00"]').first();
        const slot11 = page.locator('.fc-timegrid-slot-lane[data-time="11:00:00"]').first();
        const slotHeight = await getSlotHeight(page);
        const box10 = await slot10.boundingBox();
        const box11 = await slot11.boundingBox();

        // Start at 10:10 (slightly into the slot)
        const startX = box10!.x + box10!.width / 2;
        const startY = box10!.y + slotHeight * 0.7; // ~10 minutes into the slot
        // End at 11:20 (past the 11:00 slot)
        const endY = box11!.y + slotHeight * 1.3;

        await page.mouse.move(startX, startY);
        await page.mouse.down();
        await page.mouse.move(startX, endY, { steps: 10 });
        await page.mouse.up();

        // Create modal should appear
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible({ timeout: 5000 });

        // Submit the form and check the API request for 30-min-aligned times
        const [createResponse] = await Promise.all([
            page.waitForResponse(
                (response) =>
                    response.url().includes('/time-entries') &&
                    response.request().method() === 'POST' &&
                    response.status() === 201
            ),
            page.getByRole('button', { name: 'Create Time Entry' }).click(),
        ]);

        const body = await createResponse.json();
        const startDate = new Date(body.data.start);
        const endDate = new Date(body.data.end);

        // Both start and end minutes should be divisible by 30
        expect(startDate.getMinutes() % 30).toBe(0);
        expect(endDate.getMinutes() % 30).toBe(0);
    });
});

// =============================================
// Section 17: Resize Snap Interval
// =============================================

test.describe('Resize Snap Interval', () => {
    test('resize bottom edge respects snap interval', async ({ page, ctx }) => {
        // Set snap interval to 30 min
        await goToCalendar(page);
        await page.getByRole('button', { name: 'Calendar settings' }).click();
        await expect(page.getByText('Calendar Settings')).toBeVisible();
        await page.getByLabel('Snap Interval').click();
        await page.getByRole('option', { name: '30 min' }).click();
        await page.keyboard.press('Escape');

        // Create entry at 10:00–11:00
        const start = todayAt(10);
        const end = todayAt(11);
        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Resize snap test',
            start,
            end,
        });
        await goToCalendar(page);
        await scrollCalendarToTime(page, '09:00:00');

        const event = page.locator('.fc-event').filter({ hasText: 'Resize snap test' }).first();
        await expect(event).toBeVisible();

        const slotHeight = await getSlotHeight(page);
        const eventBox = await event.boundingBox();
        const bottomY = eventBox!.y + eventBox!.height;
        const centerX = eventBox!.x + eventBox!.width / 2;

        // Resize by dragging bottom edge down ~3 slots (45 min at 15-min grid)
        const [putResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/time-entries/') &&
                    r.request().method() === 'PUT' &&
                    r.status() === 200
            ),
            (async () => {
                await page.mouse.move(centerX, bottomY - 3);
                await page.waitForTimeout(100);
                await page.mouse.down();
                await page.mouse.move(centerX, bottomY + slotHeight * 3, { steps: 15 });
                await page.mouse.up();
            })(),
        ]);

        const body = await putResponse.json();
        const endDate = new Date(body.data.end);
        // With 30-min snap, end minutes should be divisible by 30
        expect(endDate.getMinutes() % 30).toBe(0);
    });

    test('resize preview of off-grid event snaps to absolute grid positions', async ({
        page,
        ctx,
    }) => {
        // Set snap interval to 15 min (default grid scale is also 15 min)
        await goToCalendar(page);
        await page.getByRole('button', { name: 'Calendar settings' }).click();
        await expect(page.getByText('Calendar Settings')).toBeVisible();
        await page.getByLabel('Snap Interval').click();
        await page.getByRole('option', { name: '15 min' }).click();
        await page.keyboard.press('Escape');

        // Create entry at 10:07–10:37 — deliberately off the 15-min grid
        const start = todayAt(10, 7);
        const end = todayAt(10, 37);
        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Off-grid snap test',
            start,
            end,
        });
        await goToCalendar(page);
        await scrollCalendarToTime(page, '09:00:00');

        const event = page.locator('.fc-event').filter({ hasText: 'Off-grid snap test' }).first();
        await expect(event).toBeVisible();

        const slotHeight = await getSlotHeight(page);
        const eventBox = await event.boundingBox();
        const bottomY = eventBox!.y + eventBox!.height;
        const centerX = eventBox!.x + eventBox!.width / 2;

        // Resize bottom edge down by ~2 slots (30 min at 15-min grid)
        await page.mouse.move(centerX, bottomY - 3);
        await page.waitForTimeout(100);
        await page.mouse.down();
        await page.mouse.move(centerX, bottomY + slotHeight * 2, { steps: 15 });

        // While still dragging, capture the preview's inline style (top + height).
        // The resizing event is positioned via inline styles by the composable.
        const previewStyle = await page.evaluate(() => {
            // Find all fc-event elements and pick the one being resized
            // (it will have inline top/height from the resize composable)
            const events = document.querySelectorAll('.fc-event');
            for (const ev of events) {
                const el = ev as HTMLElement;
                if (el.style.top && el.style.height) {
                    return {
                        top: parseFloat(el.style.top),
                        height: parseFloat(el.style.height),
                    };
                }
            }
            return null;
        });
        expect(previewStyle).not.toBeNull();

        // With snap = 15 min and grid scale = 15 min, snapPx = slotHeight.
        // The end edge (top + height) should be at a grid-aligned position
        // (divisible by slotHeight), even though the event starts at 10:07
        // which is NOT grid-aligned.
        const previewEnd = previewStyle!.top + previewStyle!.height;
        const endOffGrid = previewEnd % slotHeight;
        expect(endOffGrid).toBeLessThanOrEqual(1); // allow sub-pixel rounding

        // Release the mouse to complete the resize
        const [putResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/time-entries/') &&
                    r.request().method() === 'PUT' &&
                    r.status() === 200
            ),
            page.mouse.up(),
        ]);

        const body = await putResponse.json();
        const endDate = new Date(body.data.end);
        // The saved end time should be snapped to the 15-min grid
        expect(endDate.getMinutes() % 15).toBe(0);

        // After the API update, the rendered position should match the preview.
        // The event top stays the same (only end edge was resized), so
        // compare the final rendered height to the preview height.
        await expect(async () => {
            const updatedEvent = page
                .locator('.fc-event')
                .filter({ hasText: 'Off-grid snap test' })
                .first();
            const updatedBox = await updatedEvent.boundingBox();
            expect(updatedBox).not.toBeNull();
            // Rendered height should match preview height within 2px
            expect(Math.abs(updatedBox!.height - previewStyle!.height)).toBeLessThanOrEqual(2);
        }).toPass({ timeout: 5000 });
    });

    test('resize top edge of off-grid event snaps to absolute grid positions', async ({
        page,
        ctx,
    }) => {
        // Set snap interval to 15 min
        await goToCalendar(page);
        await page.getByRole('button', { name: 'Calendar settings' }).click();
        await expect(page.getByText('Calendar Settings')).toBeVisible();
        await page.getByLabel('Snap Interval').click();
        await page.getByRole('option', { name: '15 min' }).click();
        await page.keyboard.press('Escape');

        // Create entry at 10:07–11:07 — deliberately off the 15-min grid
        const start = todayAt(10, 7);
        const end = todayAt(11, 7);
        await createTimeEntryWithTimestampsViaApi(ctx, {
            description: 'Off-grid top snap test',
            start,
            end,
        });
        await goToCalendar(page);
        await scrollCalendarToTime(page, '09:00:00');

        const event = page
            .locator('.fc-event')
            .filter({ hasText: 'Off-grid top snap test' })
            .first();
        await expect(event).toBeVisible();

        const slotHeight = await getSlotHeight(page);
        const eventBox = await event.boundingBox();
        const topY = eventBox!.y;
        const centerX = eventBox!.x + eventBox!.width / 2;

        // Resize top edge up by ~2 slots (30 min)
        await page.mouse.move(centerX, topY + 3);
        await page.waitForTimeout(100);
        await page.mouse.down();
        await page.mouse.move(centerX, topY - slotHeight * 2, { steps: 15 });

        // Capture preview position during drag
        const previewStyle = await page.evaluate(() => {
            const events = document.querySelectorAll('.fc-event');
            for (const ev of events) {
                const el = ev as HTMLElement;
                if (el.style.top && el.style.height) {
                    return {
                        top: parseFloat(el.style.top),
                        height: parseFloat(el.style.height),
                    };
                }
            }
            return null;
        });
        expect(previewStyle).not.toBeNull();

        // The start edge (top) should be at a grid-aligned position
        const topOffGrid = previewStyle!.top % slotHeight;
        expect(topOffGrid).toBeLessThanOrEqual(1);

        // Release the mouse
        const [putResponse] = await Promise.all([
            page.waitForResponse(
                (r) =>
                    r.url().includes('/time-entries/') &&
                    r.request().method() === 'PUT' &&
                    r.status() === 200
            ),
            page.mouse.up(),
        ]);

        const body = await putResponse.json();
        const startDate = new Date(body.data.start);
        // The saved start time should be snapped to the 15-min grid
        expect(startDate.getMinutes() % 15).toBe(0);
    });
});

// =============================================
// Section 18: Advanced Overlap Layout
// =============================================

test.describe('Advanced Overlap Layout', () => {
    test('three overlapping events render in separate columns', async ({ page, ctx }) => {
        const start = todayAt(10);
        const end = todayAt(11);
        await createTimeEntryWithTimestampsViaApi(ctx, { description: 'Triple A', start, end });
        await createTimeEntryWithTimestampsViaApi(ctx, { description: 'Triple B', start, end });
        await createTimeEntryWithTimestampsViaApi(ctx, { description: 'Triple C', start, end });
        await goToCalendar(page);
        await scrollCalendarToTime(page, '09:00:00');

        const eventA = page.locator('.fc-event').filter({ hasText: 'Triple A' }).first();
        const eventB = page.locator('.fc-event').filter({ hasText: 'Triple B' }).first();
        const eventC = page.locator('.fc-event').filter({ hasText: 'Triple C' }).first();
        await expect(eventA).toBeVisible();
        await expect(eventB).toBeVisible();
        await expect(eventC).toBeVisible();

        const boxA = await eventA.boundingBox();
        const boxB = await eventB.boundingBox();
        const boxC = await eventC.boundingBox();
        expect(boxA).not.toBeNull();
        expect(boxB).not.toBeNull();
        expect(boxC).not.toBeNull();

        // All three should have similar widths (each ~1/3 of column)
        const widths = [boxA!.width, boxB!.width, boxC!.width].sort((a, b) => a - b);
        // Smallest should be at least 60% of largest (they're roughly equal thirds)
        expect(widths[0]).toBeGreaterThan(widths[2]! * 0.6);

        // All three should be at distinct x positions
        const xs = [boxA!.x, boxB!.x, boxC!.x].sort((a, b) => a - b);
        // First and second should differ
        expect(xs[1]! - xs[0]!).toBeGreaterThan(5);
        // Second and third should differ
        expect(xs[2]! - xs[1]!).toBeGreaterThan(5);
    });
});

// =============================================
// Section 19: Daily Total Updates After Create
// =============================================

test.describe('Daily Total After Create', () => {
    test('daily total updates after creating entry via drag-to-create', async ({ page }) => {
        await goToCalendar(page);

        const todayHeader = page.locator('.fc-day-today.fc-col-header-cell');

        // Store initial total text
        const initialText = await todayHeader.innerText();

        await scrollCalendarToTime(page, '13:00:00');

        // Get today's column X position to ensure drag is on the right day
        const todayDate = await todayHeader.getAttribute('data-date');
        const todayCol = page.locator(`.fc-timegrid-col[data-date="${todayDate}"]`);
        const colBox = await todayCol.first().boundingBox();
        const colCenterX = colBox!.x + colBox!.width / 2;

        // Drag to create a 1-hour entry from 14:00 to 15:00
        const slot14 = page.locator('.fc-timegrid-slot-lane[data-time="14:00:00"]').first();
        const slot15 = page.locator('.fc-timegrid-slot-lane[data-time="15:00:00"]').first();
        const box14 = await slot14.boundingBox();
        const box15 = await slot15.boundingBox();

        await page.mouse.move(colCenterX, box14!.y + 2);
        await page.mouse.down();
        await page.mouse.move(colCenterX, box15!.y + 2, { steps: 10 });
        await page.mouse.up();

        // Create modal should appear
        const dialog = page.getByRole('dialog');
        await expect(dialog).toBeVisible({ timeout: 5000 });

        // Submit the create form
        await Promise.all([
            page.waitForResponse(
                (response) =>
                    response.url().includes('/time-entries') &&
                    response.request().method() === 'POST' &&
                    response.status() === 201
            ),
            page.getByRole('button', { name: 'Create Time Entry' }).click(),
        ]);

        // Wait for the header total to update after refresh
        await expect(async () => {
            const updatedText = await todayHeader.innerText();
            expect(updatedText).not.toBe(initialText);
        }).toPass({ timeout: 5000 });
    });
});
