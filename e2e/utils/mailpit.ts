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
    const acceptUrlMatch = message.HTML.match(
        /href="([^"]*(?:organization-invitations|team-invitations)[^"]*)"/
    );
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

/**
 * Count emails matching the given subject sent to the given address.
 */
export async function countEmailsWithSubject(
    request: APIRequestContext,
    recipientEmail: string,
    subject: string
): Promise<number> {
    const searchResult = await searchEmails(
        request,
        `to:${encodeURIComponent(recipientEmail)} subject:"${subject}"`
    );
    return searchResult.messages.length;
}

/**
 * Poll Mailpit until the count of matching emails reaches `min`, or 5 attempts
 * (~2.5s) elapse. Returns the final count.
 */
export async function waitForEmailCount(
    request: APIRequestContext,
    recipientEmail: string,
    subject: string,
    min: number
): Promise<number> {
    let count = 0;
    for (let attempt = 0; attempt < 5; attempt++) {
        count = await countEmailsWithSubject(request, recipientEmail, subject);
        if (count >= min) break;
        await new Promise((r) => setTimeout(r, 500));
    }
    return count;
}

/**
 * Find the email-change verification URL from a Mailpit email sent to the given address.
 * Retries a few times to allow for email delivery delay.
 */
export async function getEmailChangeVerificationUrl(
    request: APIRequestContext,
    recipientEmail: string
): Promise<string> {
    let searchResult: { messages: Array<{ ID: string }> } = { messages: [] };

    for (let attempt = 0; attempt < 5; attempt++) {
        searchResult = await searchEmails(
            request,
            `to:${encodeURIComponent(recipientEmail)} subject:"Verify Email Address"`
        );
        if (searchResult.messages.length > 0) break;
        await new Promise((resolve) => setTimeout(resolve, 500));
    }
    expect(searchResult.messages.length).toBeGreaterThan(0);

    const message = await getMessage(request, searchResult.messages[0].ID);
    const verifyUrlMatch = message.HTML.match(/href="([^"]*verify-email-change[^"]*)"/);
    expect(verifyUrlMatch).toBeTruthy();

    return verifyUrlMatch![1].replace(/&amp;/g, '&');
}
