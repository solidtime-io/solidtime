import { expect } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { test } from '../playwright/fixtures';
import { goToReporting, waitForReportingUpdate } from './utils/reporting';
import {
    createProjectViaApi,
    createClientViaApi,
    createTaskViaApi,
    createTimeEntryViaApi,
    createTimeEntryWithTagViaApi,
    createTimeEntryWithBillableStatusViaApi,
    createBareTimeEntryViaApi,
    createPublicProjectViaApi,
    updateOrganizationSettingViaApi,
} from './utils/api';

// Each test registers a new user and creates test data via API
test.describe.configure({ timeout: 30000 });

// ──────────────────────────────────────────────────
// Project Multiselect Dropdown Tests
// ──────────────────────────────────────────────────

test('test that project multiselect dropdown shows projects and filters reporting', async ({
    page,
    ctx,
}) => {
    const project1Name = 'ProjFilter1 ' + Math.floor(Math.random() * 10000);
    const project2Name = 'ProjFilter2 ' + Math.floor(Math.random() * 10000);

    const project1 = await createProjectViaApi(ctx, { name: project1Name });
    const project2 = await createProjectViaApi(ctx, { name: project2Name });
    await createTimeEntryViaApi(ctx, {
        description: `Entry for ${project1Name}`,
        duration: '1h',
        projectId: project1.id,
    });
    await createTimeEntryViaApi(ctx, {
        description: `Entry for ${project2Name}`,
        duration: '2h',
        projectId: project2.id,
    });

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Open project multiselect dropdown
    await page.getByRole('button', { name: 'Projects' }).first().click();

    // Verify both projects appear as options
    await expect(page.getByRole('option').filter({ hasText: project1Name })).toBeVisible();
    await expect(page.getByRole('option').filter({ hasText: project2Name })).toBeVisible();

    // Select project1 and wait for report update
    await Promise.all([
        page.getByRole('option').filter({ hasText: project1Name }).click(),
        waitForReportingUpdate(page),
    ]);
    await page.keyboard.press('Escape');

    // Verify filter badge shows count of 1
    await expect(
        page.getByRole('button', { name: 'Projects' }).first().getByText('1')
    ).toBeVisible();

    // Verify only project1 data is shown
    await expect(page.getByTestId('reporting_view').getByText(project1Name)).toBeVisible();
    await expect(page.getByTestId('reporting_view').getByText(project2Name)).not.toBeVisible();
});

test('test that project multiselect search filters the option list', async ({ page, ctx }) => {
    const project1Name = 'SearchableAlpha ' + Math.floor(Math.random() * 10000);
    const project2Name = 'SearchableBeta ' + Math.floor(Math.random() * 10000);

    const project1 = await createProjectViaApi(ctx, { name: project1Name });
    await createProjectViaApi(ctx, { name: project2Name });
    await createTimeEntryViaApi(ctx, {
        description: `Entry for ${project1Name}`,
        duration: '1h',
        projectId: project1.id,
    });

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Open project multiselect dropdown
    await page.getByRole('button', { name: 'Projects' }).first().click();

    // Type in search
    await page.getByPlaceholder('Search for a Project...').fill('Alpha');

    // Verify only matching project is visible
    await expect(page.getByRole('option').filter({ hasText: project1Name })).toBeVisible();
    await expect(page.getByRole('option').filter({ hasText: project2Name })).not.toBeVisible();

    await page.keyboard.press('Escape');
});

test('test that selecting multiple projects shows correct badge count', async ({ page, ctx }) => {
    const project1Name = 'MultiProj1 ' + Math.floor(Math.random() * 10000);
    const project2Name = 'MultiProj2 ' + Math.floor(Math.random() * 10000);

    const project1 = await createProjectViaApi(ctx, { name: project1Name });
    const project2 = await createProjectViaApi(ctx, { name: project2Name });
    await createTimeEntryViaApi(ctx, {
        description: `Entry for ${project1Name}`,
        duration: '1h',
        projectId: project1.id,
    });
    await createTimeEntryViaApi(ctx, {
        description: `Entry for ${project2Name}`,
        duration: '2h',
        projectId: project2.id,
    });

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Open project dropdown and select both
    await page.getByRole('button', { name: 'Projects' }).first().click();
    await page.getByRole('option').filter({ hasText: project1Name }).click();
    await Promise.all([
        page.getByRole('option').filter({ hasText: project2Name }).click(),
        waitForReportingUpdate(page),
    ]);
    await page.keyboard.press('Escape');

    // Verify filter badge shows count of 2
    await expect(
        page.getByRole('button', { name: 'Projects' }).first().getByText('2')
    ).toBeVisible();

    // Verify both projects are shown in the report
    await expect(page.getByTestId('reporting_view').getByText(project1Name)).toBeVisible();
    await expect(page.getByTestId('reporting_view').getByText(project2Name)).toBeVisible();
});

