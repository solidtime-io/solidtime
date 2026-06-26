import { expect } from '@playwright/test';
import { test } from '../playwright/fixtures';
import {
    goToReportingDetailed,
    waitForDetailedReportingUpdate,
} from './utils/reporting';
import { createTimeEntryWithTagViaApi } from './utils/api';

// Each test registers a new user and creates test data via the API
test.describe.configure({ timeout: 30000 });

test('detailed reporting: "Does Not Contain" excludes entries with the selected tag', async ({
    page,
    ctx,
}) => {
    const tagA = 'MatchTagA ' + Math.floor(Math.random() * 10000);
    const tagB = 'MatchTagB ' + Math.floor(Math.random() * 10000);
    await createTimeEntryWithTagViaApi(ctx, tagA, '1h');
    await createTimeEntryWithTagViaApi(ctx, tagB, '2h');

    await goToReportingDetailed(page);
    await expect(page.getByText(`Entry with tag ${tagA}`).first()).toBeVisible();
    await expect(page.getByText(`Entry with tag ${tagB}`).first()).toBeVisible();

    // Open the Tags dropdown, select tagA, then switch the match mode to "Does Not Contain"
    await page.getByRole('button', { name: 'Tags' }).click();
    await Promise.all([
        waitForDetailedReportingUpdate(page),
        page.getByRole('option').filter({ hasText: tagA }).click(),
    ]);
    await Promise.all([
        waitForDetailedReportingUpdate(page),
        page.getByRole('radio', { name: 'Does Not Contain', exact: true }).click(),
    ]);
    await page.keyboard.press('Escape');

    // The entry with tagA is excluded; the entry with tagB remains
    await expect(page.getByText(`Entry with tag ${tagA}`)).toHaveCount(0);
    await expect(page.getByText(`Entry with tag ${tagB}`).first()).toBeVisible();
});

test('detailed reporting: toggling between "Contains" and "Does Not Contain" flips the result', async ({
    page,
    ctx,
}) => {
    const tagA = 'ToggleTagA ' + Math.floor(Math.random() * 10000);
    const tagB = 'ToggleTagB ' + Math.floor(Math.random() * 10000);
    await createTimeEntryWithTagViaApi(ctx, tagA, '1h');
    await createTimeEntryWithTagViaApi(ctx, tagB, '2h');

    await goToReportingDetailed(page);
    await page.getByRole('button', { name: 'Tags' }).click();
    await Promise.all([
        waitForDetailedReportingUpdate(page),
        page.getByRole('option').filter({ hasText: tagA }).click(),
    ]);

    // "Contains" tagA -> only the tagA entry is listed
    await page.keyboard.press('Escape');
    await expect(page.getByText(`Entry with tag ${tagA}`).first()).toBeVisible();
    await expect(page.getByText(`Entry with tag ${tagB}`)).toHaveCount(0);

    // "Does Not Contain" tagA -> flips to the tagB entry
    await page.getByRole('button', { name: 'Tags' }).click();
    await Promise.all([
        waitForDetailedReportingUpdate(page),
        page.getByRole('radio', { name: 'Does Not Contain', exact: true }).click(),
    ]);
    await page.keyboard.press('Escape');
    await expect(page.getByText(`Entry with tag ${tagB}`).first()).toBeVisible();
    await expect(page.getByText(`Entry with tag ${tagA}`)).toHaveCount(0);
});
