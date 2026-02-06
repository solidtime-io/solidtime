import { expect } from '@playwright/test';
import { test } from '../playwright/fixtures';
import {
    goToReportingDetailed,
    createProject,
    createClient,
    createProjectWithClient,
    createTask,
    createTimeEntryWithProject,
    createTimeEntryWithProjectAndTask,
    createTimeEntryWithTag,
    createBareTimeEntry,
    waitForDetailedReportingUpdate,
} from './utils/reporting';

// Each test registers a new user and creates test data, which needs more time
test.describe.configure({ timeout: 60000 });

// ──────────────────────────────────────────────────
// Basic Detailed View Tests
// ──────────────────────────────────────────────────

test('test that detailed view shows time entries correctly', async ({ page }) => {
    const projectName = 'Detailed View Project ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createTimeEntryWithProject(page, projectName, '1h');

    // Go to detailed reporting view
    await goToReportingDetailed(page);

    // Verify the time entry is shown with all details
    await expect(page.getByText(projectName, { exact: true })).toBeVisible();
    await expect(page.locator('input[name="Duration"]')).toHaveValue('1h 00min');
    await expect(page.getByText('Entry for ' + projectName, { exact: true })).toBeVisible();
});

test('test that updating duration in detailed view works correctly', async ({ page }) => {
    const projectName = 'Duration Update Project ' + Math.floor(Math.random() * 10000);
    const initialDuration = '1h';
    const updatedDuration = '2h 30min';

    await createProject(page, projectName);
    await createTimeEntryWithProject(page, projectName, initialDuration);

    // Go to detailed reporting view
    await goToReportingDetailed(page);

    // Find and update the duration
    const durationInput = page.locator('input[name="Duration"]').first();
    await durationInput.click();
    await durationInput.fill(updatedDuration);
    await Promise.all([
        durationInput.press('Enter'),
        page.waitForResponse(
            (response) => response.url().includes('/time-entries') && response.status() === 200
        ),
    ]);

    // Verify the new duration is displayed
    await expect(durationInput).toHaveValue(updatedDuration);
});

// ──────────────────────────────────────────────────
// Project Filter Tests
// ──────────────────────────────────────────────────

test('test that project multiselect filters work on detailed reporting page', async ({ page }) => {
    const project1 = 'DetailProj1 ' + Math.floor(Math.random() * 10000);
    const project2 = 'DetailProj2 ' + Math.floor(Math.random() * 10000);

    await createProject(page, project1);
    await createProject(page, project2);
    await createTimeEntryWithProject(page, project1, '1h');
    await createTimeEntryWithProject(page, project2, '2h');

    await goToReportingDetailed(page);

    // Wait for initial data load
    await expect(page.getByText(`Entry for ${project1}`)).toBeVisible();
    await expect(page.getByText(`Entry for ${project2}`)).toBeVisible();

    // Open project multiselect and select project1
    await page.getByRole('button', { name: 'Projects' }).first().click();
    await page.getByRole('option').filter({ hasText: project1 }).click();

    await Promise.all([page.keyboard.press('Escape'), waitForDetailedReportingUpdate(page)]);

    // Verify only project1 entry is shown
    await expect(page.getByText(`Entry for ${project1}`)).toBeVisible();
    await expect(page.getByText(`Entry for ${project2}`)).not.toBeVisible();
});

// ──────────────────────────────────────────────────
// Client Filter Tests
// ──────────────────────────────────────────────────

test('test that client multiselect filters work on detailed reporting page', async ({ page }) => {
    const client1 = 'DetailClient1 ' + Math.floor(Math.random() * 10000);
    const project1 = 'DetailClientProj1 ' + Math.floor(Math.random() * 10000);
    const project2 = 'DetailClientProj2 ' + Math.floor(Math.random() * 10000);

    await createClient(page, client1);
    await createProjectWithClient(page, project1, client1);
    await createProject(page, project2);
    await createTimeEntryWithProject(page, project1, '1h');
    await createTimeEntryWithProject(page, project2, '2h');

    await goToReportingDetailed(page);

    await expect(page.getByText(`Entry for ${project1}`)).toBeVisible();
    await expect(page.getByText(`Entry for ${project2}`)).toBeVisible();

    // Filter by client1
    await page.getByRole('button', { name: 'Clients' }).first().click();
    await page.getByRole('option').filter({ hasText: client1 }).click();

    await Promise.all([page.keyboard.press('Escape'), waitForDetailedReportingUpdate(page)]);

    // Only entries for project1 (with client1) should be visible
    await expect(page.getByText(`Entry for ${project1}`)).toBeVisible();
    await expect(page.getByText(`Entry for ${project2}`)).not.toBeVisible();
});

