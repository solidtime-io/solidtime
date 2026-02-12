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
import {
    createBareTimeEntryViaApi,
    createPublicProjectViaApi,
    createTimeEntryViaApi,
    updateOrganizationSettingViaApi,
} from './utils/api';

async function goToDashboard(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
}

test('test that dashboard loads with all expected sections', async ({ page }) => {
    await goToDashboard(page);
    await expect(page.getByTestId('dashboard_view')).toBeVisible({ timeout: 10000 });

    // Timer section (scoped to dashboard_timer to avoid matching sidebar timer)
    await expect(page.getByTestId('time_entry_description')).toBeVisible();
    await expect(
        page
            .getByTestId('dashboard_timer')
            .getByTestId('timer_button')
            .and(page.locator(':visible'))
    ).toBeVisible();

    // Dashboard cards
    await expect(page.getByText('Recent Time Entries', { exact: true })).toBeVisible();
    await expect(page.getByText('Last 7 Days', { exact: true })).toBeVisible();
    await expect(page.getByText('Activity Graph', { exact: true })).toBeVisible();
    await expect(page.getByText('Team Activity', { exact: true })).toBeVisible();

    // Weekly overview section
    await expect(page.getByText('This Week', { exact: true })).toBeVisible();
});

test('test that dashboard shows time entry data after creating entries', async ({ page, ctx }) => {
    await createBareTimeEntryViaApi(ctx, 'Dashboard test entry', '1h');

    await goToDashboard(page);
    await expect(page.getByTestId('dashboard_view')).toBeVisible();

    // The "Last 7 Days" or "This Week" section should reflect tracked time
    await expect(page.getByText('This Week', { exact: true })).toBeVisible();
});

test('test that timer on dashboard can start and stop', async ({ page }) => {
    await goToDashboard(page);
    await Promise.all([newTimeEntryResponse(page), startOrStopTimerWithButton(page)]);
    await assertThatTimerHasStarted(page);

    await page.waitForTimeout(1500);

    await Promise.all([stoppedTimeEntryResponse(page), startOrStopTimerWithButton(page)]);
    await assertThatTimerIsStopped(page);
});

test('test that weekly overview section displays stat cards', async ({ page, ctx }) => {
    await createBareTimeEntryViaApi(ctx, 'Stats test entry', '2h');

    await goToDashboard(page);

    // Verify stat card labels are visible
    await expect(page.getByText('Spent Time')).toBeVisible();
    await expect(page.getByText('Billable Time')).toBeVisible();
    await expect(page.getByText('Billable Amount')).toBeVisible();
});

test('test that stopping timer refreshes dashboard data', async ({ page }) => {
    await goToDashboard(page);

    // Start timer
    await Promise.all([newTimeEntryResponse(page), startOrStopTimerWithButton(page)]);
    await assertThatTimerHasStarted(page);
    await page.waitForTimeout(1500);

    // Stop timer and verify dashboard queries are refetched
    await Promise.all([
        stoppedTimeEntryResponse(page),
        page.waitForResponse(
            (response) =>
                response.url().includes('/charts/') &&
                response.request().method() === 'GET' &&
                response.status() === 200
        ),
        startOrStopTimerWithButton(page),
    ]);
    await assertThatTimerIsStopped(page);
});

// =============================================
// Employee Permission Tests
// =============================================

test.describe('Employee Dashboard Restrictions', () => {
    test('employee dashboard loads and timer is functional', async ({ employee }) => {
        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
        await expect(employee.page.getByTestId('dashboard_view')).toBeVisible({
            timeout: 10000,
        });

        // Timer should be available
        await expect(
            employee.page
                .getByTestId('dashboard_timer')
                .getByTestId('timer_button')
                .and(employee.page.locator(':visible'))
        ).toBeVisible();
        await expect(employee.page.getByTestId('time_entry_description')).toBeEditable();
    });

    test('employee cannot see Team Activity card', async ({ employee }) => {
        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
        await expect(employee.page.getByTestId('dashboard_view')).toBeVisible({
            timeout: 10000,
        });

        // Other dashboard cards should be visible
        await expect(employee.page.getByText('Recent Time Entries', { exact: true })).toBeVisible();

        // Team Activity should NOT be visible for employees
        await expect(employee.page.getByText('Team Activity', { exact: true })).not.toBeVisible();
    });

    test('employee cannot see Cost column in This Week table by default', async ({
        ctx,
        employee,
    }) => {
        const project = await createPublicProjectViaApi(ctx, {
            name: 'EmpDashBillProj',
            is_billable: true,
            billable_rate: 10000,
        });
        await createTimeEntryViaApi(
            { ...ctx, memberId: employee.memberId },
            {
                description: 'Emp dashboard cost entry',
                duration: '1h',
                projectId: project.id,
                billable: true,
            }
        );

        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
        await expect(employee.page.getByTestId('dashboard_view')).toBeVisible({
            timeout: 10000,
        });

        // This Week table should be visible
        await expect(employee.page.getByText('This Week', { exact: true })).toBeVisible();

        // Duration column should be visible, but Cost column should NOT
        await expect(employee.page.getByText('Duration', { exact: true })).toBeVisible();
        await expect(employee.page.getByText('Cost', { exact: true })).not.toBeVisible();
    });

    test('employee can see Cost column in This Week table when employees_can_see_billable_rates is enabled', async ({
        ctx,
        employee,
    }) => {
        await updateOrganizationSettingViaApi(ctx, { employees_can_see_billable_rates: true });

        const project = await createPublicProjectViaApi(ctx, {
            name: 'EmpDashBillVisProj',
            is_billable: true,
            billable_rate: 10000,
        });
        await createTimeEntryViaApi(
            { ...ctx, memberId: employee.memberId },
            {
                description: 'Emp dashboard cost visible entry',
                duration: '1h',
                projectId: project.id,
                billable: true,
            }
        );

        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
        await expect(employee.page.getByTestId('dashboard_view')).toBeVisible({
            timeout: 10000,
        });

        // Both Duration and Cost columns should be visible
        await expect(employee.page.getByText('Duration', { exact: true })).toBeVisible();
        await expect(employee.page.getByText('Cost', { exact: true })).toBeVisible();

        // 1h at 100.00/h = 100.00 EUR cost should be visible
        await expect(employee.page.getByText('100,00 EUR').first()).toBeVisible();
    });
});
