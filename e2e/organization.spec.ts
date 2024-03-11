import { test, expect } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';

async function goToOrganizationSettings(page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
    await page.getByTestId('organization_switcher').click();
    await page.getByText('Team Settings').click();
}

test('test that organization name can be updated', async ({ page }) => {
    await goToOrganizationSettings(page);
    await page.getByLabel('Team Name').fill('NEW ORG NAME');
    await page.getByLabel('Team Name').press('Enter');
    await page.getByLabel('Team Name').press('Meta+r');
    await expect(page.getByTestId('organization_switcher')).toContainText(
        'NEW ORG NAME'
    );
});

test('test that new manager can be invited', async ({ page }) => {
    await goToOrganizationSettings(page);
    const editorId = Math.round(Math.random() * 10000);
    await page.getByLabel('Email').fill(`new+${editorId}@editor.test`);
    await page.getByRole('button', { name: 'Manager' }).click();
    await page.getByRole('button', { name: 'Add' }).click();
    await page.reload();
    await expect(page.getByRole('main')).toContainText(
        `new+${editorId}@editor.test`
    );
});

test('test that new employee can be invited', async ({ page }) => {
    await goToOrganizationSettings(page);
    const editorId = Math.round(Math.random() * 10000);
    await page.getByLabel('Email').fill(`new+${editorId}@editor.test`);
    await page.getByRole('button', { name: 'Employee' }).click();
    await page.getByRole('button', { name: 'Add' }).click();
    await page.reload();
    await expect(page.getByRole('main')).toContainText(
        `new+${editorId}@editor.test`
    );
});

test('test that new admin can be invited', async ({ page }) => {
    await goToOrganizationSettings(page);
    const adminId = Math.round(Math.random() * 10000);
    await page.getByLabel('Email').fill(`new+${adminId}@admin.test`);
    await page.getByRole('button', { name: 'Administrator' }).click();
    await page.getByRole('button', { name: 'Add' }).click();
    await page.reload();
    await expect(page.getByRole('main')).toContainText(
        `new+${adminId}@admin.test`
    );
});
test('test that error shows if no role is selected', async ({ page }) => {
    await goToOrganizationSettings(page);
    const noRoleId = Math.round(Math.random() * 10000);

    await page.getByLabel('Email').fill(`new+${noRoleId}@norole.test`);
    await page.getByRole('button', { name: 'Add' }).click();
    await expect(page.getByRole('main')).toContainText(
        'The role field is required.'
    );
});