// ──────────────────────────────────────────────────
// Task Filter Tests
// ──────────────────────────────────────────────────

test('test that task multiselect dropdown filters reporting by task', async ({ page }) => {
    const projectName = 'TaskFilterProj ' + Math.floor(Math.random() * 10000);
    const task1 = 'TaskFilter1 ' + Math.floor(Math.random() * 10000);
    const task2 = 'TaskFilter2 ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createTask(page, projectName, task1);
    await createTask(page, projectName, task2);
    await createTimeEntryWithProjectAndTask(page, projectName, task1, '1h');
    await createTimeEntryWithProjectAndTask(page, projectName, task2, '2h');

    // Use the detailed view to verify task filtering (shows individual entries)
    await goToReportingDetailed(page);

    await expect(page.getByText(`Entry for ${projectName} - ${task1}`)).toBeVisible();
    await expect(page.getByText(`Entry for ${projectName} - ${task2}`)).toBeVisible();

    // Open task multiselect dropdown
    await page.getByRole('button', { name: 'Tasks' }).first().click();

    // Verify both tasks appear
    await expect(page.getByRole('option').filter({ hasText: task1 })).toBeVisible();
    await expect(page.getByRole('option').filter({ hasText: task2 })).toBeVisible();

    // Select task1
    await page.getByRole('option').filter({ hasText: task1 }).click();

    await Promise.all([page.keyboard.press('Escape'), waitForDetailedReportingUpdate(page)]);

    // Verify badge shows count of 1
    await expect(page.getByRole('button', { name: 'Tasks' }).first().getByText('1')).toBeVisible();

    // Verify only task1 entry is shown
    await expect(page.getByText(`Entry for ${projectName} - ${task1}`)).toBeVisible();
    await expect(page.getByText(`Entry for ${projectName} - ${task2}`)).not.toBeVisible();
});

test('test that selecting multiple tasks shows correct badge count', async ({ page }) => {
    const projectName = 'MultiTaskProj ' + Math.floor(Math.random() * 10000);
    const task1 = 'MultiTask1 ' + Math.floor(Math.random() * 10000);
    const task2 = 'MultiTask2 ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createTask(page, projectName, task1);
    await createTask(page, projectName, task2);
    await createTimeEntryWithProjectAndTask(page, projectName, task1, '1h');
    await createTimeEntryWithProjectAndTask(page, projectName, task2, '2h');

    // Use the detailed view to verify task filtering
    await goToReportingDetailed(page);

    await expect(page.getByText(`Entry for ${projectName} - ${task1}`)).toBeVisible();
    await expect(page.getByText(`Entry for ${projectName} - ${task2}`)).toBeVisible();

    // Select both tasks
    await page.getByRole('button', { name: 'Tasks' }).first().click();
    await page.getByRole('option').filter({ hasText: task1 }).click();
    await page.getByRole('option').filter({ hasText: task2 }).click();

    await Promise.all([page.keyboard.press('Escape'), waitForDetailedReportingUpdate(page)]);

    // Verify badge shows count of 2
    await expect(page.getByRole('button', { name: 'Tasks' }).first().getByText('2')).toBeVisible();

    // Verify both task entries are shown
    await expect(page.getByText(`Entry for ${projectName} - ${task1}`)).toBeVisible();
    await expect(page.getByText(`Entry for ${projectName} - ${task2}`)).toBeVisible();
});

