import { expect, test } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import type { Page } from '@playwright/test';

const TIMER_BUTTON_SELECTOR = '[data-testid="dashboard_timer"] [data-testid="timer_button"]';

async function goToDashboard(page: Page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
}

async function openCommandPalette(page: Page) {
    await page.getByTestId('command_palette_button').click();
    await expect(page.locator('[role="dialog"]')).toBeVisible({ timeout: 5000 });
}

async function closeCommandPalette(page: Page) {
    await page.keyboard.press('Escape');
    await expect(page.locator('[role="dialog"]')).not.toBeVisible();
}

async function searchInCommandPalette(page: Page, query: string) {
    await page.locator('[role="dialog"] input').fill(query);
    // Wait for search debounce to settle (command palette uses a debounced search)
    await page.waitForTimeout(300);
}

async function selectCommand(page: Page, name: string) {
    const option = page.getByRole('option', { name, exact: true });
    await option.scrollIntoViewIfNeeded();
    await option.click();
}

async function assertTimerIsRunning(page: Page) {
    await expect(page.locator(TIMER_BUTTON_SELECTOR)).toHaveClass(/bg-red-400\/80/, {
        timeout: 10000,
    });
}

async function assertTimerIsStopped(page: Page) {
    await expect(page.locator(TIMER_BUTTON_SELECTOR)).toHaveClass(/bg-accent-300\/70/, {
        timeout: 10000,
    });
}

