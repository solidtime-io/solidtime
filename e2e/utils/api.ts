import { expect } from '@playwright/test';
import type { APIRequestContext, Page } from '@playwright/test';
import { PLAYWRIGHT_BASE_URL } from '../../playwright/config';

// ──────────────────────────────────────────────────
// Types
// ──────────────────────────────────────────────────

export interface TestContext {
    request: APIRequestContext;
    orgId: string;
    memberId: string;
}

// ──────────────────────────────────────────────────
// Auth helpers
// ──────────────────────────────────────────────────

async function getApiHeaders(page: Page): Promise<Record<string, string>> {
    const cookies = await page.context().cookies();
    const xsrfCookie = cookies.find((c) => c.name === 'XSRF-TOKEN');
    return {
        Accept: 'application/json',
        ...(xsrfCookie ? { 'X-XSRF-TOKEN': decodeURIComponent(xsrfCookie.value) } : {}),
    };
}

// ──────────────────────────────────────────────────
// Context setup
// ──────────────────────────────────────────────────

export async function setupTestContext(page: Page): Promise<TestContext> {
    const request = page.request;
    const headers = await getApiHeaders(page);
    const orgId = await getOrganizationId(request, headers);
    const memberId = await getCurrentMemberId(request, orgId, headers);
    return { request: createAuthenticatedRequest(request, headers), orgId, memberId };
}

function createAuthenticatedRequest(
    request: APIRequestContext,
    headers: Record<string, string>
): APIRequestContext {
    // Wrap the request to always include auth headers
    return new Proxy(request, {
        get(target, prop) {
            if (
                prop === 'get' ||
                prop === 'post' ||
                prop === 'put' ||
                prop === 'delete' ||
                prop === 'patch'
            ) {
                return (url: string, options?: Record<string, unknown>) => {
                    return target[prop as 'get'](url, {
                        ...options,
                        headers: {
                            ...headers,
                            ...((options?.headers as Record<string, string>) || {}),
                        },
                    });
                };
            }
            return target[prop as keyof APIRequestContext];
        },
    });
}

async function getOrganizationId(
    request: APIRequestContext,
    headers: Record<string, string>
): Promise<string> {
    const response = await request.get(`${PLAYWRIGHT_BASE_URL}/api/v1/users/me/memberships`, {
        headers,
    });
    expect(response.status()).toBe(200);
    const body = await response.json();
    return body.data[0].organization.id;
}

async function getCurrentMemberId(
    request: APIRequestContext,
    orgId: string,
    headers: Record<string, string>
): Promise<string> {
    const response = await request.get(
        `${PLAYWRIGHT_BASE_URL}/api/v1/organizations/${orgId}/members`,
        { headers }
    );
    expect(response.status()).toBe(200);
    const body = await response.json();
    return body.data[0].id;
}

// ──────────────────────────────────────────────────
// Duration parsing
// ──────────────────────────────────────────────────

function parseDurationToSeconds(duration: string): number {
    let totalSeconds = 0;

    // Match patterns like "1h", "30min", "2h 30min", "1h 7min"
    const hourMatch = duration.match(/(\d+)\s*h/);
    const minMatch = duration.match(/(\d+)\s*min/);

    if (hourMatch) {
        totalSeconds += parseInt(hourMatch[1], 10) * 3600;
    }
    if (minMatch) {
        totalSeconds += parseInt(minMatch[1], 10) * 60;
    }

    // If no h/min pattern matched, try plain number as minutes
    if (!hourMatch && !minMatch) {
        const plainNumber = parseInt(duration, 10);
        if (!isNaN(plainNumber)) {
            totalSeconds = plainNumber * 60;
        }
    }

    return totalSeconds;
}

function createTimestamps(duration: string): { start: string; end: string } {
    const durationSeconds = parseDurationToSeconds(duration);
    const now = new Date();
    const start = new Date(now.getFullYear(), now.getMonth(), now.getDate(), 9, 0, 0);
    const end = new Date(start.getTime() + durationSeconds * 1000);

    return {
        start: formatTimestamp(start),
        end: formatTimestamp(end),
    };
}

function formatTimestamp(date: Date): string {
    return date.toISOString().replace(/\.\d{3}Z$/, 'Z');
}

function randomColor(): string {
    const colors = [
        '#ef5350',
        '#ab47bc',
        '#5c6bc0',
        '#29b6f6',
        '#26a69a',
        '#9ccc65',
        '#ffa726',
        '#8d6e63',
    ];
    return colors[Math.floor(Math.random() * colors.length)];
}

// ──────────────────────────────────────────────────
// Entity creation
// ──────────────────────────────────────────────────

export async function createPublicProjectViaApi(
    ctx: TestContext,
    data: {
        name: string;
        is_billable?: boolean;
        billable_rate?: number | null;
        client_id?: string | null;
    }
) {
    return createProjectViaApi(ctx, {
        ...data,
        is_public: true,
    });
}