test('test that deselecting a task removes the filter', async ({ page }) => {
    const projectName = 'TaskDeselectProj ' + Math.floor(Math.random() * 10000);
    const task1 = 'TaskDeselect1 ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createTask(page, projectName, task1);
    await createTimeEntryWithProjectAndTask(page, projectName, task1, '1h');

    await goToReportingDetailed(page);
    await expect(page.getByText(`Entry for ${projectName} - ${task1}`)).toBeVisible();

    // Select task
    await page.getByRole('button', { name: 'Tasks' }).first().click();
    await page.getByRole('option').filter({ hasText: task1 }).click();
    await Promise.all([page.keyboard.press('Escape'), waitForDetailedReportingUpdate(page)]);

    await expect(page.getByRole('button', { name: 'Tasks' }).first().getByText('1')).toBeVisible();

    // Deselect task
    await page.getByRole('button', { name: 'Tasks' }).first().click();
    await page.getByRole('option').filter({ hasText: task1 }).click();
    await Promise.all([page.keyboard.press('Escape'), waitForDetailedReportingUpdate(page)]);

    await expect(
        page.getByRole('button', { name: 'Tasks' }).first().getByText(/^\d+$/)
    ).not.toBeVisible();
});

// ──────────────────────────────────────────────────
// Member Filter Tests
// ──────────────────────────────────────────────────

test('test that member multiselect filters work on detailed reporting page', async ({ page }) => {
    const projectName = 'DetailMemberProj ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createTimeEntryWithProject(page, projectName, '1h');

    await goToReportingDetailed(page);
    await expect(page.getByText(`Entry for ${projectName}`)).toBeVisible();

    // Filter by the current member
    await page.getByRole('button', { name: 'Members' }).first().click();
    await page.getByRole('option').filter({ hasText: 'John Doe' }).click();

    await Promise.all([page.keyboard.press('Escape'), waitForDetailedReportingUpdate(page)]);

    // Data should still be visible since all entries belong to this member
    await expect(page.getByText(`Entry for ${projectName}`)).toBeVisible();

    // Verify badge shows count of 1
    await expect(
        page.getByRole('button', { name: 'Members' }).first().getByText('1')
    ).toBeVisible();
});

// ──────────────────────────────────────────────────
// Tag Filter Tests
// ──────────────────────────────────────────────────

test('test that tag filter works on detailed reporting page', async ({ page }) => {
    const tag1 = 'DetailTag1 ' + Math.floor(Math.random() * 10000);
    const tag2 = 'DetailTag2 ' + Math.floor(Math.random() * 10000);

    await createTimeEntryWithTag(page, tag1, '1h');
    await createTimeEntryWithTag(page, tag2, '2h');

    await goToReportingDetailed(page);

    await expect(page.getByText(`Entry with tag ${tag1}`)).toBeVisible();
    await expect(page.getByText(`Entry with tag ${tag2}`)).toBeVisible();

    // Filter by tag1
    await page.getByRole('button', { name: 'Tags' }).click();
    await page.getByRole('option').filter({ hasText: tag1 }).click();

    await Promise.all([page.keyboard.press('Escape'), waitForDetailedReportingUpdate(page)]);

    await expect(page.getByText(`Entry with tag ${tag1}`)).toBeVisible();
    await expect(page.getByText(`Entry with tag ${tag2}`)).not.toBeVisible();
});

// ──────────────────────────────────────────────────
// Billable Filter Tests
// ──────────────────────────────────────────────────

test('test that billable filter works on detailed reporting page', async ({ page }) => {
    const projectName = 'DetailBillProj ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createTimeEntryWithProject(page, projectName, '1h');

    await goToReportingDetailed(page);
    await expect(page.getByText(`Entry for ${projectName}`)).toBeVisible();

    // Filter by billable only
    await page.getByRole('combobox').filter({ hasText: 'Billable' }).click();
    await Promise.all([
        page.getByRole('option', { name: 'Billable', exact: true }).click(),
        waitForDetailedReportingUpdate(page),
    ]);

    // Switch to Non Billable
    await page.getByRole('combobox').filter({ hasText: 'Billable' }).click();
    await Promise.all([
        page.getByRole('option', { name: 'Non Billable', exact: true }).click(),
        waitForDetailedReportingUpdate(page),
    ]);

    // Switch back to Both
    await page.getByRole('combobox').filter({ hasText: 'Non Billable' }).click();
    await Promise.all([
        page.getByRole('option', { name: 'Both' }).click(),
        waitForDetailedReportingUpdate(page),
    ]);
});

