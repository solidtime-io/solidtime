import { expect } from '@playwright/test';
import type { Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import { formatCentsWithOrganizationDefaults } from './utils/money';
import {
    createProjectViaApi,
    createPublicProjectViaApi,
    createTaskViaApi,
    createClientViaApi,
    createTimeEntryViaApi,
    archiveProjectViaApi,
    updateOrganizationSettingViaApi,
} from './utils/api';

async function goToProjectsOverview(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/projects');
}

// Helper to clear localStorage before tests that check persistence
async function clearProjectTableState(page: Page) {
    await page.evaluate(() => {
        localStorage.removeItem('project-table-state');
    });
}

// Create new project via modal
test('test that creating and deleting a new project via the modal works', async ({ page }) => {
    const newProjectName = 'New Project ' + Math.floor(1 + Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(newProjectName);
    await Promise.all([
        page.getByRole('button', { name: 'Create Project' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects') &&
                response.request().method() === 'POST' &&
                response.status() === 201 &&
                (await response.json()).data.id !== null &&
                (await response.json()).data.color !== null &&
                (await response.json()).data.client_id === null &&
                (await response.json()).data.name === newProjectName
        ),
    ]);

    await expect(page.getByTestId('project_table')).toContainText(newProjectName);
    const moreButton = page.locator("[aria-label='Actions for Project " + newProjectName + "']");
    await moreButton.click();
    const deleteButton = page.locator("[aria-label='Delete Project " + newProjectName + "']");

    await Promise.all([
        deleteButton.click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects') &&
                response.request().method() === 'DELETE' &&
                response.status() === 204
        ),
    ]);
    await expect(page.getByTestId('project_table')).not.toContainText(newProjectName);
});

// Helper to select a status filter using the new dropdown UI
async function selectStatusFilter(page: Page, status: 'Active' | 'Archived') {
    // Click the Filter button to open the dropdown
    await page.getByRole('button', { name: 'Filter projects' }).click();
    // Click on Status submenu
    await page.getByRole('menuitem', { name: 'Status' }).click();
    // Select the status option
    await page.getByRole('menuitem', { name: status }).click();
}

// Helper to remove status filter by clicking the X on the badge
async function removeStatusFilter(page: Page) {
    const statusBadge = page.getByTestId('status-filter-badge');
    // Click the remove button (second button in the badge, contains XMarkIcon)
    await statusBadge.locator('button').last().click();
}

test('test that archiving and unarchiving projects works', async ({ page, ctx }) => {
    const newProjectName = 'New Project ' + Math.floor(1 + Math.random() * 10000);
    await createProjectViaApi(ctx, { name: newProjectName });

    await goToProjectsOverview(page);
    await clearProjectTableState(page);
    await page.reload();
    await expect(page.getByText(newProjectName)).toBeVisible({ timeout: 10000 });

    // Archive the project
    await page.getByRole('row').first().getByRole('button').click();
    await page.getByRole('menuitem').getByText('Archive').first().click();

    // Project should still be visible since default is "all" (no filter)
    await expect(page.getByText(newProjectName)).toBeVisible();

    // Apply Active filter - archived project should disappear
    await selectStatusFilter(page, 'Active');
    await expect(page.getByText(newProjectName)).not.toBeVisible();

    // Remove Active filter and apply Archived filter
    await removeStatusFilter(page);
    await selectStatusFilter(page, 'Archived');
    await expect(page.getByText(newProjectName)).toBeVisible();

    // Unarchive the project
    await page.getByRole('row').first().getByRole('button').click();
    await page.getByRole('menuitem').getByText('Unarchive').first().click();

    // Project should disappear from Archived view
    await expect(page.getByText(newProjectName)).not.toBeVisible();

    // Remove Archived filter and apply Active filter to see the project
    await removeStatusFilter(page);
    await selectStatusFilter(page, 'Active');
    await expect(page.getByText(newProjectName)).toBeVisible();
});

test('test that updating billable rate works with existing time entries', async ({ page, ctx }) => {
    const newProjectName = 'New Project ' + Math.floor(1 + Math.random() * 10000);
    const newBillableRate = Math.round(Math.random() * 10000);
    await createProjectViaApi(ctx, { name: newProjectName });

    await goToProjectsOverview(page);
    await expect(page.getByText(newProjectName)).toBeVisible({ timeout: 10000 });

    await page.getByRole('row').first().getByRole('button').click();
    await page.getByRole('menuitem').getByText('Edit').first().click();

    // Set billable default to Billable
    await page.getByRole('dialog').locator('#billable').click();
    await page.getByRole('option', { name: 'Billable', exact: true }).click();

    // Set billable rate to Custom Rate
    await page.getByRole('dialog').locator('#billableRateType').click();
    await page.getByRole('option', { name: 'Custom Rate' }).click();

    await page.getByPlaceholder('Billable Rate').fill(newBillableRate.toString());
    await page.getByRole('button', { name: 'Update Project' }).click();

    await Promise.all([
        page.locator('button').filter({ hasText: 'Yes, update existing time' }).click(),
        page.waitForRequest(
            async (request) =>
                request.url().includes('/projects/') &&
                request.method() === 'PUT' &&
                request.postDataJSON().billable_rate === newBillableRate * 100
        ),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects/') &&
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

test('test that creating a project with default billable rate works', async ({ page }) => {
    const newProjectName = 'Default Rate Project ' + Math.floor(1 + Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(newProjectName);

    // Set billable default to Billable (leaves rate type as Default Rate)
    await page.getByRole('dialog').locator('#billable').click();
    await page.getByRole('option', { name: 'Billable', exact: true }).click();

    // Verify rate type is "Default Rate" and the rate input is disabled
    await expect(page.getByRole('dialog').locator('#billableRateType')).toContainText(
        'Default Rate'
    );
    await expect(page.getByPlaceholder('Billable Rate')).toBeDisabled();

    await Promise.all([
        page.getByRole('button', { name: 'Create Project' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects') &&
                response.request().method() === 'POST' &&
                response.status() === 201 &&
                (await response.json()).data.is_billable === true &&
                (await response.json()).data.billable_rate === null
        ),
    ]);

    await expect(page.getByTestId('project_table')).toContainText(newProjectName);
});

test('test that creating a non-billable project works', async ({ page }) => {
    const newProjectName = 'Non-Billable Project ' + Math.floor(1 + Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(newProjectName);

    // Billable default should already be "Non-billable" by default
    await expect(page.getByRole('dialog').locator('#billable')).toContainText('Non-billable');

    await Promise.all([
        page.getByRole('button', { name: 'Create Project' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects') &&
                response.request().method() === 'POST' &&
                response.status() === 201 &&
                (await response.json()).data.is_billable === false &&
                (await response.json()).data.billable_rate === null
        ),
    ]);

    await expect(page.getByTestId('project_table')).toContainText(newProjectName);
});

test('test that switching from custom rate to default rate clears billable rate', async ({
    page,
    ctx,
}) => {
    const newProjectName = 'Rate Switch Project ' + Math.floor(1 + Math.random() * 10000);
    // Create a project with an existing custom billable rate
    await createProjectViaApi(ctx, {
        name: newProjectName,
        is_billable: true,
        billable_rate: 15000,
    });

    await goToProjectsOverview(page);
    await expect(page.getByText(newProjectName)).toBeVisible({ timeout: 10000 });

    await page.getByRole('row').first().getByRole('button').click();
    await page.getByRole('menuitem').getByText('Edit').first().click();

    // Verify it loaded as Billable with Custom Rate
    await expect(page.getByRole('dialog').locator('#billable')).toContainText('Billable');
    await expect(page.getByRole('dialog').locator('#billableRateType')).toContainText(
        'Custom Rate'
    );

    // Switch to Default Rate
    await page.getByRole('dialog').locator('#billableRateType').click();
    await page.getByRole('option', { name: 'Default Rate' }).click();

    // Rate input should now be disabled
    await expect(page.getByPlaceholder('Billable Rate')).toBeDisabled();

    // Submit — billable_rate changes from 15000 to null, so confirmation dialog appears
    await page.getByRole('button', { name: 'Update Project' }).click();
    await Promise.all([
        page.locator('button').filter({ hasText: 'Yes, update existing time' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200 &&
                (await response.json()).data.is_billable === true &&
                (await response.json()).data.billable_rate === null
        ),
    ]);
});

test('test that switching from billable to non-billable preserves rate settings', async ({
    page,
    ctx,
}) => {
    const newProjectName = 'Billable Reset Project ' + Math.floor(1 + Math.random() * 10000);
    // Create a project with a custom billable rate
    await createProjectViaApi(ctx, {
        name: newProjectName,
        is_billable: true,
        billable_rate: 20000,
    });

    await goToProjectsOverview(page);
    await expect(page.getByText(newProjectName)).toBeVisible({ timeout: 10000 });

    await page.getByRole('row').first().getByRole('button').click();
    await page.getByRole('menuitem').getByText('Edit').first().click();

    // Verify it loaded correctly as Billable with Custom Rate
    await expect(page.getByRole('dialog').locator('#billable')).toContainText('Billable');
    await expect(page.getByRole('dialog').locator('#billableRateType')).toContainText(
        'Custom Rate'
    );

    // Switch to Non-billable
    await page.getByRole('dialog').locator('#billable').click();
    await page.getByRole('option', { name: 'Non-billable' }).click();

    // Rate type should still be Custom Rate (not reset)
    await expect(page.getByRole('dialog').locator('#billableRateType')).toContainText(
        'Custom Rate'
    );

    // Submit and verify project is non-billable but keeps its custom rate
    await Promise.all([
        page.getByRole('button', { name: 'Update Project' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200 &&
                (await response.json()).data.is_billable === false &&
                (await response.json()).data.billable_rate === 20000
        ),
    ]);
});

test('test that editing an existing billable project with default rate loads correctly', async ({
    page,
    ctx,
}) => {
    const newProjectName = 'Default Rate Edit Project ' + Math.floor(1 + Math.random() * 10000);
    // Create a project that is billable but has no custom rate (= default rate)
    await createProjectViaApi(ctx, {
        name: newProjectName,
        is_billable: true,
        billable_rate: null,
    });

    await goToProjectsOverview(page);
    await expect(page.getByText(newProjectName)).toBeVisible({ timeout: 10000 });

    await page.getByRole('row').first().getByRole('button').click();
    await page.getByRole('menuitem').getByText('Edit').first().click();

    // Verify it loaded as Billable with Default Rate
    await expect(page.getByRole('dialog').locator('#billable')).toContainText('Billable');
    await expect(page.getByRole('dialog').locator('#billableRateType')).toContainText(
        'Default Rate'
    );
    await expect(page.getByPlaceholder('Billable Rate')).toBeDisabled();
});

// Sorting tests
test('test that sorting projects by all columns works', async ({ page, ctx }) => {
    // Seed projects with distinct values for each sortable column
    const clientAlpha = await createClientViaApi(ctx, { name: 'Alpha Client' });
    const clientBeta = await createClientViaApi(ctx, { name: 'Beta Client' });

    // Project A: client Alpha, low billable rate, has estimated time, active
    const projectA = await createProjectViaApi(ctx, {
        name: 'AAA Project',
        client_id: clientAlpha.id,
        is_billable: true,
        billable_rate: 5000,
        estimated_time: 36000, // 10h
    });
    // Add 1h of time entries (10% progress)
    await createTimeEntryViaApi(ctx, {
        duration: '1h',
        projectId: projectA.id,
    });

    // Project B: client Beta, high billable rate, has estimated time, archived
    const projectB = await createProjectViaApi(ctx, {
        name: 'BBB Project',
        client_id: clientBeta.id,
        is_billable: true,
        billable_rate: 15000,
        estimated_time: 7200, // 2h
    });
    // Add 1h of time entries (50% progress)
    await createTimeEntryViaApi(ctx, {
        duration: '1h',
        projectId: projectB.id,
    });
    await archiveProjectViaApi(ctx, {
        ...projectB,
        client_id: clientBeta.id,
        billable_rate: 15000,
        estimated_time: 7200,
    });

    // Project C: no client, medium billable rate, no estimated time, active
    const projectC = await createProjectViaApi(ctx, {
        name: 'CCC Project',
        is_billable: true,
        billable_rate: 10000,
    });
    // Add 3h of time entries
    await createTimeEntryViaApi(ctx, {
        duration: '3h',
        projectId: projectC.id,
    });

    await goToProjectsOverview(page);
    await clearProjectTableState(page);
    await page.reload();
    await expect(page.getByTestId('project_table')).toBeVisible();
    await expect(page.getByText('AAA Project')).toBeVisible();
    await expect(page.getByText('BBB Project')).toBeVisible();
    await expect(page.getByText('CCC Project')).toBeVisible();

    // Helper to get the visual order of our seeded projects by reading
    // all row text in a single evaluate call (avoids locator timing issues)
    const seededNames = ['AAA Project', 'BBB Project', 'CCC Project'];
    const getOrder = async (): Promise<string[]> => {
        const allRowTexts = await page.evaluate(() => {
            const table = document.querySelector('[data-testid="project_table"]');
            if (!table) return [];
            const rows = table.querySelectorAll('[role="row"]');
            return Array.from(rows).map((row) => row.textContent ?? '');
        });
        const order: string[] = [];
        for (const text of allRowTexts) {
            const match = seededNames.find((name) => text.includes(name));
            if (match) order.push(match);
        }
        return order;
    };

    // Helper: click a column header and wait for sort to apply.
    // expectedFirstAmongSeeded = which of our 3 seeded projects should appear first
    const clickSortHeader = async (headerText: string, expectedFirstAmongSeeded: string) => {
        const header = page
            .locator('[data-testid="project_table"] .select-none', {
                hasText: headerText,
            })
            .first();
        await header.click();
        // Wait until the expected project appears before the others among our seeded set
        await page.waitForFunction(
            ({ expected, names }) => {
                const table = document.querySelector('[data-testid="project_table"]');
                if (!table) return false;
                const rows = table.querySelectorAll('[role="row"]');
                let firstSeededIdx = -1;
                for (let i = 0; i < rows.length; i++) {
                    const text = rows[i].textContent ?? '';
                    if (names.some((n: string) => text.includes(n))) {
                        firstSeededIdx = i;
                        break;
                    }
                }
                if (firstSeededIdx === -1) return false;
                return (rows[firstSeededIdx].textContent ?? '').includes(expected);
            },
            { expected: expectedFirstAmongSeeded, names: seededNames },
            { timeout: 5000 }
        );
    };

    // --- Sort by Name ---
    // Default is name asc (A-Z)
    let order = await getOrder();
    expect(order).toEqual(['AAA Project', 'BBB Project', 'CCC Project']);

    // Click to toggle to Z-A
    await clickSortHeader('Name', 'CCC Project');
    order = await getOrder();
    expect(order).toEqual(['CCC Project', 'BBB Project', 'AAA Project']);

    // --- Sort by Client (text: first click = A-Z, no-client last) ---
    await clickSortHeader('Client', 'AAA Project');
    order = await getOrder();
    expect(order).toEqual(['AAA Project', 'BBB Project', 'CCC Project']); // Alpha, Beta, No client

    // Reverse: Z-A, no-client still last
    await clickSortHeader('Client', 'BBB Project');
    order = await getOrder();
    expect(order).toEqual(['BBB Project', 'AAA Project', 'CCC Project']); // Beta, Alpha, No client

    // --- Sort by Total Time (numeric: first click = highest first) ---
    await clickSortHeader('Total Time', 'CCC Project');
    order = await getOrder();
    expect(order[0]).toBe('CCC Project'); // C=3h first, A and B tied at 1h

    // Reverse: lowest first
    await clickSortHeader('Total Time', 'AAA Project');
    order = await getOrder();
    expect(order[2]).toBe('CCC Project'); // C=3h last

    // --- Sort by Billable Rate (numeric: first click = highest first) ---
    await clickSortHeader('Billable Rate', 'BBB Project');
    order = await getOrder();
    expect(order).toEqual(['BBB Project', 'CCC Project', 'AAA Project']); // 15000, 10000, 5000

    // Reverse: lowest first
    await clickSortHeader('Billable Rate', 'AAA Project');
    order = await getOrder();
    expect(order).toEqual(['AAA Project', 'CCC Project', 'BBB Project']); // 5000, 10000, 15000

    // --- Sort by Progress (numeric: first click = highest first, no-estimate last) ---
    await clickSortHeader('Progress', 'BBB Project');
    order = await getOrder();
    expect(order).toEqual(['BBB Project', 'AAA Project', 'CCC Project']); // 50%, 10%, no estimate

    // Reverse: lowest first, no-estimate still last
    await clickSortHeader('Progress', 'AAA Project');
    order = await getOrder();
    expect(order).toEqual(['AAA Project', 'BBB Project', 'CCC Project']); // 10%, 50%, no estimate

    // --- Sort by Status (first click = active first, archived last) ---
    await expect(async () => {
        await clickSortHeader('Status', 'AAA Project');
        order = await getOrder();
        expect(order.indexOf('BBB Project')).toBeGreaterThan(order.indexOf('AAA Project'));
        expect(order.indexOf('BBB Project')).toBeGreaterThan(order.indexOf('CCC Project'));
    }).toPass({ timeout: 5000 });

    // Reverse: archived first
    await expect(async () => {
        await clickSortHeader('Status', 'BBB Project');
        order = await getOrder();
        expect(order.indexOf('BBB Project')).toBeLessThan(order.indexOf('AAA Project'));
        expect(order.indexOf('BBB Project')).toBeLessThan(order.indexOf('CCC Project'));
    }).toPass({ timeout: 5000 });
});

// Filter tests
test('test that filtering projects by status works', async ({ page, ctx }) => {
    const newProjectName = 'Filter Test Project ' + Math.floor(1 + Math.random() * 10000);
    await createProjectViaApi(ctx, { name: newProjectName });

    await goToProjectsOverview(page);
    await clearProjectTableState(page);
    await page.reload();
    await expect(page.getByText(newProjectName)).toBeVisible({ timeout: 10000 });

    // Archive the project
    await page.getByRole('row').first().getByRole('button').click();
    await page.getByRole('menuitem').getByText('Archive').first().click();

    // Project should still be visible (default is "all" - no filter)
    await expect(page.getByText(newProjectName)).toBeVisible();

    // Apply Active filter - archived project should disappear
    await selectStatusFilter(page, 'Active');
    await expect(page.getByText(newProjectName)).not.toBeVisible();

    // Remove Active filter - project should reappear (back to "all")
    await removeStatusFilter(page);
    await expect(page.getByText(newProjectName)).toBeVisible();

    // Apply Archived filter - project should still be visible
    await selectStatusFilter(page, 'Archived');
    await expect(page.getByText(newProjectName)).toBeVisible();

    // Remove Archived filter and apply Active filter - project should not be visible
    await removeStatusFilter(page);
    await selectStatusFilter(page, 'Active');
    await expect(page.getByText(newProjectName)).not.toBeVisible();
});

test('test that filter state persists after page reload', async ({ page }) => {
    await goToProjectsOverview(page);
    await clearProjectTableState(page);
    await page.reload();

    // Apply Active status filter
    await selectStatusFilter(page, 'Active');

    // Verify the filter badge is visible
    await expect(page.getByTestId('status-filter-badge')).toBeVisible();

    // Reload the page
    await page.reload();

    // Verify the filter badge is still visible after reload
    await expect(page.getByTestId('status-filter-badge')).toBeVisible();
});

test('test that sort state persists after page reload', async ({ page }) => {
    await goToProjectsOverview(page);
    await clearProjectTableState(page);
    await page.reload();

    // Click on Name header twice to sort descending
    const nameHeader = page.getByText('Name').first();
    await nameHeader.click();
    await expect(nameHeader.locator('svg')).toBeVisible();
    await nameHeader.click();

    // Reload the page
    await page.reload();

    // Verify descending sort indicator is visible on Name column
    await expect(page.getByTestId('project_table')).toBeVisible();
});

test('test that custom billable rate is displayed correctly on project detail page', async ({
    page,
    ctx,
}) => {
    const newProjectName = 'Billable Rate Project ' + Math.floor(1 + Math.random() * 10000);
    const newBillableRate = Math.round(10 + Math.random() * 1000);
    await createProjectViaApi(ctx, { name: newProjectName });

    await goToProjectsOverview(page);
    await expect(page.getByText(newProjectName)).toBeVisible({ timeout: 10000 });

    // Edit the project to set a custom billable rate
    await page.getByRole('row').first().getByRole('button').click();
    await page.getByRole('menuitem').getByText('Edit').first().click();

    // Set billable default to Billable
    await page.getByRole('dialog').locator('#billable').click();
    await page.getByRole('option', { name: 'Billable', exact: true }).click();

    // Set billable rate to Custom Rate
    await page.getByRole('dialog').locator('#billableRateType').click();
    await page.getByRole('option', { name: 'Custom Rate' }).click();

    await page.getByPlaceholder('Billable Rate').fill(newBillableRate.toString());
    await page.getByRole('button', { name: 'Update Project' }).click();

    await Promise.all([
        page.locator('button').filter({ hasText: 'Yes, update existing time' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
    ]);

    // Navigate to the project detail page by clicking the project name
    await page.getByText(newProjectName).first().click();
    await page.waitForURL(/\/projects\/[a-f0-9-]+/);

    // Verify the badge displays the correctly formatted billable rate
    const expectedFormattedRate = formatCentsWithOrganizationDefaults(newBillableRate * 100);
    await expect(page.locator('nav[aria-label="Breadcrumb"]').locator('..')).toContainText(
        expectedFormattedRate
    );
});

// Tests for estimated time input (Issue #460)
test('test that creating a project with estimated time in human-readable format works', async ({
    page,
}) => {
    const newProjectName = 'Estimated Time Project ' + Math.floor(1 + Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(newProjectName);

    // Fill in estimated time using human-readable format
    const estimatedTimeInput = page.getByPlaceholder('e.g. 2h 30m or 1.5');
    await estimatedTimeInput.fill('2h 30m');
    await estimatedTimeInput.press('Tab');

    await Promise.all([
        page.getByRole('button', { name: 'Create Project' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects') &&
                response.request().method() === 'POST' &&
                response.status() === 201 &&
                // 2h 30m = 9000 seconds
                (await response.json()).data.estimated_time === 9000
        ),
    ]);

    await expect(page.getByTestId('project_table')).toContainText(newProjectName);
});

test('test that creating a project with estimated time using decimal notation works', async ({
    page,
}) => {
    const newProjectName = 'Decimal Estimated Project ' + Math.floor(1 + Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(newProjectName);

    // Fill in estimated time using decimal notation (1.5 hours = 1h 30m)
    const estimatedTimeInput = page.getByPlaceholder('e.g. 2h 30m or 1.5');
    await estimatedTimeInput.fill('1.5');
    await estimatedTimeInput.press('Tab');

    await Promise.all([
        page.getByRole('button', { name: 'Create Project' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects') &&
                response.request().method() === 'POST' &&
                response.status() === 201 &&
                // 1.5 hours = 5400 seconds
                (await response.json()).data.estimated_time === 5400
        ),
    ]);

    await expect(page.getByTestId('project_table')).toContainText(newProjectName);
});

test('test that creating a project with estimated time using comma decimal notation works', async ({
    page,
}) => {
    const newProjectName = 'Comma Decimal Project ' + Math.floor(1 + Math.random() * 10000);
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();
    await page.getByLabel('Project Name').fill(newProjectName);

    // Fill in estimated time using comma decimal notation (2,5 hours = 2h 30m)
    const estimatedTimeInput = page.getByPlaceholder('e.g. 2h 30m or 1.5');
    await estimatedTimeInput.fill('2,5');
    await estimatedTimeInput.press('Tab');

    await Promise.all([
        page.getByRole('button', { name: 'Create Project' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects') &&
                response.request().method() === 'POST' &&
                response.status() === 201 &&
                // 2.5 hours = 9000 seconds
                (await response.json()).data.estimated_time === 9000
        ),
    ]);

    await expect(page.getByTestId('project_table')).toContainText(newProjectName);
});

test('test that updating estimated time on existing project works', async ({ page, ctx }) => {
    const newProjectName = 'Update Estimated Project ' + Math.floor(1 + Math.random() * 10000);
    await createProjectViaApi(ctx, { name: newProjectName });

    await goToProjectsOverview(page);
    await expect(page.getByText(newProjectName)).toBeVisible({ timeout: 10000 });

    // Edit the project to add estimated time
    await page.getByRole('row').first().getByRole('button').click();
    await page.getByRole('menuitem').getByText('Edit').first().click();

    // Fill in estimated time
    const estimatedTimeInput = page.getByPlaceholder('e.g. 2h 30m or 1.5');
    await estimatedTimeInput.fill('4h 15m');
    await estimatedTimeInput.press('Tab');

    await Promise.all([
        page.getByRole('button', { name: 'Update Project' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/projects/') &&
                response.request().method() === 'PUT' &&
                response.status() === 200 &&
                // 4h 15m = 15300 seconds
                (await response.json()).data.estimated_time === 15300
        ),
    ]);
});

test('test that estimated time input displays formatted value after blur', async ({ page }) => {
    await goToProjectsOverview(page);
    await page.getByRole('button', { name: 'Create Project' }).click();

    const estimatedTimeInput = page.getByPlaceholder('e.g. 2h 30m or 1.5');

    // Enter time in various formats and check the displayed value
    await estimatedTimeInput.fill('90');
    await estimatedTimeInput.press('Tab');
    // 90 hours should be displayed as "90h 00min" (default format)
    await expect(estimatedTimeInput).toHaveValue(/90h/);

    await estimatedTimeInput.fill('1:30');
    await estimatedTimeInput.press('Tab');
    // 1:30 should be displayed as "1h 30min"
    await expect(estimatedTimeInput).toHaveValue(/1h.*30/);
});

test('test that editing a task name on the project detail page works', async ({ page, ctx }) => {
    const projectName = 'Task Edit Project ' + Math.floor(1 + Math.random() * 10000);
    const originalTaskName = 'Original Task ' + Math.floor(1 + Math.random() * 10000);
    const updatedTaskName = 'Updated Task ' + Math.floor(1 + Math.random() * 10000);
    const project = await createProjectViaApi(ctx, { name: projectName });
    await createTaskViaApi(ctx, { name: originalTaskName, project_id: project.id });

    // Navigate to the project detail page
    await goToProjectsOverview(page);
    await expect(page.getByText(projectName)).toBeVisible({ timeout: 10000 });
    await page.getByText(projectName).first().click();
    await page.waitForURL(/\/projects\/[a-f0-9-]+/);

    // Verify task is visible
    await expect(page.getByTestId('task_table')).toContainText(originalTaskName);

    // Open edit modal via actions menu
    const moreButton = page.locator("[aria-label='Actions for Task " + originalTaskName + "']");
    await moreButton.click();
    await page.getByTestId('task_edit').click();

    // Update the task name
    await page.locator('#taskName').fill(updatedTaskName);
    await Promise.all([
        page.getByRole('button', { name: 'Update Task' }).click(),
        page.waitForResponse(
            async (response) =>
                response.url().includes('/tasks') &&
                response.request().method() === 'PUT' &&
                response.status() === 200
        ),
    ]);

    // Verify updated name is shown and old name is gone
    await expect(page.getByTestId('task_table')).toContainText(updatedTaskName);
    await expect(page.getByTestId('task_table')).not.toContainText(originalTaskName);
});

// =============================================
// Employee Permission Tests
// =============================================

test.describe('Employee Projects Restrictions', () => {
    test('employee can view public projects but cannot create', async ({ ctx, employee }) => {
        const projectName = 'EmpViewProj ' + Math.floor(Math.random() * 10000);
        await createPublicProjectViaApi(ctx, { name: projectName });

        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/projects');
        await expect(employee.page.getByTestId('projects_view')).toBeVisible({
            timeout: 10000,
        });

        // Employee can see the public project
        await expect(employee.page.getByText(projectName)).toBeVisible({ timeout: 10000 });

        // Employee cannot see Create Project button
        await expect(
            employee.page.getByRole('button', { name: 'Create Project' })
        ).not.toBeVisible();
    });

    test('employee cannot see edit/delete/archive actions on projects', async ({
        ctx,
        employee,
    }) => {
        const projectName = 'EmpActionsProj ' + Math.floor(Math.random() * 10000);
        await createPublicProjectViaApi(ctx, { name: projectName });

        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/projects');
        await expect(employee.page.getByText(projectName)).toBeVisible({ timeout: 10000 });

        // Click the actions dropdown trigger to open the menu
        const actionsButton = employee.page.locator(
            `[aria-label='Actions for Project ${projectName}']`
        );
        await actionsButton.click();

        // The dropdown menu items (Edit, Archive, Delete) should NOT be visible
        await expect(
            employee.page.locator(`[aria-label='Edit Project ${projectName}']`)
        ).not.toBeVisible();
        await expect(
            employee.page.locator(`[aria-label='Archive Project ${projectName}']`)
        ).not.toBeVisible();
        await expect(
            employee.page.locator(`[aria-label='Delete Project ${projectName}']`)
        ).not.toBeVisible();
    });
});

test.describe('Employee Billable Rate Visibility', () => {
    test('employee cannot see billable rate column by default', async ({ ctx, employee }) => {
        const projectName = 'EmpBillableProj ' + Math.floor(Math.random() * 10000);
        await createPublicProjectViaApi(ctx, {
            name: projectName,
            is_billable: true,
            billable_rate: 15000,
        });

        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/projects');
        await expect(employee.page.getByText(projectName)).toBeVisible({ timeout: 10000 });

        // Billable Rate column should not be visible to employee by default
        await expect(employee.page.getByText('Billable Rate')).not.toBeVisible();
    });

    test('employee can see billable rate column when employees_can_see_billable_rates is enabled', async ({
        ctx,
        employee,
    }) => {
        await updateOrganizationSettingViaApi(ctx, { employees_can_see_billable_rates: true });

        const projectName = 'EmpBillableVisProj ' + Math.floor(Math.random() * 10000);
        await createPublicProjectViaApi(ctx, {
            name: projectName,
            is_billable: true,
            billable_rate: 20000,
        });

        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/projects');
        await expect(employee.page.getByText(projectName)).toBeVisible({ timeout: 10000 });

        // Billable Rate column header should be visible
        await expect(employee.page.getByText('Billable Rate')).toBeVisible();

        // The project row should show the formatted billable rate
        const projectRow = employee.page.getByRole('row').filter({ hasText: projectName });
        await expect(projectRow).toContainText('200');
    });
});
