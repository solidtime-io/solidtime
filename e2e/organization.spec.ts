import { test, expect } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';

async function goToOrganizationSettings(page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/dashboard');
    await page.locator('[data-testid="organization_switcher"]:visible').click();
    await page.getByText('Organization Settings').click();
}

test('test that organization name can be updated', async ({ page }) => {
    await goToOrganizationSettings(page);
    await page.getByLabel('Organization Name').fill('NEW ORG NAME');
    await page.getByLabel('Organization Name').press('Enter');
    await page.getByLabel('Organization Name').press('Meta+r');
    await expect(
        page.locator('[data-testid="organization_switcher"]:visible')
    ).toContainText('NEW ORG NAME');
});

// TODO: Add Test for import