// ──────────────────────────────────────────────────
// Combined Filter Tests
// ──────────────────────────────────────────────────

test('test that combining project and task filters narrows results', async ({ page }) => {
    const projectName = 'CombinedProj ' + Math.floor(Math.random() * 10000);
    const otherProject = 'OtherCombProj ' + Math.floor(Math.random() * 10000);
    const task1 = 'CombinedTask1 ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createProject(page, otherProject);
    await createTask(page, projectName, task1);
    await createTimeEntryWithProjectAndTask(page, projectName, task1, '1h');
    await createTimeEntryWithProject(page, otherProject, '2h');

    await goToReportingDetailed(page);

    await expect(page.getByText(`Entry for ${projectName} - ${task1}`)).toBeVisible();
    await expect(page.getByText(`Entry for ${otherProject}`)).toBeVisible();

    // Filter by project
    await page.getByRole('button', { name: 'Projects' }).first().click();
    await page.getByRole('option').filter({ hasText: projectName }).click();
    await Promise.all([page.keyboard.press('Escape'), waitForDetailedReportingUpdate(page)]);

    // Additionally filter by task
    await page.getByRole('button', { name: 'Tasks' }).first().click();
    await page.getByRole('option').filter({ hasText: task1 }).click();
    await Promise.all([page.keyboard.press('Escape'), waitForDetailedReportingUpdate(page)]);

    // Verify both badges show count of 1
    await expect(
        page.getByRole('button', { name: 'Projects' }).first().getByText('1')
    ).toBeVisible();
    await expect(page.getByRole('button', { name: 'Tasks' }).first().getByText('1')).toBeVisible();

    // Verify only the combined entry is shown
    await expect(page.getByText(`Entry for ${projectName} - ${task1}`)).toBeVisible();
    await expect(page.getByText(`Entry for ${otherProject}`)).not.toBeVisible();
});

test('test that combining client and member filters narrows results on detailed page', async ({
    page,
}) => {
    const client1 = 'CombClient ' + Math.floor(Math.random() * 10000);
    const project1 = 'CombClientProj ' + Math.floor(Math.random() * 10000);
    const project2 = 'CombNoClientProj ' + Math.floor(Math.random() * 10000);

    await createClient(page, client1);
    await createProjectWithClient(page, project1, client1);
    await createProject(page, project2);
    await createTimeEntryWithProject(page, project1, '1h');
    await createTimeEntryWithProject(page, project2, '2h');

    await goToReportingDetailed(page);

    await expect(page.getByText(`Entry for ${project1}`)).toBeVisible();
    await expect(page.getByText(`Entry for ${project2}`)).toBeVisible();

    // Filter by client
    await page.getByRole('button', { name: 'Clients' }).first().click();
    await page.getByRole('option').filter({ hasText: client1 }).click();
    await Promise.all([page.keyboard.press('Escape'), waitForDetailedReportingUpdate(page)]);

    // Additionally filter by member
    await page.getByRole('button', { name: 'Members' }).first().click();
    await page.getByRole('option').filter({ hasText: 'John Doe' }).click();
    await Promise.all([page.keyboard.press('Escape'), waitForDetailedReportingUpdate(page)]);

    // Only project1 entry should be visible (filtered by client + member)
    await expect(page.getByText(`Entry for ${project1}`)).toBeVisible();
    await expect(page.getByText(`Entry for ${project2}`)).not.toBeVisible();

    // Both badges should show count of 1
    await expect(
        page.getByRole('button', { name: 'Clients' }).first().getByText('1')
    ).toBeVisible();
    await expect(
        page.getByRole('button', { name: 'Members' }).first().getByText('1')
    ).toBeVisible();
});

