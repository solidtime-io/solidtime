import { expect, test } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import type { Page } from '@playwright/test';
import {
    assertThatTimerHasStarted,
    assertThatTimerIsStopped,
    newTimeEntryResponse,
    startOrStopTimerWithButton,
    stoppedTimeEntryResponse,
} from './utils/currentTimeEntry';
import { createTimeEntryViaApi, updateOrganizationSettingViaApi } from './utils/api';

async function goToDashboard(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
}

function visibleBreakButton(page: Page) {
    return page.getByRole('button', { name: 'Take a break' }).locator('visible=true').first();
}

// Breaks are disabled by default for new organizations, so enable them for the break flows.
// The tests that assert the disabled behaviour turn them back off explicitly.
test.beforeEach(async ({ ctx }) => {
    await updateOrganizationSettingViaApi(ctx, { breaks_enabled: true });
});

test('test that switching to a break stops the work timer and starts a break entry', async ({
    page,
}) => {
    await goToDashboard(page);
    await expect(page.getByTestId('time_entry_description')).toBeEditable();
    await page.getByTestId('time_entry_description').fill('Work before break');
    await Promise.all([
        newTimeEntryResponse(page, { description: 'Work before break', type: 'work' }),
        page.getByTestId('time_entry_description').press('Enter'),
    ]);
    await assertThatTimerHasStarted(page);
    await page.waitForTimeout(1500);

    // Switch to break: stops the work entry and starts a break entry
    await Promise.all([
        newTimeEntryResponse(page, { description: '', type: 'break' }),
        visibleBreakButton(page).click(),
    ]);
    await expect(page.getByText('On break')).toBeVisible();

    // The break bar offers a one-click resume that stops the break and restores
    // the interrupted work context
    await page.waitForTimeout(1500);
    const resumeButton = page.getByRole('button', { name: 'Resume "Work before break"' });
    await expect(resumeButton).toBeVisible();
    await Promise.all([
        stoppedTimeEntryResponse(page, { type: 'break' }),
        newTimeEntryResponse(page, { description: 'Work before break', type: 'work' }),
        resumeButton.click(),
    ]);
    await assertThatTimerHasStarted(page);
    await expect(page.getByTestId('time_entry_description')).toHaveValue('Work before break');

    // Cleanup: stop the running entry
    await Promise.all([
        stoppedTimeEntryResponse(page, { description: 'Work before break', type: 'work' }),
        startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerIsStopped(page);
});

test('test that stopping a break returns to an idle tracker where a fresh entry starts normally', async ({
    page,
}) => {
    await goToDashboard(page);
    await expect(page.getByTestId('time_entry_description')).toBeEditable();
    await page.getByTestId('time_entry_description').fill('Work before break');
    await Promise.all([
        newTimeEntryResponse(page, { description: 'Work before break', type: 'work' }),
        page.getByTestId('time_entry_description').press('Enter'),
    ]);
    await assertThatTimerHasStarted(page);
    await page.waitForTimeout(1500);

    // Switch to a break
    await Promise.all([
        newTimeEntryResponse(page, { description: '', type: 'break' }),
        visibleBreakButton(page).click(),
    ]);
    await expect(page.getByText('On break')).toBeVisible();

    // Stopping the break just ends it — no modal, the tracker returns to the
    // empty idle input with focus so typing starts a fresh entry
    await page.waitForTimeout(1500);
    await Promise.all([
        stoppedTimeEntryResponse(page, { type: 'break' }),
        startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerIsStopped(page);
    await expect(page.getByTestId('time_entry_description')).toHaveValue('');
    await expect(page.getByTestId('time_entry_description')).toBeFocused();

    // A fresh entry is the normal start flow: type + Enter
    await page.getByTestId('time_entry_description').fill('Fresh after break');
    await Promise.all([
        newTimeEntryResponse(page, { description: 'Fresh after break', type: 'work' }),
        page.getByTestId('time_entry_description').press('Enter'),
    ]);
    await assertThatTimerHasStarted(page);

    // Cleanup: stop the running entry
    await Promise.all([
        stoppedTimeEntryResponse(page, { description: 'Fresh after break', type: 'work' }),
        startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerIsStopped(page);
});

test('test that the more options dropdown can start a break directly', async ({ page }) => {
    await goToDashboard(page);
    await expect(page.getByTestId('time_entry_description')).toBeEditable();

    // Start a break straight from the more options dropdown (no create modal)
    await page.getByRole('button', { name: 'Time entry actions' }).click();
    await Promise.all([
        newTimeEntryResponse(page, { description: '', type: 'break' }),
        page.getByRole('menuitem', { name: 'Start Break' }).click(),
    ]);
    await expect(page.getByText('On break')).toBeVisible();

    // Without interrupted work there is nothing to resume, so no resume button is offered
    await expect(page.getByRole('button', { name: /^Resume/ })).toHaveCount(0);

    // Cleanup: stop the break
    await page.waitForTimeout(1500);
    await Promise.all([
        stoppedTimeEntryResponse(page, { type: 'break' }),
        startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerIsStopped(page);
});

test('test that disabling breaks hides every break-creation entry point', async ({ page, ctx }) => {
    // Breaks disabled for the organization (delivered to the client via the organization endpoint)
    await updateOrganizationSettingViaApi(ctx, { breaks_enabled: false });
    await createTimeEntryViaApi(ctx, { duration: '1h', description: 'Regular work' });

    // Calendar: the empty-slot context menu offers "Create Time Entry" but no "Add Break",
    // and the edit modal drops the work-time/break type selector
    await page.goto(PLAYWRIGHT_BASE_URL + '/calendar');
    await expect(page.locator('.fc')).toBeVisible();
    const event = page.locator('.fc-event').filter({ hasText: 'Regular work' }).first();
    await event.scrollIntoViewIfNeeded();
    await expect(event).toBeVisible();

    const box = await event.boundingBox();
    expect(box).not.toBeNull();
    await page.mouse.click(box!.x + box!.width / 2, box!.y + box!.height + 40, { button: 'right' });
    await expect(page.getByRole('menu')).toBeVisible();
    await expect(page.getByRole('menuitem', { name: 'Create Time Entry' })).toBeVisible();
    await expect(page.getByRole('menuitem', { name: 'Add Break' })).toHaveCount(0);
    await page.keyboard.press('Escape');

    await event.click({ button: 'right' });
    await expect(page.getByRole('menu')).toBeVisible();
    await page.getByRole('menuitem', { name: 'Edit' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();
    await expect(
        page.getByRole('dialog').getByRole('combobox').filter({ hasText: 'Work time' })
    ).toHaveCount(0);
    await page.keyboard.press('Escape');

    // Timesheet: no break row is shown
    await page.goto(PLAYWRIGHT_BASE_URL + '/timesheet');
    await expect(page.getByRole('button', { name: 'Add row' }).first()).toBeVisible();
    await expect(page.getByText('Break', { exact: true })).toHaveCount(0);

    // Dashboard tracker: no "Start Break" in the more options dropdown
    await goToDashboard(page);
    await expect(page.getByTestId('time_entry_description')).toBeEditable();
    await page.getByRole('button', { name: 'Time entry actions' }).click();
    await expect(page.getByRole('menuitem', { name: 'Switch to simple mode' })).toBeVisible();
    await expect(page.getByRole('menuitem', { name: 'Start Break' })).toHaveCount(0);
});

// The employee fixture registers a second user and accepts an invitation via Mailpit,
// which does not fit into the default per-test timeout.
test.describe('Org-level breaks setting', () => {
    test.describe.configure({ timeout: 60000 });

    test('test that the org-level breaks setting is respected for employees', async ({
        ctx,
        employee,
    }) => {
        const employeePage = employee.page;

        // Breaks enabled (via beforeEach): the employee sees "Start Break" in the more options dropdown
        await employeePage.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
        await expect(employeePage.getByTestId('dashboard_view')).toBeVisible();
        await employeePage.getByRole('button', { name: 'Time entry actions' }).click();
        await expect(
            employeePage.getByRole('menuitem', { name: 'Switch to simple mode' })
        ).toBeVisible();
        await expect(employeePage.getByRole('menuitem', { name: 'Start Break' })).toBeVisible();
        await employeePage.keyboard.press('Escape');

        // The owner disables breaks for the whole organization
        await updateOrganizationSettingViaApi(ctx, { breaks_enabled: false });

        // The employee reloads: "Start Break" is gone from the dropdown
        await employeePage.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
        await expect(employeePage.getByTestId('dashboard_view')).toBeVisible();
        await employeePage.getByRole('button', { name: 'Time entry actions' }).click();
        await expect(
            employeePage.getByRole('menuitem', { name: 'Switch to simple mode' })
        ).toBeVisible();
        await expect(employeePage.getByRole('menuitem', { name: 'Start Break' })).toHaveCount(0);
        await employeePage.keyboard.press('Escape');

        // With an active timer the break (coffee) button is not shown either
        await employeePage.getByTestId('time_entry_description').fill('Employee work');
        await Promise.all([
            newTimeEntryResponse(employeePage, { description: 'Employee work', type: 'work' }),
            employeePage.getByTestId('time_entry_description').press('Enter'),
        ]);
        await assertThatTimerHasStarted(employeePage);
        await expect(employeePage.getByRole('button', { name: 'Take a break' })).toHaveCount(0);

        // Cleanup: stop the running entry
        await Promise.all([
            stoppedTimeEntryResponse(employeePage, { description: 'Employee work', type: 'work' }),
            startOrStopTimerWithButton(employeePage),
        ]);
        await assertThatTimerIsStopped(employeePage);
    });
});

test('test that mass update warns about selected breaks and reports skipped entries instead of success', async ({
    page,
    ctx,
}) => {
    // One work entry and one break: a billable mass update applies to the work
    // entry but the server skips the break entirely — the UI must say so.
    await createTimeEntryViaApi(ctx, { duration: '1h', description: 'Mass update work entry' });
    await createTimeEntryViaApi(ctx, { duration: '30min', type: 'break' });

    await page.goto(PLAYWRIGHT_BASE_URL + '/time');
    await expect(page.locator('[data-testid="time_entry_row"]')).toHaveCount(2);
    await page.getByLabel('Select All').click();
    await expect(page.getByText('2 selected')).toBeVisible();
    await page.getByRole('button', { name: 'Edit' }).click();
    await expect(page.getByRole('dialog')).toBeVisible();

    // No warning while the changeset is compatible with breaks
    await expect(page.getByTestId('mass_update_break_warning')).not.toBeVisible();

    // Making the entries billable is break-incompatible → warning appears
    await page
        .getByRole('dialog')
        .getByRole('combobox')
        .filter({ hasText: 'Set billable status' })
        .click();
    await page.getByRole('option', { name: 'Billable', exact: true }).click();
    await expect(page.getByTestId('mass_update_break_warning')).toBeVisible();
    await expect(page.getByTestId('mass_update_break_warning')).toContainText('skipped entirely');

    // Submit: the work entry updates, the break is skipped, and the toast
    // reports the skip instead of claiming success for all entries
    const [massUpdateResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries') &&
                response.request().method() === 'PATCH' &&
                response.status() === 200
        ),
        page.getByRole('button', { name: 'Update Time Entries' }).click(),
    ]);
    const massUpdateBody = await massUpdateResponse.json();
    expect(massUpdateBody.success.length).toBe(1);
    expect(massUpdateBody.error.length).toBe(1);
    await expect(page.getByText('1 of 2 time entries was skipped')).toBeVisible();
});
