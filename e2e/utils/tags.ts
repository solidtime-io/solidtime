import type { Page } from '@playwright/test';

export function newTagResponse(page: Page, { name = '' } = {}) {
    return page.waitForResponse(async (response) => {
        return (
            response.status() === 201 &&
            (await response.headerValue('Content-Type')) === 'application/json' &&
            (await response.json()).data.name === name
        );
    });
}