test('test that combining tag and project filters narrows results', async ({ page }) => {
    const tag1 = 'CombTag ' + Math.floor(Math.random() * 10000);
    const project1 = 'CombTagProj ' + Math.floor(Math.random() * 10000);

    await createProject(page, project1);

    // Create a time entry with a project (no tag)
    await createTimeEntryWithProject(page, project1, '1h');

    // Create a time entry with a tag (no specific project)
    await createTimeEntryWithTag(page, tag1, '2h');

    await goToReportingDetailed(page);

    await expect(page.getByText(`Entry for ${project1}`)).toBeVisible();
    await expect(page.getByText(`Entry with tag ${tag1}`)).toBeVisible();

    // Filter by project
    await page.getByRole('button', { name: 'Projects' }).first().click();
    await page.getByRole('option').filter({ hasText: project1 }).click();
    await Promise.all([page.keyboard.press('Escape'), waitForDetailedReportingUpdate(page)]);

    // Only the project entry should be visible (tagged entry has no project)
    await expect(page.getByText(`Entry for ${project1}`)).toBeVisible();
    await expect(page.getByText(`Entry with tag ${tag1}`)).not.toBeVisible();
});

// ──────────────────────────────────────────────────
// "No X" Filter Tests
// ──────────────────────────────────────────────────

test('test that "No Project" filter shows entries without a project', async ({ page }) => {
    const project1 = 'NoProj1 ' + Math.floor(Math.random() * 10000);

    await createProject(page, project1);
    await createTimeEntryWithProject(page, project1, '1h');
    await createBareTimeEntry(page, 'Bare entry no project', '30min');

    await goToReportingDetailed(page);

    await expect(page.getByText(`Entry for ${project1}`)).toBeVisible();
    await expect(page.getByText('Bare entry no project')).toBeVisible();

    // Open project dropdown and select "No Project"
    await page.getByRole('button', { name: 'Projects' }).first().click();
    await page.getByRole('option').filter({ hasText: 'No Project' }).click();

    await Promise.all([page.keyboard.press('Escape'), waitForDetailedReportingUpdate(page)]);

    // Verify badge shows 1
    await expect(
        page.getByRole('button', { name: 'Projects' }).first().getByText('1')
    ).toBeVisible();

    // Only the bare entry (no project) should be visible
    await expect(page.getByText('Bare entry no project')).toBeVisible();
    await expect(page.getByText(`Entry for ${project1}`)).not.toBeVisible();
});

test('test that "No Task" filter shows entries without a task', async ({ page }) => {
    const projectName = 'NoTaskProj ' + Math.floor(Math.random() * 10000);
    const task1 = 'NoTaskFilter1 ' + Math.floor(Math.random() * 10000);

    await createProject(page, projectName);
    await createTask(page, projectName, task1);
    await createTimeEntryWithProjectAndTask(page, projectName, task1, '1h');
    await createTimeEntryWithProject(page, projectName, '30min');

    await goToReportingDetailed(page);

    await expect(page.getByText(`Entry for ${projectName} - ${task1}`)).toBeVisible();
    await expect(page.getByText(`Entry for ${projectName}`).first()).toBeVisible();

    // Open task dropdown and select "No Task"
    await page.getByRole('button', { name: 'Tasks' }).first().click();
    await page.getByRole('option').filter({ hasText: 'No Task' }).click();

    await Promise.all([page.keyboard.press('Escape'), waitForDetailedReportingUpdate(page)]);

    await expect(page.getByRole('button', { name: 'Tasks' }).first().getByText('1')).toBeVisible();

    // Only the entry without a task should be visible
    await expect(page.getByText(`Entry for ${projectName} - ${task1}`)).not.toBeVisible();
});

test('test that "No Tag" filter shows entries without tags', async ({ page }) => {
    const tag1 = 'NoTagFilter1 ' + Math.floor(Math.random() * 10000);

    await createTimeEntryWithTag(page, tag1, '1h');
    await createBareTimeEntry(page, 'Entry without any tag', '30min');

    await goToReportingDetailed(page);

    await expect(page.getByText(`Entry with tag ${tag1}`)).toBeVisible();
    await expect(page.getByText('Entry without any tag')).toBeVisible();

    // Open tag dropdown and select "No Tag"
    await page.getByRole('button', { name: 'Tags' }).click();
    await page.getByRole('option').filter({ hasText: 'No Tag' }).click();

    await Promise.all([page.keyboard.press('Escape'), waitForDetailedReportingUpdate(page)]);

    await expect(page.getByRole('button', { name: 'Tags' }).getByText('1')).toBeVisible();

    await expect(page.getByText('Entry without any tag')).toBeVisible();
    await expect(page.getByText(`Entry with tag ${tag1}`)).not.toBeVisible();
});