test('test that deselecting a project removes the filter', async ({ page, ctx }) => {
    const project1Name = 'DeselectProj ' + Math.floor(Math.random() * 10000);

    const project1 = await createProjectViaApi(ctx, { name: project1Name });
    await createTimeEntryViaApi(ctx, {
        description: `Entry for ${project1Name}`,
        duration: '1h',
        projectId: project1.id,
    });

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Select project
    await page.getByRole('button', { name: 'Projects' }).first().click();
    await Promise.all([
        page.getByRole('option').filter({ hasText: project1Name }).click(),
        waitForReportingUpdate(page),
    ]);
    await page.keyboard.press('Escape');

    // Verify badge count is 1
    await expect(
        page.getByRole('button', { name: 'Projects' }).first().getByText('1')
    ).toBeVisible();

    // Deselect project (no network request expected — TanStack Query serves cached unfiltered data)
    await page.getByRole('button', { name: 'Projects' }).first().click();
    await page.getByRole('option').filter({ hasText: project1Name }).click();
    await page.keyboard.press('Escape');

    // Verify badge count is gone (no count displayed when 0)
    await expect(
        page.getByRole('button', { name: 'Projects' }).first().getByText(/^\d+$/)
    ).not.toBeVisible();
});

// ──────────────────────────────────────────────────
// Client Multiselect Dropdown Tests
// ──────────────────────────────────────────────────

test('test that client multiselect dropdown filters reporting by client', async ({ page, ctx }) => {
    const client1Name = 'ClientFilter1 ' + Math.floor(Math.random() * 10000);
    const client2Name = 'ClientFilter2 ' + Math.floor(Math.random() * 10000);
    const project1Name = 'ClientProj1 ' + Math.floor(Math.random() * 10000);
    const project2Name = 'ClientProj2 ' + Math.floor(Math.random() * 10000);

    const client1 = await createClientViaApi(ctx, { name: client1Name });
    const client2 = await createClientViaApi(ctx, { name: client2Name });
    const project1 = await createProjectViaApi(ctx, {
        name: project1Name,
        client_id: client1.id,
    });
    const project2 = await createProjectViaApi(ctx, {
        name: project2Name,
        client_id: client2.id,
    });
    await createTimeEntryViaApi(ctx, {
        description: `Entry for ${project1Name}`,
        duration: '1h',
        projectId: project1.id,
    });
    await createTimeEntryViaApi(ctx, {
        description: `Entry for ${project2Name}`,
        duration: '2h',
        projectId: project2.id,
    });

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Open client multiselect dropdown
    await page.getByRole('button', { name: 'Clients' }).first().click();

    // Verify both clients appear
    await expect(page.getByRole('option').filter({ hasText: client1Name })).toBeVisible();
    await expect(page.getByRole('option').filter({ hasText: client2Name })).toBeVisible();

    // Select client1
    await Promise.all([
        page.getByRole('option').filter({ hasText: client1Name }).click(),
        waitForReportingUpdate(page),
    ]);
    await page.keyboard.press('Escape');

    // Verify badge shows count of 1
    await expect(
        page.getByRole('button', { name: 'Clients' }).first().getByText('1')
    ).toBeVisible();

    // Verify only project1 (belonging to client1) is shown
    await expect(page.getByTestId('reporting_view').getByText(project1Name)).toBeVisible();
    await expect(page.getByTestId('reporting_view').getByText(project2Name)).not.toBeVisible();
});

