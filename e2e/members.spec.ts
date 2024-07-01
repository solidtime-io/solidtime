// TODO: Edit Billable Rate

import { test } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';

async function goToMembersSection(page) {
    await page.goto(PLAYWRIGHT_BASE_URL + '/members');
}

test('test that member billable rate can be updated', async ({ page }) => {
    await goToMembersSection(page);
    const newBillableRate = Math.round(Math.random() * 10000);
    await page.getByRole('row').first().getByRole('button').click();
    await page.getByRole('button').getByText('Edit').first().click();
    await page.getByText('Organization Default Rate').click();
    await page.getByText('Custom Rate').click();
    await page
        .getByPlaceholder('Billable Rate')
        .fill(newBillableRate.toString());
    await page.getByRole('button', { name: 'Update Member' }).click();

    await Promise.all([
        page
            .getByRole('button', { name: 'No, only for new time entries' })
            .click(),
        page.waitForRequest(
            async (request) =>
                request.url().includes('/members/') &&
                request.method() === 'PUT' &&
                request.postDataJSON().billable_rate ===
                    newBillableRate * 100 &&
                request.postDataJSON().billable_rate_update_time_entries ===
                    false
        ),
        /* page.waitForResponse(
            async (response) =>
                response.url().includes("/organizations/") &&
                response.request().method() === "PUT" &&
                response.status() === 200 &&
                (await response.json()).data.billable_rate === (newBillableRate * 100)
        )*/
    ]);
});

test('test that organization billable rate can be updated with all existing time entries', async ({
    page,
}) => {
    await goToMembersSection(page);
    const newBillableRate = Math.round(Math.random() * 10000);
    await page.getByRole('row').first().getByRole('button').click();
    await page.getByRole('button').getByText('Edit').first().click();
    await page.getByText('Organization Default Rate').click();
    await page.getByText('Custom Rate').click();
    await page
        .getByPlaceholder('Billable Rate')
        .fill(newBillableRate.toString());
    await page.getByRole('button', { name: 'Update Member' }).click();

    await Promise.all([
        page.getByRole('button', { name: 'Yes, update existing time' }).click(),
        page.waitForRequest(
            async (request) =>
                request.url().includes('/members/') &&
                request.method() === 'PUT' &&
                request.postDataJSON().billable_rate ===
                    newBillableRate * 100 &&
                request.postDataJSON().billable_rate_update_time_entries ===
                    true
        ),
        /* page.waitForResponse(
            async (response) =>
                response.url().includes("/organizations/") &&
                response.request().method() === "PUT" &&
                response.status() === 200 &&
                (await response.json()).data.billable_rate === (newBillableRate * 100)
        )*/
    ]);
});