export async function createProjectViaApi(
    ctx: TestContext,
    data: {
        name: string;
        color?: string;
        is_billable?: boolean;
        billable_rate?: number | null;
        client_id?: string | null;
        estimated_time?: number | null;
        is_public?: boolean;
    }
) {
    const response = await ctx.request.post(
        `${PLAYWRIGHT_BASE_URL}/api/v1/organizations/${ctx.orgId}/projects`,
        {
            data: {
                name: data.name,
                color: data.color ?? randomColor(),
                is_billable: data.is_billable ?? false,
                billable_rate: data.billable_rate ?? null,
                client_id: data.client_id ?? null,
                estimated_time: data.estimated_time ?? null,
                is_public: data.is_public ?? false,
            },
        }
    );
    expect(response.status()).toBe(201);
    const body = await response.json();
    return body.data as { id: string; name: string; color: string; is_billable: boolean };
}

export async function archiveProjectViaApi(
    ctx: TestContext,
    project: {
        id: string;
        name: string;
        color: string;
        is_billable: boolean;
        client_id?: string | null;
        billable_rate?: number | null;
        estimated_time?: number | null;
    }
) {
    const response = await ctx.request.put(
        `${PLAYWRIGHT_BASE_URL}/api/v1/organizations/${ctx.orgId}/projects/${project.id}`,
        {
            data: {
                name: project.name,
                color: project.color,
                is_billable: project.is_billable,
                is_archived: true,
                client_id: project.client_id ?? null,
                billable_rate: project.billable_rate ?? null,
                estimated_time: project.estimated_time ?? null,
            },
        }
    );
    expect(response.status()).toBe(200);
    const body = await response.json();
    return body.data;
}

export async function createBillableProjectViaApi(
    ctx: TestContext,
    data: { name: string; billable_rate?: number | null }
) {
    return createProjectViaApi(ctx, {
        name: data.name,
        is_billable: true,
        billable_rate: data.billable_rate ?? null,
    });
}

export async function createClientViaApi(ctx: TestContext, data: { name: string }) {
    const response = await ctx.request.post(
        `${PLAYWRIGHT_BASE_URL}/api/v1/organizations/${ctx.orgId}/clients`,
        { data: { name: data.name } }
    );
    expect(response.status()).toBe(201);
    const body = await response.json();
    return body.data as { id: string; name: string };
}

export async function createProjectWithClientViaApi(
    ctx: TestContext,
    projectName: string,
    clientName: string
) {
    const client = await createClientViaApi(ctx, { name: clientName });
    const project = await createProjectViaApi(ctx, {
        name: projectName,
        client_id: client.id,
    });
    return { project, client };
}

export async function createTaskViaApi(
    ctx: TestContext,
    data: { name: string; project_id: string }
) {
    const response = await ctx.request.post(
        `${PLAYWRIGHT_BASE_URL}/api/v1/organizations/${ctx.orgId}/tasks`,
        {
            data: {
                name: data.name,
                project_id: data.project_id,
            },
        }
    );
    expect(response.status()).toBe(201);
    const body = await response.json();
    return body.data as { id: string; name: string; project_id: string };
}

export async function createTagViaApi(ctx: TestContext, data: { name: string }) {
    const response = await ctx.request.post(
        `${PLAYWRIGHT_BASE_URL}/api/v1/organizations/${ctx.orgId}/tags`,
        { data: { name: data.name } }
    );
    expect(response.status()).toBe(201);
    const body = await response.json();
    return body.data as { id: string; name: string };
}

export async function createTimeEntryViaApi(
    ctx: TestContext,
    data: {
        description?: string;
        duration: string;
        projectId?: string | null;
        taskId?: string | null;
        tags?: string[];
        billable?: boolean;
    }
) {
    const { start, end } = createTimestamps(data.duration);
    const response = await ctx.request.post(
        `${PLAYWRIGHT_BASE_URL}/api/v1/organizations/${ctx.orgId}/time-entries`,
        {
            data: {
                member_id: ctx.memberId,
                start,
                end,
                description: data.description ?? '',
                project_id: data.projectId ?? null,
                task_id: data.taskId ?? null,
                tags: data.tags ?? [],
                billable: data.billable ?? false,
            },
        }
    );
    expect(response.status()).toBe(201);
    const body = await response.json();
    return body.data as { id: string; start: string; end: string; description: string };
}

export async function createProjectMemberViaApi(
    ctx: TestContext,
    projectId: string,
    data: { member_id: string; billable_rate?: number | null }
) {
    const response = await ctx.request.post(
        `${PLAYWRIGHT_BASE_URL}/api/v1/organizations/${ctx.orgId}/projects/${projectId}/project-members`,
        {
            data: {
                member_id: data.member_id,
                billable_rate: data.billable_rate ?? null,
            },
        }
    );
    expect(response.status()).toBe(201);
    const body = await response.json();
    return body.data as { id: string; billable_rate: number | null };
}

export async function getMembersViaApi(ctx: TestContext) {
    const response = await ctx.request.get(
        `${PLAYWRIGHT_BASE_URL}/api/v1/organizations/${ctx.orgId}/members`
    );
    expect(response.status()).toBe(200);
    const body = await response.json();
    return body.data as Array<{
        id: string;
        name: string;
        email: string;
        role: string;
        billable_rate: number | null;
        is_placeholder: boolean;
    }>;
}