test('test that client multiselect search filters the option list', async ({ page, ctx }) => {
    const client1Name = 'ClientSearchAlpha ' + Math.floor(Math.random() * 10000);
    const client2Name = 'ClientSearchBeta ' + Math.floor(Math.random() * 10000);

    await createClientViaApi(ctx, { name: client1Name });
    await createClientViaApi(ctx, { name: client2Name });

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    await page.getByRole('button', { name: 'Clients' }).first().click();

    // Search for "Alpha"
    await page.getByPlaceholder('Search for a Client...').fill('Alpha');

    await expect(page.getByRole('option').filter({ hasText: client1Name })).toBeVisible();
    await expect(page.getByRole('option').filter({ hasText: client2Name })).not.toBeVisible();

    await page.keyboard.press('Escape');
});

test('test that deselecting a client removes the filter', async ({ page, ctx }) => {
    const client1Name = 'ClientDeselect ' + Math.floor(Math.random() * 10000);
    const project1Name = 'ClientDeselectProj ' + Math.floor(Math.random() * 10000);

    const client1 = await createClientViaApi(ctx, { name: client1Name });
    const project1 = await createProjectViaApi(ctx, {
        name: project1Name,
        client_id: client1.id,
    });
    await createTimeEntryViaApi(ctx, {
        description: `Entry for ${project1Name}`,
        duration: '1h',
        projectId: project1.id,
    });

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Select client
    await page.getByRole('button', { name: 'Clients' }).first().click();
    await Promise.all([
        page.getByRole('option').filter({ hasText: client1Name }).click(),
        waitForReportingUpdate(page),
    ]);
    await page.keyboard.press('Escape');

    await expect(
        page.getByRole('button', { name: 'Clients' }).first().getByText('1')
    ).toBeVisible();

    // Deselect client (no network request expected — TanStack Query serves cached unfiltered data)
    await page.getByRole('button', { name: 'Clients' }).first().click();
    await page.getByRole('option').filter({ hasText: client1Name }).click();
    await page.keyboard.press('Escape');

    await expect(
        page.getByRole('button', { name: 'Clients' }).first().getByText(/^\d+$/)
    ).not.toBeVisible();
});

// ──────────────────────────────────────────────────
// Task Multiselect Dropdown Tests
// ──────────────────────────────────────────────────

test('test that task filtering works in reporting', async ({ page, ctx }) => {
    const projectName = 'Task Filter Proj ' + Math.floor(Math.random() * 10000);
    const task1Name = 'Task Filter A ' + Math.floor(Math.random() * 10000);
    const task2Name = 'Task Filter B ' + Math.floor(Math.random() * 10000);

    const project = await createProjectViaApi(ctx, { name: projectName });
    await createTimeEntryViaApi(ctx, {
        description: `Entry for ${projectName}`,
        duration: '30min',
        projectId: project.id,
    });
    const task1 = await createTaskViaApi(ctx, { name: task1Name, project_id: project.id });
    const task2 = await createTaskViaApi(ctx, { name: task2Name, project_id: project.id });
    await createTimeEntryViaApi(ctx, {
        description: `Entry for ${projectName} - ${task1Name}`,
        duration: '1h',
        projectId: project.id,
        taskId: task1.id,
    });
    await createTimeEntryViaApi(ctx, {
        description: `Entry for ${projectName} - ${task2Name}`,
        duration: '2h',
        projectId: project.id,
        taskId: task2.id,
    });

    // Go to reporting and group by task to see individual tasks
    await goToReporting(page);

    // Filter by task1
    await page.getByRole('button', { name: 'Tasks' }).first().click();
    await Promise.all([
        page.getByRole('option').filter({ hasText: task1Name }).click(),
        waitForReportingUpdate(page),
    ]);
    await page.keyboard.press('Escape');

    // Verify the report only shows 1h (task1's duration)
    await expect(page.getByTestId('reporting_view').getByText('1h 00min').first()).toBeVisible();
});

test('test that task multiselect search filters the option list', async ({ page, ctx }) => {
    const projectName = 'TaskSearchProj ' + Math.floor(Math.random() * 10000);
    const task1Name = 'TaskSearchAlpha ' + Math.floor(Math.random() * 10000);
    const task2Name = 'TaskSearchBeta ' + Math.floor(Math.random() * 10000);

    const project = await createProjectViaApi(ctx, { name: projectName });
    await createTaskViaApi(ctx, { name: task1Name, project_id: project.id });
    await createTaskViaApi(ctx, { name: task2Name, project_id: project.id });

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    await page.getByRole('button', { name: 'Tasks' }).first().click();

    await page.getByPlaceholder('Search for a Task...').fill('Alpha');

    await expect(page.getByRole('option').filter({ hasText: task1Name })).toBeVisible();
    await expect(page.getByRole('option').filter({ hasText: task2Name })).not.toBeVisible();

    await page.keyboard.press('Escape');
});

