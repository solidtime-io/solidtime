import { expect, test } from '../playwright/fixtures';
import { PLAYWRIGHT_BASE_URL } from '../playwright/config';
import { getInvitationAcceptUrl } from './utils/mailpit';

test('prefills the registration email after accepting an invitation', async ({ page, browser }) => {
    const memberId = `${Date.now()}-${Math.floor(Math.random() * 100000)}`;
    const memberEmail = `prefill-${memberId}@invitation.test`;

    await page.goto(PLAYWRIGHT_BASE_URL + '/members');
    await page.getByRole('button', { name: 'Invite Member' }).click();
    await page.getByPlaceholder('Member Email').fill(memberEmail);
    await page.getByRole('button', { name: 'Employee' }).click();
    await Promise.all([
        page.waitForResponse(
            (response) =>
                response.url().includes('/invitations') &&
                response.request().method() === 'POST' &&
                response.status() === 204
        ),
        page.getByRole('button', { name: 'Invite Member', exact: true }).click(),
    ]);

    const inviteeContext = await browser.newContext();
    const inviteePage = await inviteeContext.newPage();
    const acceptUrl = await getInvitationAcceptUrl(inviteePage.request, memberEmail);
    await inviteePage.goto(acceptUrl);
    await inviteePage.waitForURL(/\/register$/);

    await expect(inviteePage.getByLabel('Email')).toHaveValue(memberEmail);

    await inviteeContext.close();
});