export async function updateMemberBillableRateViaApi(
    ctx: TestContext,
    memberId: string,
    billableRate: number | null
) {
    const response = await ctx.request.put(
        `${PLAYWRIGHT_BASE_URL}/api/v1/organizations/${ctx.orgId}/members/${memberId}`,
        { data: { billable_rate: billableRate } }
    );
    expect(response.status()).toBe(200);
    const body = await response.json();
    return body.data;
}

// ──────────────────────────────────────────────────
// Composite helpers (matching existing UI helper signatures)
// ──────────────────────────────────────────────────

export async function createTimeEntryWithProjectViaApi(
    ctx: TestContext,
    projectName: string,
    duration: string
) {
    const project = await createProjectViaApi(ctx, { name: projectName });
    const entry = await createTimeEntryViaApi(ctx, {
        description: `Entry for ${projectName}`,
        duration,
        projectId: project.id,
    });
    return { project, entry };
}

export async function createTimeEntryWithProjectAndTaskViaApi(
    ctx: TestContext,
    projectId: string,
    taskName: string,
    projectName: string,
    duration: string
) {
    const task = await createTaskViaApi(ctx, { name: taskName, project_id: projectId });
    const entry = await createTimeEntryViaApi(ctx, {
        description: `Entry for ${projectName} - ${taskName}`,
        duration,
        projectId,
        taskId: task.id,
    });
    return { task, entry };
}

export async function createTimeEntryWithTagViaApi(
    ctx: TestContext,
    tagName: string,
    duration: string
) {
    const tag = await createTagViaApi(ctx, { name: tagName });
    const entry = await createTimeEntryViaApi(ctx, {
        description: `Entry with tag ${tagName}`,
        duration,
        tags: [tag.id],
    });
    return { tag, entry };
}

export async function createBareTimeEntryViaApi(
    ctx: TestContext,
    description: string,
    duration: string
) {
    return createTimeEntryViaApi(ctx, { description, duration });
}

export async function createTimeEntryWithBillableStatusViaApi(
    ctx: TestContext,
    isBillable: boolean,
    duration: string
) {
    return createTimeEntryViaApi(ctx, {
        description: `Time entry ${isBillable ? 'billable' : 'non-billable'}`,
        duration,
        billable: isBillable,
    });
}

// ──────────────────────────────────────────────────
// Import helper (for placeholder member creation)
// ──────────────────────────────────────────────────

export async function createPlaceholderMemberViaImportApi(
    ctx: TestContext,
    placeholderName: string
) {
    const placeholderEmail = `placeholder+${Math.floor(Math.random() * 100000)}@solidtime-import.test`;
    const csvContent = [
        'User,Email,Client,Project,Task,Description,Billable,Start date,Start time,End date,End time,Tags',
        `${placeholderName},${placeholderEmail},,,,Imported entry,No,2024-01-01,09:00:00,2024-01-01,10:00:00,`,
    ].join('\n');

    const base64Data = Buffer.from(csvContent).toString('base64');

    const response = await ctx.request.post(
        `${PLAYWRIGHT_BASE_URL}/api/v1/organizations/${ctx.orgId}/import`,
        {
            data: {
                type: 'toggl_time_entries',
                data: base64Data,
            },
        }
    );
    expect(response.status()).toBe(200);
    return await response.json();
}

// ──────────────────────────────────────────────────
// Organization settings helpers
// ──────────────────────────────────────────────────

export async function updateOrganizationSettingViaApi(
    ctx: TestContext,
    settings: Record<string, unknown>
) {
    const response = await ctx.request.put(
        `${PLAYWRIGHT_BASE_URL}/api/v1/organizations/${ctx.orgId}`,
        { data: settings }
    );
    expect(response.status()).toBe(200);
    const body = await response.json();
    return body.data;
}

export async function updateOrganizationCurrencyViaWeb(
    ctx: TestContext,
    currency: string,
    name: string = 'Test Organization'
) {
    const response = await ctx.request.put(`${PLAYWRIGHT_BASE_URL}/teams/${ctx.orgId}`, {
        data: { name, currency },
    });
    expect(response.status()).toBe(200);
}

// ──────────────────────────────────────────────────
// Bulk helpers
// ──────────────────────────────────────────────────

export async function createMultipleTimeEntriesViaApi(
    ctx: TestContext,
    count: number,
    data: { description?: string; duration?: string } = {}
) {
    const entries = [];
    for (let i = 0; i < count; i++) {
        const entry = await createTimeEntryViaApi(ctx, {
            description: data.description ?? `Bulk entry ${i + 1}`,
            duration: data.duration ?? '30min',
        });
        entries.push(entry);
    }
    return entries;
}

// ──────────────────────────────────────────────────
// Invitation helpers
// ──────────────────────────────────────────────────

export async function getInvitationsViaApi(ctx: TestContext) {
    const response = await ctx.request.get(
        `${PLAYWRIGHT_BASE_URL}/api/v1/organizations/${ctx.orgId}/invitations`
    );
    expect(response.status()).toBe(200);
    const body = await response.json();
    return body.data as Array<{ id: string; email: string; role: string }>;
}