// ──────────────────────────────────────────────────
// Member Multiselect Dropdown Tests
// ──────────────────────────────────────────────────

test('test that member multiselect dropdown shows current member and filters reporting', async ({
    page,
    ctx,
}) => {
    const projectName = 'MemberFilterProj ' + Math.floor(Math.random() * 10000);

    const project = await createProjectViaApi(ctx, { name: projectName });
    await createTimeEntryViaApi(ctx, {
        description: `Entry for ${projectName}`,
        duration: '1h',
        projectId: project.id,
    });

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Open member multiselect dropdown
    await page.getByRole('button', { name: 'Members' }).first().click();

    // Verify the current user (John Doe from fixture) appears as an option
    await expect(page.getByRole('option').filter({ hasText: 'John Doe' })).toBeVisible();

    // Select the member
    await Promise.all([
        page.getByRole('option').filter({ hasText: 'John Doe' }).click(),
        waitForReportingUpdate(page),
    ]);
    await page.keyboard.press('Escape');

    // Verify badge shows count of 1
    await expect(
        page.getByRole('button', { name: 'Members' }).first().getByText('1')
    ).toBeVisible();

    // Verify data is still shown (since all entries belong to this member)
    await expect(page.getByTestId('reporting_view').getByText(projectName)).toBeVisible();
});

test('test that member multiselect search filters the option list', async ({ page }) => {
    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    await page.getByRole('button', { name: 'Members' }).first().click();

    // Search for the registered user
    await page.getByPlaceholder('Search for a Member...').fill('John');
    await expect(page.getByRole('option').filter({ hasText: 'John Doe' })).toBeVisible();

    // Search for a non-existent member
    await page.getByPlaceholder('Search for a Member...').fill('NonExistentMember');
    await expect(page.getByRole('option')).not.toBeVisible();

    await page.keyboard.press('Escape');
});

test('test that deselecting a member removes the filter', async ({ page, ctx }) => {
    const projectName = 'MemberDeselectProj ' + Math.floor(Math.random() * 10000);

    const project = await createProjectViaApi(ctx, { name: projectName });
    await createTimeEntryViaApi(ctx, {
        description: `Entry for ${projectName}`,
        duration: '1h',
        projectId: project.id,
    });

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Select member
    await page.getByRole('button', { name: 'Members' }).first().click();
    await Promise.all([
        page.getByRole('option').filter({ hasText: 'John Doe' }).click(),
        waitForReportingUpdate(page),
    ]);
    await page.keyboard.press('Escape');

    await expect(
        page.getByRole('button', { name: 'Members' }).first().getByText('1')
    ).toBeVisible();

    // Deselect member (no network request expected — TanStack Query serves cached unfiltered data)
    await page.getByRole('button', { name: 'Members' }).first().click();
    await page.getByRole('option').filter({ hasText: 'John Doe' }).click();
    await page.keyboard.press('Escape');

    // Verify badge count is gone
    await expect(
        page.getByRole('button', { name: 'Members' }).first().getByText(/^\d+$/)
    ).not.toBeVisible();
});

// ──────────────────────────────────────────────────
// Tag Dropdown Tests
// ──────────────────────────────────────────────────

test('test that tag filtering works in reporting', async ({ page, ctx }) => {
    const tag1Name = 'Test Tag 1 ' + Math.floor(Math.random() * 10000);
    const tag2Name = 'Test Tag 2 ' + Math.floor(Math.random() * 10000);

    // Create time entries with different tags
    await createTimeEntryWithTagViaApi(ctx, tag1Name, '1h');
    await createTimeEntryWithTagViaApi(ctx, tag2Name, '2h');

    // Go to reporting and filter by tag1
    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Tags' })).toBeVisible();

    await page.getByRole('button', { name: 'Tags' }).click();
    await Promise.all([page.getByText(tag1Name).click(), waitForReportingUpdate(page)]);
    await page.keyboard.press('Escape');

    // Verify only time entries with tag1 are shown
    await expect(page.getByTestId('reporting_view').getByText('1h 00min').first()).toBeVisible();
});

