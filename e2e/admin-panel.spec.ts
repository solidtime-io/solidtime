import { adminTest as test, expect } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL, TEST_USER_PASSWORD } from '../playwright/config';

/**
 * Index pages of all Filament resources that ship with the core application.
 * Resources provided by extensions are intentionally left out, because
 * extensions are not installed in CI.
 */
const resourcePages = [
    { path: '/admin/time-entries', heading: 'Time Entries' },
    { path: '/admin/projects', heading: 'Projects' },
    { path: '/admin/tasks', heading: 'Tasks' },
    { path: '/admin/clients', heading: 'Clients' },
    { path: '/admin/tags', heading: 'Tags' },
    { path: '/admin/reports', heading: 'Reports' },
    { path: '/admin/users', heading: 'Users' },
    { path: '/admin/organizations', heading: 'Organizations' },
    { path: '/admin/organization-invitations', heading: 'Invitations' },
    { path: '/admin/project-members', heading: 'Project Members' },
    { path: '/admin/tokens', heading: 'Tokens' },
    { path: '/admin/failed-jobs', heading: 'Failed Jobs' },
];

test.describe('Admin Panel Access', () => {
    test.use({ storageState: undefined });

    test('test that the admin panel redirects guests to the login page', async ({ page }) => {
        await page.goto(PLAYWRIGHT_BASE_URL + '/admin');
        await expect(page).toHaveURL(PLAYWRIGHT_BASE_URL + '/login');
    });

    test('test that the admin panel is forbidden for users that are no super admin', async ({
        page,
    }) => {
        const email = `john+${Date.now()}_${Math.floor(Math.random() * 10000)}@doe.com`;
        await page.goto(PLAYWRIGHT_BASE_URL + '/register');
        await page.getByLabel('Name').fill('John Doe');
        await page.getByLabel('Email').fill(email);
        await page.getByLabel('Password', { exact: true }).fill(TEST_USER_PASSWORD);
        await page.getByLabel('Confirm Password').fill(TEST_USER_PASSWORD);
        await page.getByLabel('I agree to the Terms of').click();
        await page.getByRole('button', { name: 'Register' }).click();
        await expect(page.getByTestId('dashboard_view')).toBeVisible();

        const response = await page.goto(PLAYWRIGHT_BASE_URL + '/admin');
        expect(response?.status()).toBe(403);
    });
});

test.describe('Admin Panel', () => {
    test('test that the admin dashboard loads with all widgets', async ({ page }) => {
        await page.goto(PLAYWRIGHT_BASE_URL + '/admin');

        await expect(page.getByRole('heading', { name: 'Dashboard', level: 1 })).toBeVisible();

        // The widgets are loaded lazily via Livewire, so they only show up once
        // their content has been rendered.
        await expect(page.getByText('Total real users')).toBeVisible();
        await expect(page.getByText('Placeholder users')).toBeVisible();
        await expect(page.getByText('Active users in the last seven days')).toBeVisible();
        await expect(page.getByText('User Registrations', { exact: true })).toBeVisible();
        await expect(page.getByText('Time Entries Created', { exact: true })).toBeVisible();
        await expect(page.getByText('Time Entries Imported', { exact: true })).toBeVisible();
    });

    for (const { path, heading } of resourcePages) {
        test(`test that the admin page ${path} loads`, async ({ page }) => {
            const response = await page.goto(PLAYWRIGHT_BASE_URL + path);

            expect(response?.status()).toBe(200);
            await expect(page.getByRole('heading', { name: heading, level: 1 })).toBeVisible();
        });
    }
});