test('test that "No Client" filter shows entries without a client', async ({ page }) => {
    const client1 = 'NoClientFilter ' + Math.floor(Math.random() * 10000);
    const projectWithClient = 'NoClientProj1 ' + Math.floor(Math.random() * 10000);
    const projectNoClient = 'NoClientProj2 ' + Math.floor(Math.random() * 10000);

    await createClient(page, client1);
    await createProjectWithClient(page, projectWithClient, client1);
    await createProject(page, projectNoClient);
    await createTimeEntryWithProject(page, projectWithClient, '1h');
    await createTimeEntryWithProject(page, projectNoClient, '30min');

    await goToReportingDetailed(page);

    await expect(page.getByText(`Entry for ${projectWithClient}`)).toBeVisible();
    await expect(page.getByText(`Entry for ${projectNoClient}`)).toBeVisible();

    // Open client dropdown and select "No Client"
    await page.getByRole('button', { name: 'Clients' }).first().click();
    await page.getByRole('option').filter({ hasText: 'No Client' }).click();

    await Promise.all([page.keyboard.press('Escape'), waitForDetailedReportingUpdate(page)]);

    await expect(
        page.getByRole('button', { name: 'Clients' }).first().getByText('1')
    ).toBeVisible();

    await expect(page.getByText(`Entry for ${projectNoClient}`)).toBeVisible();
    await expect(page.getByText(`Entry for ${projectWithClient}`)).not.toBeVisible();
});

test('test that combining "No Project" with a project ID shows both', async ({ page }) => {
    const project1 = 'CombNoProj ' + Math.floor(Math.random() * 10000);

    await createProject(page, project1);
    await createTimeEntryWithProject(page, project1, '1h');
    await createBareTimeEntry(page, 'Bare combined entry', '30min');

    await goToReportingDetailed(page);

    await expect(page.getByText(`Entry for ${project1}`)).toBeVisible();
    await expect(page.getByText('Bare combined entry')).toBeVisible();

    // Select both "No Project" and the specific project
    await page.getByRole('button', { name: 'Projects' }).first().click();
    await page.getByRole('option').filter({ hasText: 'No Project' }).click();
    await page.getByRole('option').filter({ hasText: project1 }).click();

    await Promise.all([page.keyboard.press('Escape'), waitForDetailedReportingUpdate(page)]);

    // Badge should show 2
    await expect(
        page.getByRole('button', { name: 'Projects' }).first().getByText('2')
    ).toBeVisible();

    // Both entries should be visible
    await expect(page.getByText(`Entry for ${project1}`)).toBeVisible();
    await expect(page.getByText('Bare combined entry')).toBeVisible();
});

// ──────────────────────────────────────────────────
// Keyboard Navigation Tests
// ──────────────────────────────────────────────────

test('test that keyboard navigation works in multiselect dropdown', async ({ page }) => {
    const project1 = 'KbNavProj1 ' + Math.floor(Math.random() * 10000);
    const project2 = 'KbNavProj2 ' + Math.floor(Math.random() * 10000);

    await createProject(page, project1);
    await createProject(page, project2);
    await createTimeEntryWithProject(page, project1, '1h');
    await createTimeEntryWithProject(page, project2, '2h');

    await goToReportingDetailed(page);
    await expect(page.getByText(`Entry for ${project1}`)).toBeVisible();

    // Open project dropdown
    await page.getByRole('button', { name: 'Projects' }).first().click();

    // The search input should be focused, first item ("No Project") highlighted
    await expect(page.getByPlaceholder('Search for a Project...')).toBeFocused();

    // Press ArrowDown to move to first project, then Enter to select it
    await page.keyboard.press('ArrowDown');
    await page.keyboard.press('ArrowDown');
    await page.keyboard.press('Enter');

    // Close dropdown and verify filter applied
    await Promise.all([page.keyboard.press('Escape'), waitForDetailedReportingUpdate(page)]);

    // Badge should show 1
    await expect(
        page.getByRole('button', { name: 'Projects' }).first().getByText('1')
    ).toBeVisible();
});