test('test that tag dropdown search filters the option list', async ({ page, ctx }) => {
    const tag1Name = 'TagSearchAlpha ' + Math.floor(Math.random() * 10000);
    const tag2Name = 'TagSearchBeta ' + Math.floor(Math.random() * 10000);

    await createTimeEntryWithTagViaApi(ctx, tag1Name, '1h');
    await createTimeEntryWithTagViaApi(ctx, tag2Name, '2h');

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    await page.getByRole('button', { name: 'Tags' }).click();

    await page.getByPlaceholder('Search for a Tag...').fill('Alpha');

    await expect(page.getByRole('option').filter({ hasText: tag1Name })).toBeVisible();
    await expect(page.getByRole('option').filter({ hasText: tag2Name })).not.toBeVisible();

    await page.keyboard.press('Escape');
});

test('test that selecting multiple tags shows correct badge count', async ({ page, ctx }) => {
    const tag1Name = 'MultiTag1 ' + Math.floor(Math.random() * 10000);
    const tag2Name = 'MultiTag2 ' + Math.floor(Math.random() * 10000);

    await createTimeEntryWithTagViaApi(ctx, tag1Name, '1h');
    await createTimeEntryWithTagViaApi(ctx, tag2Name, '2h');

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Select both tags
    await page.getByRole('button', { name: 'Tags' }).click();
    await page.getByRole('option').filter({ hasText: tag1Name }).click();
    await Promise.all([
        page.getByRole('option').filter({ hasText: tag2Name }).click(),
        waitForReportingUpdate(page),
    ]);
    await page.keyboard.press('Escape');

    // Verify badge shows count of 2
    await expect(page.getByRole('button', { name: 'Tags' }).getByText('2')).toBeVisible();
});

test('test that deselecting a tag removes the filter', async ({ page, ctx }) => {
    const tag1Name = 'TagDeselect ' + Math.floor(Math.random() * 10000);

    await createTimeEntryWithTagViaApi(ctx, tag1Name, '1h');

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Select tag
    await page.getByRole('button', { name: 'Tags' }).click();
    await Promise.all([
        page.getByRole('option').filter({ hasText: tag1Name }).click(),
        waitForReportingUpdate(page),
    ]);
    await page.keyboard.press('Escape');

    await expect(page.getByRole('button', { name: 'Tags' }).getByText('1')).toBeVisible();

    // Deselect tag (no network request expected — TanStack Query serves cached unfiltered data)
    await page.getByRole('button', { name: 'Tags' }).click();
    await page.getByRole('option').filter({ hasText: tag1Name }).click();
    await page.keyboard.press('Escape');

    await expect(page.getByRole('button', { name: 'Tags' }).getByText(/^\d+$/)).not.toBeVisible();
});

test('test that creating a tag inline from the reporting filter works', async ({ page, ctx }) => {
    const projectName = 'TagCreateProj ' + Math.floor(Math.random() * 10000);
    const newTag = 'InlineTag ' + Math.floor(Math.random() * 10000);

    const project = await createProjectViaApi(ctx, { name: projectName });
    await createTimeEntryViaApi(ctx, {
        description: `Entry for ${projectName}`,
        duration: '1h',
        projectId: project.id,
    });

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Open tag dropdown and create a new tag
    await page.getByRole('button', { name: 'Tags' }).click();
    await page.getByText('Create new tag').click();
    await page.getByPlaceholder('Tag Name').fill(newTag);

    await Promise.all([
        page.getByRole('button', { name: 'Create Tag' }).click(),
        page.waitForResponse(
            (response) => response.url().includes('/tags') && response.status() === 201
        ),
    ]);

    // The new tag should now be selected in the dropdown (badge should show 1)
    await expect(page.getByRole('button', { name: 'Tags' }).getByText('1')).toBeVisible();
});

// ──────────────────────────────────────────────────
// Billable Select Tests
// ──────────────────────────────────────────────────

test('test that billable status filtering works in reporting', async ({ page, ctx }) => {
    // Create billable and non-billable time entries
    await createTimeEntryWithBillableStatusViaApi(ctx, true, '1h');
    await createTimeEntryWithBillableStatusViaApi(ctx, false, '2h');

    // Go to reporting and filter by billable
    await goToReporting(page);

    await page.getByRole('combobox').filter({ hasText: 'Billable' }).click();
    await Promise.all([
        page.getByRole('option', { name: 'Billable', exact: true }).click(),
        waitForReportingUpdate(page),
    ]);

    await expect(page.getByTestId('reporting_view').getByText('1h 00min').first()).toBeVisible();
});

