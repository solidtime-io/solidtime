import { expect } from '@playwright/test';
import type { APIRequestContext } from '@playwright/test';
import { MAILPIT_BASE_URL } from '../../playwright/config';

/**
 * Search for emails in Mailpit matching the given query.
 */
export async function searchEmails(
    request: APIRequestContext,
    query: string
): Promise<{ messages: Array<{ ID: string; Subject: string }> }> {
    const response = await request.get(`${MAILPIT_BASE_URL}/api/v1/search?query=${query}`);
    return response.json();
}

/**
 * Get the full email message from Mailpit by ID.
 */
export async function getMessage(
    request: APIRequestContext,
    messageId: string
): Promise<{ HTML: string; Text: string }> {
    const response = await request.get(`${MAILPIT_BASE_URL}/api/v1/message/${messageId}`);
    return response.json();
}

/**
 * Find the invitation acceptance URL from a Mailpit email sent to the given address.
 * Retries a few times to allow for email delivery delay.
 */
export async function getInvitationAcceptUrl(
    request: APIRequestContext,
    recipientEmail: string
): Promise<string> {
    let searchResult: { messages: Array<{ ID: string }> } = { messages: [] };

    // Retry up to 5 times with 500ms delay to allow for email delivery
    for (let attempt = 0; attempt < 5; attempt++) {
        searchResult = await searchEmails(
            request,
            `to:${encodeURIComponent(recipientEmail)} subject:"Organization Invitation"`
        );
        if (searchResult.messages.length > 0) break;
        await new Promise((resolve) => setTimeout(resolve, 500));
    }
    expect(searchResult.messages.length).toBeGreaterThan(0);

    const message = await getMessage(request, searchResult.messages[0].ID);
    const acceptUrlMatch = message.HTML.match(/href="([^"]*team-invitations[^"]*)"/);
    expect(acceptUrlMatch).toBeTruthy();

    return acceptUrlMatch![1].replace(/&amp;/g, '&');
}

/**
 * Find the password reset URL from a Mailpit email sent to the given address.
 * Retries a few times to allow for email delivery delay.
 */
export async function getPasswordResetUrl(
    request: APIRequestContext,
    recipientEmail: string
): Promise<string> {
    let searchResult: { messages: Array<{ ID: string }> } = { messages: [] };

    // Retry up to 5 times with 500ms delay to allow for email delivery
    for (let attempt = 0; attempt < 5; attempt++) {
        searchResult = await searchEmails(
            request,
            `to:${encodeURIComponent(recipientEmail)} subject:"Reset Password"`
        );
        if (searchResult.messages.length > 0) break;
        await new Promise((resolve) => setTimeout(resolve, 500));
    }
    expect(searchResult.messages.length).toBeGreaterThan(0);

    const message = await getMessage(request, searchResult.messages[0].ID);
    const resetUrlMatch = message.HTML.match(/href="([^"]*reset-password[^"]*)"/);
    expect(resetUrlMatch).toBeTruthy();

    return resetUrlMatch![1].replace(/&amp;/g, '&');
}