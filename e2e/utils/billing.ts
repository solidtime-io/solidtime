import { expect } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../../playwright/config';
import type { TestContext } from './api';

/**
 * End the trial of the test organization, only works with the Billing extension. Reload the page
 * afterwards, the page props still contain the trial otherwise.
 */
export async function cancelTrialViaApi(ctx: TestContext): Promise<void> {
    const response = await ctx.request.post(
        `${PLAYWRIGHT_BASE_URL}/api/v1/organizations/${ctx.orgId}/trial/cancel`
    );
    expect(response.status()).toBe(204);
}