test('test that billable filter can switch between all three states', async ({ page }) => {
    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    const billableSelect = page.getByRole('combobox').filter({ hasText: 'Billable' });

    // Switch to Billable
    await billableSelect.click();
    await Promise.all([
        page.getByRole('option', { name: 'Billable', exact: true }).click(),
        waitForReportingUpdate(page),
    ]);

    // Switch to Non Billable
    await billableSelect.click();
    await Promise.all([
        page.getByRole('option', { name: 'Non Billable', exact: true }).click(),
        waitForReportingUpdate(page),
    ]);

    // Verify "Non Billable" is displayed
    await expect(billableSelect).toContainText('Non Billable');

    // Switch back to Both (cached by TanStack Query, no new API request)
    await billableSelect.click();
    await page.getByRole('option', { name: 'Both' }).click();
    await expect(billableSelect).toContainText('Billable');
});

// ──────────────────────────────────────────────────
// Rounding Controls Tests
// ──────────────────────────────────────────────────

test('test that rounding can be enabled', async ({ page, ctx }) => {
    const projectName = 'RoundingProj ' + Math.floor(Math.random() * 10000);

    const project = await createProjectViaApi(ctx, { name: projectName });
    await createTimeEntryViaApi(ctx, {
        description: `Entry for ${projectName}`,
        duration: '1h 7min',
        projectId: project.id,
    });

    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Verify rounding is off by default
    await expect(page.getByRole('button', { name: /Rounding off/ })).toBeVisible();

    // Open rounding controls and enable rounding
    await page.getByRole('button', { name: /Rounding off/ }).click();

    const reportUpdatePromise = waitForReportingUpdate(page);
    await page.getByRole('switch', { name: 'Enable Rounding' }).click();
    await reportUpdatePromise;

    // Close the popover by clicking elsewhere
    await page.keyboard.press('Escape');

    // Verify button text changed to "on"
    await expect(page.getByRole('button', { name: /Rounding on/ })).toBeVisible();
});

// ──────────────────────────────────────────────────
// Export Tests
// ──────────────────────────────────────────────────

test('test that export dropdown shows all format options', async ({ page, ctx }) => {
    const projectName = 'Export Test ' + Math.floor(Math.random() * 10000);

    const project = await createProjectViaApi(ctx, { name: projectName });
    await createTimeEntryViaApi(ctx, {
        description: `Entry for ${projectName}`,
        duration: '1h',
        projectId: project.id,
    });

    // Go to reporting page
    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Click the export button
    await page.getByRole('button', { name: 'Export' }).click();

    // Verify all 4 format options are visible
    await expect(page.getByRole('menuitem', { name: /Export as PDF/i })).toBeVisible();
    await expect(page.getByRole('menuitem', { name: /Export as Excel/i })).toBeVisible();
    await expect(page.getByRole('menuitem', { name: /Export as CSV/i })).toBeVisible();
    await expect(page.getByRole('menuitem', { name: /Export as ODS/i })).toBeVisible();
});

test('test that CSV export triggers download', async ({ page, ctx }) => {
    const projectName = 'CSV Export ' + Math.floor(Math.random() * 10000);

    const project = await createProjectViaApi(ctx, { name: projectName });
    await createTimeEntryViaApi(ctx, {
        description: `Entry for ${projectName}`,
        duration: '1h',
        projectId: project.id,
    });

    // Go to reporting page
    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Click export and select CSV, wait for the export API response with a download URL
    await page.getByRole('button', { name: 'Export' }).click();
    const [exportResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/aggregate/export') &&
                response.status() === 200
        ),
        page.getByRole('menuitem', { name: /Export as CSV/i }).click(),
    ]);

    // Verify the API returned a download URL
    const responseBody = await exportResponse.json();
    expect(responseBody.download_url).toBeTruthy();

    // Verify the export success modal appeared
    await expect(page.getByText('Export Successful!')).toBeVisible();
});

// ──────────────────────────────────────────────────
// Group By Tests
// ──────────────────────────────────────────────────