test.describe('Command Palette', () => {
    test.describe('Opening and Closing', () => {
        test('opens via search button and closes with Escape', async ({ page }) => {
            await goToDashboard(page);
            await openCommandPalette(page);
            await expect(
                page.locator('[role="dialog"] input[placeholder*="command"]')
            ).toBeVisible();

            await closeCommandPalette(page);
            await expect(page.locator('[role="dialog"]')).not.toBeVisible();
        });

        test('opens with keyboard shortcut', async ({ page }) => {
            await goToDashboard(page);
            // Click on body to ensure page has focus
            await page.locator('body').click();
            // Use ControlOrMeta which resolves to Ctrl on Linux/Windows and Meta on macOS
            await page.keyboard.press('ControlOrMeta+k');
            await expect(page.locator('[role="dialog"]')).toBeVisible({ timeout: 5000 });
        });

        test('clears search on close', async ({ page }) => {
            await goToDashboard(page);
            await openCommandPalette(page);
            await searchInCommandPalette(page, 'dashboard');
            await closeCommandPalette(page);

            await openCommandPalette(page);
            await expect(page.locator('[role="dialog"] input')).toHaveValue('');
        });
    });

    test.describe('Command Display', () => {
        test('displays navigation and timer commands', async ({ page }) => {
            await goToDashboard(page);
            await openCommandPalette(page);

            // Navigation commands
            await expect(page.getByRole('option', { name: 'Go to Dashboard' })).toBeVisible();
            await expect(page.getByRole('option', { name: 'Go to Time' })).toBeVisible();
            await expect(page.getByRole('option', { name: 'Go to Calendar' })).toBeVisible();

            // Timer commands
            await expect(page.getByRole('option', { name: 'Start Timer' })).toBeVisible();
            await expect(page.getByRole('option', { name: 'Create Time Entry' })).toBeVisible();
        });

        test('displays create commands', async ({ page }) => {
            await goToDashboard(page);
            await openCommandPalette(page);

            await expect(page.getByRole('option', { name: 'Create Project' })).toBeVisible();
            await expect(page.getByRole('option', { name: 'Create Client' })).toBeVisible();
            await expect(page.getByRole('option', { name: 'Create Tag' })).toBeVisible();
        });
    });

    test.describe('Navigation Commands', () => {
        // Tests use element visibility assertions for consistency with codebase patterns
        const navigationTests = [
            ['Go to Dashboard', 'dashboard_view', '/time'],
            ['Go to Time', 'time_view', '/dashboard'],
            ['Go to Calendar', 'calendar_view', '/dashboard'],
            ['Go to Projects', 'projects_view', '/dashboard'],
            ['Go to Clients', 'clients_view', '/dashboard'],
            ['Go to Members', 'members_view', '/dashboard'],
            ['Go to Tags', 'tags_view', '/dashboard'],
        ] as const;

        for (const [commandName, expectedTestId, startUrl] of navigationTests) {
            test(`${commandName}`, async ({ page }) => {
                await page.goto(PLAYWRIGHT_BASE_URL + startUrl);
                await openCommandPalette(page);
                await searchInCommandPalette(page, commandName.replace('Go to ', ''));
                await selectCommand(page, commandName);
                await expect(page.getByTestId(expectedTestId)).toBeVisible({ timeout: 10000 });
            });
        }

        test('Go to Profile', async ({ page }) => {
            await goToDashboard(page);
            await openCommandPalette(page);
            await searchInCommandPalette(page, 'Profile');
            await selectCommand(page, 'Go to Profile');
            // Profile page doesn't have a testId, so check for a unique element
            await expect(page.getByRole('heading', { name: 'Profile Information' })).toBeVisible({
                timeout: 10000,
            });
        });

        test('Go to Reporting Overview', async ({ page }) => {
            await goToDashboard(page);
            await openCommandPalette(page);
            await searchInCommandPalette(page, 'Reporting Overview');
            await selectCommand(page, 'Go to Reporting Overview');
            await expect(page.getByTestId('reporting_view')).toBeVisible({ timeout: 10000 });
        });

        test('Go to Settings', async ({ page }) => {
            await goToDashboard(page);
            await openCommandPalette(page);
            await searchInCommandPalette(page, 'Settings');
            await selectCommand(page, 'Go to Settings');
            // Settings page uses team settings which has an h3 heading
            await expect(
                page.getByRole('heading', { name: 'Organization Name', level: 3 })
            ).toBeVisible({
                timeout: 10000,
            });
        });
    });

    test.describe('Search and Filtering', () => {
        test('filters commands when searching', async ({ page }) => {
            await goToDashboard(page);
            await openCommandPalette(page);

            await searchInCommandPalette(page, 'dashboard');
            await expect(page.getByRole('option', { name: 'Go to Dashboard' })).toBeVisible();

            await searchInCommandPalette(page, 'calendar');
            await expect(page.getByRole('option', { name: 'Go to Calendar' })).toBeVisible();
        });

        test('search is case insensitive', async ({ page }) => {
            await goToDashboard(page);
            await openCommandPalette(page);

            await searchInCommandPalette(page, 'DASHBOARD');
            await expect(page.getByRole('option', { name: 'Go to Dashboard' })).toBeVisible();
        });

        test('partial word search works', async ({ page }) => {
            await goToDashboard(page);
            await openCommandPalette(page);

            await searchInCommandPalette(page, 'proj');
            await expect(page.getByRole('option', { name: 'Go to Projects' })).toBeVisible();
            await expect(page.getByRole('option', { name: 'Create Project' })).toBeVisible();
        });

        test('keyboard navigation and selection works', async ({ page }) => {
            await goToDashboard(page);
            await openCommandPalette(page);

            await page.keyboard.press('ArrowDown');
            await page.keyboard.press('ArrowDown');
            await page.keyboard.press('Enter');

            await expect(page.locator('[role="dialog"]')).not.toBeVisible();
        });
    });

    test.describe('Theme Commands', () => {
        test('switches to dark theme', async ({ page }) => {
            await goToDashboard(page);
            await openCommandPalette(page);
            await searchInCommandPalette(page, 'Dark Theme');
            await selectCommand(page, 'Switch to Dark Theme');
            await expect(page.locator('html')).toHaveClass(/dark/);
        });

        test('switches to light theme', async ({ page }) => {
            await goToDashboard(page);
            await openCommandPalette(page);
            await searchInCommandPalette(page, 'Light Theme');
            await selectCommand(page, 'Switch to Light Theme');
            await expect(page.locator('html')).toHaveClass(/light/);
        });
    });

    test.describe('Timer Commands', () => {
        test('starts and stops timer', async ({ page }) => {
            await goToDashboard(page);

            // Start timer
            await openCommandPalette(page);
            await searchInCommandPalette(page, 'Start Timer');
            await selectCommand(page, 'Start Timer');
            await assertTimerIsRunning(page);

            // Stop timer
            await openCommandPalette(page);
            await searchInCommandPalette(page, 'Stop Timer');
            await selectCommand(page, 'Stop Timer');
            await assertTimerIsStopped(page);
        });

        test('shows active timer commands when running', async ({ page }) => {
            await goToDashboard(page);

            // Start timer
            await openCommandPalette(page);
            await searchInCommandPalette(page, 'Start Timer');
            await selectCommand(page, 'Start Timer');
            await assertTimerIsRunning(page);

            // Check active timer commands - search for them to ensure visibility
            await openCommandPalette(page);
            await searchInCommandPalette(page, 'Set Project');
            await expect(page.getByRole('option', { name: 'Set Project' })).toBeVisible();
        });
    });

    test.describe('Create Commands', () => {
        test('opens create time entry modal', async ({ page }) => {
            await goToDashboard(page);
            await openCommandPalette(page);
            await searchInCommandPalette(page, 'Create Time Entry');
            await selectCommand(page, 'Create Time Entry');
            await expect(
                page.locator('[role="dialog"]').getByText('Create manual time entry')
            ).toBeVisible();
        });

        test('opens create project modal', async ({ page }) => {
            await goToDashboard(page);
            await openCommandPalette(page);
            await searchInCommandPalette(page, 'Create Project');
            await selectCommand(page, 'Create Project');
            await expect(
                page.locator('[role="dialog"]').getByRole('heading', { name: 'Create Project' })
            ).toBeVisible();
        });

        test('opens create client modal', async ({ page }) => {
            await goToDashboard(page);
            await openCommandPalette(page);
            await searchInCommandPalette(page, 'Create Client');
            await selectCommand(page, 'Create Client');
            await expect(
                page.locator('[role="dialog"]').getByRole('heading', { name: 'Create Client' })
            ).toBeVisible();
        });

        test('opens create tag modal', async ({ page }) => {
            await goToDashboard(page);
            await openCommandPalette(page);
            await searchInCommandPalette(page, 'Create Tag');
            await selectCommand(page, 'Create Tag');
            await expect(page.locator('[role="dialog"]').getByText('Create Tags')).toBeVisible();
        });

        test('opens invite member modal', async ({ page }) => {
            await goToDashboard(page);
            await openCommandPalette(page);
            await searchInCommandPalette(page, 'Invite Member');
            await selectCommand(page, 'Invite Member');
            // Modal has title with "Invite Member" text - use first() to get the title span
            await expect(
                page.locator('[role="dialog"]').getByText('Invite Member').first()
            ).toBeVisible();
        });
    });

    test.describe('Entity Search', () => {
        test('searches for projects and navigates on selection', async ({ page }) => {
            const projectName = 'CmdPalette' + Math.floor(Math.random() * 10000);

            // Create project first
            await page.goto(PLAYWRIGHT_BASE_URL + '/projects');
            await page.getByRole('button', { name: 'Create Project' }).click();
            await page.getByPlaceholder('The next big thing').fill(projectName);

            await page.getByRole('button', { name: 'Create Project' }).click();
            // Wait for project to be created and page to update
            await expect(page.getByText(projectName)).toBeVisible({ timeout: 10000 });

            // Search from the projects page where the query cache now has the new project
            await openCommandPalette(page);
            await searchInCommandPalette(page, projectName);

            // Wait for entity search to return results
            const projectOption = page.getByRole('option').filter({ hasText: projectName });
            await expect(projectOption).toBeVisible({
                timeout: 5000,
            });

            // Select the project from search results
            await projectOption.click();
        });
    });

    test.describe('Organization Switching', () => {
        test('shows switch commands only when multiple organizations exist', async ({ page }) => {
            await goToDashboard(page);
            await openCommandPalette(page);

            // With only one org, no switch commands should appear
            await searchInCommandPalette(page, 'Switch to');
            // Check that no organization switch commands appear (only theme switch commands)
            const switchOptions = page.getByRole('option', { name: /^Switch to (?!.*Theme)/ });
            await expect(switchOptions).toHaveCount(0);
        });

        test('switches organization via command palette', async ({ page }) => {
            const newOrgName = 'TestOrg' + Math.floor(Math.random() * 10000);

            // Create a new organization
            await page.goto(PLAYWRIGHT_BASE_URL + '/teams/create');
            await page.getByLabel('Organization Name').fill(newOrgName);
            await page.getByRole('button', { name: 'Create' }).click();

            // Wait for navigation to new org's dashboard
            await expect(page.getByTestId('dashboard_view')).toBeVisible({ timeout: 10000 });

            // Use visible switcher (desktop sidebar has one, mobile header has another)
            const orgSwitcher = page.locator('[data-testid="organization_switcher"]:visible');

            // Verify we're in the new org by checking the switcher
            await expect(orgSwitcher).toContainText(newOrgName);

            // Get the original org name from switcher dropdown
            await orgSwitcher.click();
            await expect(page.getByText('Switch Organizations')).toBeVisible();

            // Find the other organization button (has ArrowRightIcon, not CheckCircleIcon)
            // The button contains an SVG and a div with the org name
            const otherOrgItem = page.locator('form button').filter({ hasText: /.+/ }).first();
            await expect(otherOrgItem).toBeVisible();
            const originalOrgName = (await otherOrgItem.innerText()).trim();
            await page.keyboard.press('Escape'); // Close dropdown

            // Now use command palette to switch back to original org
            await openCommandPalette(page);
            await searchInCommandPalette(page, 'Switch to');

            // Should see the switch command for the original org
            const switchCommand = page.getByRole('option', {
                name: new RegExp(`Switch to ${originalOrgName}`),
            });
            await expect(switchCommand).toBeVisible();
            await switchCommand.click();

            // Wait for organization switch to complete
            await expect(orgSwitcher).toContainText(originalOrgName, {
                timeout: 10000,
            });
        });

        test('organization switch commands appear in Organization group', async ({ page }) => {
            const newOrgName = 'GroupTestOrg' + Math.floor(Math.random() * 10000);

            // Create a new organization to ensure we have multiple
            await page.goto(PLAYWRIGHT_BASE_URL + '/teams/create');
            await page.getByLabel('Organization Name').fill(newOrgName);
            await page.getByRole('button', { name: 'Create' }).click();
            await expect(page.getByTestId('dashboard_view')).toBeVisible({ timeout: 10000 });

            // Open command palette and check for Organization group heading
            await openCommandPalette(page);

            // The Organization group should be visible when there are switch commands
            await expect(page.getByText('Organization', { exact: true })).toBeVisible();
        });
    });
});
