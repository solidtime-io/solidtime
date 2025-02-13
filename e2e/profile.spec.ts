import {test, expect} from '../playwright/fixtures';
import {PLAYWRIGHT_BASE_URL} from '../playwright/config';

test('test that user name can be updated', async ({page}) => {
    await page.goto(PLAYWRIGHT_BASE_URL + '/user/profile');
    await page.getByLabel('Name', {exact: true} ).fill('NEW NAME');
    await Promise.all([
        page.getByRole('button', {name: 'Save'}).first().click(),
        page.waitForResponse('**/user/profile-information'),
    ]);
    await page.reload();
    await expect(page.getByLabel('Name', {exact: true})).toHaveValue('NEW NAME');
});

test.skip('test that user email can be updated', async ({page}) => {
    // this does not work because of email verification currently
    await page.goto(PLAYWRIGHT_BASE_URL + '/user/profile');
    const emailId = Math.round(Math.random() * 10000);
    await page.getByLabel('Email').fill(`newemail+${emailId}@test.com`);
    await page.getByRole('button', {name: 'Save'}).first().click();
    await page.reload();
    await expect(page.getByLabel('Email')).toHaveValue(
        `newemail+${emailId}@test.com`
    );
});

async function createNewApiToken(page) {
    await page.getByLabel('API Key Name').fill('NEW API KEY');
    await Promise.all([
        page.getByRole('button', {name: 'Create API Key'}).click(),
        page.waitForResponse('**/users/me/api-tokens')
    ]);

    await expect(page.locator('body')).toContainText('API Token created successfully');
    await page.getByRole('dialog').getByText('Close').click();
    await expect(page.locator('body')).toContainText('NEW API KEY');
}

test('test that user can create an API key', async ({page}) => {
    await page.goto(PLAYWRIGHT_BASE_URL + '/user/profile');
    await createNewApiToken(page);
});

test('test that user can delete an API key', async ({page}) => {
    await page.goto(PLAYWRIGHT_BASE_URL + '/user/profile');
    await createNewApiToken(page);
    page.getByLabel('Delete API Token NEW API KEY').click();
    await expect(page.getByRole('dialog')).toContainText('Are you sure you would like to delete this API token?');
    await Promise.all([
        page.getByRole('dialog').getByRole('button', {name: 'Delete'}).click(),
        page.waitForResponse('**/users/me/api-tokens')
    ]);
    await expect(page.locator('body')).not.toContainText('NEW API KEY');
});


test('test that user can revoke an API key', async ({page}) => {
    await page.goto(PLAYWRIGHT_BASE_URL + '/user/profile');
    await createNewApiToken(page);
    page.getByLabel('Revoke API Token NEW API KEY').click();
    await expect(page.getByRole('dialog')).toContainText('Are you sure you would like to revoke this API token?');
    await Promise.all([
        page.getByRole('dialog').getByRole('button', {name: 'Revoke'}).click(),
        page.waitForResponse('**/users/me/api-tokens')
    ]);
    await expect(page.getByRole('button', {name: 'Revoke'})).toBeHidden();
    await expect(page.locator('body')).toContainText('NEW API KEY');
    await expect(page.locator('body')).toContainText('Revoked');
});