test('test that group by select changes report grouping', async ({ page, ctx }) => {
    const projectName = 'GroupBy Test ' + Math.floor(Math.random() * 10000);

    const project = await createProjectViaApi(ctx, { name: projectName });
    await createTimeEntryViaApi(ctx, {
        description: `Entry for ${projectName}`,
        duration: '1h',
        projectId: project.id,
    });

    // Go to reporting page
    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Find the "Group by" selects within the reporting table
    const groupBySelects = page.locator('[data-testid="reporting_view"]').getByRole('combobox');

    // Click the first group by select to change grouping
    await groupBySelects.filter({ hasText: 'Project' }).first().click();

    // Select "Members" option and wait for the table query to update with group=user
    const [aggregateResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/aggregate') &&
                response.url().includes('group=user') &&
                response.status() === 200
        ),
        page.getByRole('option', { name: 'Members' }).click(),
    ]);

    // Verify the API request contains the correct group parameter
    const requestUrl = new URL(aggregateResponse.url());
    expect(requestUrl.searchParams.get('group')).toBe('user');

    // Verify the grouping changed (the select should now show "Members")
    await expect(groupBySelects.filter({ hasText: 'Members' }).first()).toBeVisible();
});

test('test that setting group by to current sub group triggers sub group fallback', async ({
    page,
    ctx,
}) => {
    const projectName = 'Fallback Test ' + Math.floor(Math.random() * 10000);

    const project = await createProjectViaApi(ctx, { name: projectName });
    await createTimeEntryViaApi(ctx, {
        description: `Entry for ${projectName}`,
        duration: '1h',
        projectId: project.id,
    });

    // Go to reporting page
    await goToReporting(page);
    await expect(page.getByRole('button', { name: 'Export' })).toBeVisible();

    // Find the "Group by" selects within the reporting table
    const groupBySelects = page.locator('[data-testid="reporting_view"]').getByRole('combobox');

    // Default state: group=Project, subGroup=Tasks
    // Change group to "Tasks" (which is the current sub group)
    await groupBySelects.filter({ hasText: 'Projects' }).first().click();

    const [aggregateResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/aggregate') &&
                response.url().includes('group=task') &&
                response.status() === 200
        ),
        page.getByRole('option', { name: 'Tasks' }).click(),
    ]);

    // Verify the API request has group=task and sub_group changed away from task
    const requestUrl = new URL(aggregateResponse.url());
    expect(requestUrl.searchParams.get('group')).toBe('task');
    expect(requestUrl.searchParams.get('sub_group')).not.toBe('task');

    // The group should now be "Tasks"
    await expect(groupBySelects.filter({ hasText: 'Tasks' }).first()).toBeVisible();

    // The sub group should have fallen back to a different value (not "Tasks")
    await expect(groupBySelects.filter({ hasText: 'Members' }).first()).toBeVisible();
});

// ──────────────────────────────────────────────────
// Export Tests
// ──────────────────────────────────────────────────

test('test that CSV export can be triggered from the reporting page', async ({ page, ctx }) => {
    await createTimeEntryViaApi(ctx, {
        description: 'CSV export test',
        duration: '1h',
    });

    await goToReporting(page);
    await waitForReportingUpdate(page);

    // Open export dropdown
    await page.getByRole('button', { name: 'Export' }).click();

    // Click CSV export and wait for the API response
    const [exportResponse] = await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/time-entries/aggregate/export') &&
                response.status() === 200
        ),
        page.getByRole('menuitem', { name: 'Export as CSV' }).click(),
    ]);

    // Verify the API returned a download URL
    const responseBody = await exportResponse.json();
    expect(responseBody.download_url).toBeTruthy();

    // Verify the export success modal appeared
    await expect(page.getByText('Export Successful!')).toBeVisible();
});

test('test that export dropdown shows all export options', async ({ page, ctx }) => {
    await createTimeEntryViaApi(ctx, {
        description: 'Export options test',
        duration: '1h',
    });

    await goToReporting(page);
    await waitForReportingUpdate(page);

    // Open export dropdown
    await page.getByRole('button', { name: 'Export' }).click();

    // Verify all export options are visible
    await expect(page.getByRole('menuitem', { name: 'Export as PDF' })).toBeVisible();
    await expect(page.getByRole('menuitem', { name: 'Export as Excel' })).toBeVisible();
    await expect(page.getByRole('menuitem', { name: 'Export as CSV' })).toBeVisible();
    await expect(page.getByRole('menuitem', { name: 'Export as ODS' })).toBeVisible();
});

// =============================================
// Employee Permission Tests
// =============================================

test.describe('Employee Reporting Restrictions', () => {
    test('employee can access overview reporting and sees own data', async ({ ctx, employee }) => {
        // Owner creates a time entry
        await createBareTimeEntryViaApi(ctx, 'Owner report entry', '2h');

        // Create employee time entry
        await createTimeEntryViaApi(
            { ...ctx, memberId: employee.memberId },
            { description: 'Emp report entry', duration: '1h' }
        );

        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/reporting');
        await expect(employee.page.getByTestId('reporting_view')).toBeVisible({
            timeout: 10000,
        });

        // Employee's data should be visible (1h)
        await expect(
            employee.page.getByTestId('reporting_view').getByText('1h 00min').first()
        ).toBeVisible();
    });

    test('employee can access detailed reporting and sees only own entries', async ({
        ctx,
        employee,
    }) => {
        // Owner creates time entries
        const ownerDescription = 'OwnerDetailEntry ' + Math.floor(Math.random() * 10000);
        await createBareTimeEntryViaApi(ctx, ownerDescription, '2h');

        // Create employee time entry
        const empDescription = 'EmpDetailEntry ' + Math.floor(Math.random() * 10000);
        await createTimeEntryViaApi(
            { ...ctx, memberId: employee.memberId },
            { description: empDescription, duration: '1h' }
        );

        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/reporting/detailed');
        await expect(employee.page.getByTestId('reporting_view')).toBeVisible({
            timeout: 10000,
        });

        // Employee's entry IS visible
        await expect(
            employee.page.getByTestId('reporting_view').locator(`text=${empDescription}`).first()
        ).toBeAttached({ timeout: 10000 });

        // Owner's entry is NOT visible
        await expect(
            employee.page.getByTestId('reporting_view').locator(`text=${ownerDescription}`)
        ).not.toBeAttached();
    });

    test('employee cannot see shared reports tab in reporting', async ({ employee }) => {
        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/reporting');
        await expect(employee.page.getByTestId('reporting_view')).toBeVisible({
            timeout: 10000,
        });

        // Overview and Detailed tabs should be visible (scope to main to avoid sidebar matches)
        const mainContent = employee.page.getByRole('main');
        await expect(mainContent.getByRole('tab', { name: 'Overview' })).toBeVisible();
        await expect(mainContent.getByRole('tab', { name: 'Detailed' })).toBeVisible();

        // Shared tab should NOT be visible for employees
        await expect(mainContent.getByRole('tab', { name: 'Shared' })).not.toBeVisible();
    });

    test('employee cannot see Cost column in reporting by default', async ({ ctx, employee }) => {
        const project = await createPublicProjectViaApi(ctx, {
            name: 'EmpBillProj',
            is_billable: true,
            billable_rate: 10000,
        });
        await createTimeEntryViaApi(
            { ...ctx, memberId: employee.memberId },
            { description: 'Emp cost entry', duration: '1h', projectId: project.id }
        );

        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/reporting');
        await expect(employee.page.getByTestId('reporting_view')).toBeVisible({
            timeout: 10000,
        });

        // Cost column header should NOT be visible
        await expect(employee.page.getByText('Cost', { exact: true })).not.toBeVisible();
    });

    test('employee can see Cost column when employees_can_see_billable_rates is enabled', async ({
        ctx,
        employee,
    }) => {
        await updateOrganizationSettingViaApi(ctx, { employees_can_see_billable_rates: true });

        const project = await createPublicProjectViaApi(ctx, {
            name: 'EmpBillVisProj',
            is_billable: true,
            billable_rate: 10000,
        });
        await createTimeEntryViaApi(
            { ...ctx, memberId: employee.memberId },
            {
                description: 'Emp cost visible entry',
                duration: '1h',
                projectId: project.id,
                billable: true,
            }
        );

        await employee.page.goto(PLAYWRIGHT_BASE_URL + '/reporting');
        await expect(employee.page.getByTestId('reporting_view')).toBeVisible({
            timeout: 10000,
        });

        // Cost column header should be visible
        await expect(employee.page.getByText('Cost', { exact: true })).toBeVisible();

        // 1h at 100.00/h billable rate = 100.00 cost (shown in row and total)
        await expect(employee.page.getByText('100,00 EUR').first()).toBeVisible();
    });
});
