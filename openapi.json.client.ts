import { makeApi, Zodios, type ZodiosOptions } from '@zodios/core';
import { z } from 'zod';

const ClientResource = z
    .object({
        id: z.string(),
        name: z.string(),
        is_archived: z.boolean(),
        created_at: z.string(),
        updated_at: z.string(),
    })
    .passthrough();
const ClientCollection = z.array(ClientResource);
const updateClient_Body = z
    .object({ name: z.string(), is_archived: z.boolean().optional() })
    .passthrough();
const importData_Body = z
    .object({ type: z.string(), data: z.string() })
    .passthrough();
const InvitationResource = z
    .object({ id: z.string(), email: z.string(), role: z.string() })
    .passthrough();
const Role = z.enum(['owner', 'admin', 'manager', 'employee', 'placeholder']);
const invite_Body = z
    .object({ email: z.string().email(), role: Role })
    .passthrough();
const MemberPivotResource = z
    .object({
        id: z.string(),
        user_id: z.string(),
        name: z.string(),
        email: z.string(),
        role: z.string(),
        is_placeholder: z.boolean(),
        billable_rate: z.union([z.number(), z.null()]),
    })
    .passthrough();
const updateMember_Body = z
    .object({
        role: Role,
        billable_rate: z.union([z.number(), z.null()]),
        billable_rate_update_time_entries: z.boolean(),
    })
    .partial()
    .passthrough();
const MemberResource = z
    .object({
        id: z.string(),
        user_id: z.string(),
        name: z.string(),
        email: z.string(),
        role: z.string(),
        is_placeholder: z.boolean(),
        billable_rate: z.union([z.number(), z.null()]),
    })
    .passthrough();
const OrganizationResource = z
    .object({
        id: z.string(),
        name: z.string(),
        is_personal: z.boolean(),
        billable_rate: z.union([z.number(), z.null()]),
    })
    .passthrough();
const updateOrganization_Body = z
    .object({
        name: z.string(),
        billable_rate: z.union([z.number(), z.null()]).optional(),
        billable_rate_update_time_entries: z.boolean().optional(),
    })
    .passthrough();
const ProjectResource = z
    .object({
        id: z.string(),
        name: z.string(),
        color: z.string(),
        client_id: z.union([z.string(), z.null()]),
        is_archived: z.boolean(),
        billable_rate: z.union([z.number(), z.null()]),
        is_billable: z.boolean(),
    })
    .passthrough();
const createProject_Body = z
    .object({
        name: z.string(),
        color: z.string(),
        is_billable: z.boolean(),
        billable_rate: z.union([z.number(), z.null()]).optional(),
        client_id: z.union([z.string(), z.null()]).optional(),
    })
    .passthrough();
const updateProject_Body = z
    .object({
        name: z.string(),
        color: z.string(),
        is_billable: z.boolean(),
        is_archived: z.boolean().optional(),
        client_id: z.union([z.string(), z.null()]).optional(),
        billable_rate: z.union([z.number(), z.null()]).optional(),
        billable_rate_update_time_entries: z.boolean().optional(),
    })
    .passthrough();
const ProjectMemberResource = z
    .object({
        id: z.string(),
        billable_rate: z.union([z.number(), z.null()]),
        member_id: z.string(),
        project_id: z.string(),
    })
    .passthrough();
const createProjectMember_Body = z
    .object({
        member_id: z.string().uuid(),
        billable_rate: z.union([z.number(), z.null()]).optional(),
    })
    .passthrough();
const updateProjectMember_Body = z
    .object({
        billable_rate: z.union([z.number(), z.null()]),
        billable_rate_update_time_entries: z.boolean(),
    })
    .partial()
    .passthrough();
const TagResource = z
    .object({
        id: z.string(),
        name: z.string(),
        created_at: z.string(),
        updated_at: z.string(),
    })
    .passthrough();
const TagCollection = z.array(TagResource);
const TaskResource = z
    .object({
        id: z.string(),
        name: z.string(),
        is_done: z.boolean(),
        project_id: z.string(),
        created_at: z.string(),
        updated_at: z.string(),
    })
    .passthrough();
const createTask_Body = z
    .object({ name: z.string(), project_id: z.string() })
    .passthrough();
const updateTask_Body = z
    .object({ name: z.string(), is_done: z.boolean().optional() })
    .passthrough();
const start = z.union([z.string(), z.null()]).optional();
const TimeEntryResource = z
    .object({
        id: z.string(),
        start: z.string(),
        end: z.union([z.string(), z.null()]),
        duration: z.union([z.number(), z.null()]),
        description: z.union([z.string(), z.null()]),
        task_id: z.union([z.string(), z.null()]),
        project_id: z.union([z.string(), z.null()]),
        organization_id: z.string(),
        user_id: z.string(),
        tags: z.array(z.string()),
        billable: z.boolean(),
    })
    .passthrough();
const TimeEntryCollection = z.array(TimeEntryResource);
const createTimeEntry_Body = z
    .object({
        member_id: z.string().uuid(),
        project_id: z.union([z.string(), z.null()]).optional(),
        task_id: z.union([z.string(), z.null()]).optional(),
        start: z.string(),
        end: z.union([z.string(), z.null()]).optional(),
        billable: z.boolean(),
        description: z.union([z.string(), z.null()]).optional(),
        tags: z.union([z.array(z.string()), z.null()]).optional(),
    })
    .passthrough();
const updateMultipleTimeEntries_Body = z
    .object({
        ids: z.array(z.string()),
        changes: z
            .object({
                member_id: z.string().uuid(),
                project_id: z.union([z.string(), z.null()]),
                task_id: z.union([z.string(), z.null()]),
                billable: z.boolean(),
                description: z.union([z.string(), z.null()]),
                tags: z.union([z.array(z.string()), z.null()]),
            })
            .partial()
            .passthrough(),
    })
    .passthrough();
const updateTimeEntry_Body = z
    .object({
        member_id: z.string().uuid(),
        project_id: z.union([z.string(), z.null()]),
        task_id: z.union([z.string(), z.null()]),
        start: z.string(),
        end: z.union([z.string(), z.null()]),
        billable: z.boolean(),
        description: z.union([z.string(), z.null()]),
        tags: z.union([z.array(z.string()), z.null()]),
    })
    .partial()
    .passthrough();

export const schemas = {
    ClientResource,
    ClientCollection,
    updateClient_Body,
    importData_Body,
    InvitationResource,
    Role,
    invite_Body,
    MemberPivotResource,
    updateMember_Body,
    MemberResource,
    OrganizationResource,
    updateOrganization_Body,
    ProjectResource,
    createProject_Body,
    updateProject_Body,
    ProjectMemberResource,
    createProjectMember_Body,
    updateProjectMember_Body,
    TagResource,
    TagCollection,
    TaskResource,
    createTask_Body,
    updateTask_Body,
    start,
    TimeEntryResource,
    TimeEntryCollection,
    createTimeEntry_Body,
    updateMultipleTimeEntries_Body,
    updateTimeEntry_Body,
};

const endpoints = makeApi([
    {
        method: 'get',
        path: '/v1/organizations/:organization',
        alias: 'getOrganization',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: OrganizationResource }).passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization',
        alias: 'updateOrganization',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: updateOrganization_Body,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: OrganizationResource }).passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/clients',
        alias: 'getClients',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'page',
                type: 'Query',
                schema: z.number().int().gte(1).optional(),
            },
            {
                name: 'archived',
                type: 'Query',
                schema: z.enum(['true', 'false', 'all']).optional(),
            },
        ],
        response: z.object({ data: ClientCollection }).passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/clients',
        alias: 'createClient',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: z.object({ name: z.string() }).passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: ClientResource }).passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/clients/:client',
        alias: 'updateClient',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: updateClient_Body,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'client',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: ClientResource }).passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/clients/:client',
        alias: 'deleteClient',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: z.object({}).partial().passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'client',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.null(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({
                        error: z.boolean(),
                        key: z.string(),
                        message: z.string(),
                    })
                    .passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/import',
        alias: 'importData',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: importData_Body,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z
            .object({
                report: z
                    .object({
                        clients: z
                            .object({ created: z.number().int() })
                            .passthrough(),
                        projects: z
                            .object({ created: z.number().int() })
                            .passthrough(),
                        tasks: z
                            .object({ created: z.number().int() })
                            .passthrough(),
                        time_entries: z
                            .object({ created: z.number().int() })
                            .passthrough(),
                        tags: z
                            .object({ created: z.number().int() })
                            .passthrough(),
                        users: z
                            .object({ created: z.number().int() })
                            .passthrough(),
                    })
                    .passthrough(),
            })
            .passthrough(),
        errors: [
            {
                status: 400,
                schema: z.union([
                    z.object({ message: z.string() }).passthrough(),
                    z.object({ message: z.string() }).passthrough(),
                ]),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/importers',
        alias: 'getImporters',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z
            .object({
                data: z.array(
                    z
                        .object({
                            key: z.string(),
                            name: z.string(),
                            description: z.string(),
                        })
                        .passthrough()
                ),
            })
            .passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/invitations',
        alias: 'getInvitations',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z
            .object({
                data: z.array(InvitationResource),
                links: z
                    .object({
                        first: z.union([z.string(), z.null()]),
                        last: z.union([z.string(), z.null()]),
                        prev: z.union([z.string(), z.null()]),
                        next: z.union([z.string(), z.null()]),
                    })
                    .passthrough(),
                meta: z
                    .object({
                        current_page: z.number().int(),
                        from: z.union([z.number(), z.null()]),
                        last_page: z.number().int(),
                        links: z.array(
                            z
                                .object({
                                    url: z.union([z.string(), z.null()]),
                                    label: z.string(),
                                    active: z.boolean(),
                                })
                                .passthrough()
                        ),
                        path: z.union([z.string(), z.null()]),
                        per_page: z.number().int(),
                        to: z.union([z.number(), z.null()]),
                        total: z.number().int(),
                    })
                    .passthrough(),
            })
            .passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/invitations',
        alias: 'invite',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: invite_Body,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.null(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/invitations/:invitation',
        alias: 'removeInvitation',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: z.object({}).partial().passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'invitation',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.null(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/invitations/:invitation/resend',
        alias: 'resendInvitationEmail',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: z.object({}).partial().passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'invitation',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.null(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/members',
        alias: 'getMembers',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z
            .object({
                data: z.array(MemberPivotResource),
                links: z
                    .object({
                        first: z.union([z.string(), z.null()]),
                        last: z.union([z.string(), z.null()]),
                        prev: z.union([z.string(), z.null()]),
                        next: z.union([z.string(), z.null()]),
                    })
                    .passthrough(),
                meta: z
                    .object({
                        current_page: z.number().int(),
                        from: z.union([z.number(), z.null()]),
                        last_page: z.number().int(),
                        links: z.array(
                            z
                                .object({
                                    url: z.union([z.string(), z.null()]),
                                    label: z.string(),
                                    active: z.boolean(),
                                })
                                .passthrough()
                        ),
                        path: z.union([z.string(), z.null()]),
                        per_page: z.number().int(),
                        to: z.union([z.number(), z.null()]),
                        total: z.number().int(),
                    })
                    .passthrough(),
            })
            .passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/members/:member',
        alias: 'updateMember',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: updateMember_Body,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'member',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: MemberResource }).passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/members/:member',
        alias: 'removeMember',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: z.object({}).partial().passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'member',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.null(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({
                        error: z.boolean(),
                        key: z.string(),
                        message: z.string(),
                    })
                    .passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/members/:member/invite-placeholder',
        alias: 'invitePlaceholder',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: z.object({}).partial().passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'member',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.null(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({
                        error: z.boolean(),
                        key: z.string(),
                        message: z.string(),
                    })
                    .passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/project-members/:projectMember',
        alias: 'updateProjectMember',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: updateProjectMember_Body,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'projectMember',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: ProjectMemberResource }).passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/project-members/:projectMember',
        alias: 'deleteProjectMember',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: z.object({}).partial().passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'projectMember',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.null(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/projects',
        alias: 'getProjects',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'page',
                type: 'Query',
                schema: z.number().int().gte(1).optional(),
            },
            {
                name: 'archived',
                type: 'Query',
                schema: z.enum(['true', 'false', 'all']).optional(),
            },
        ],
        response: z
            .object({
                data: z.array(ProjectResource),
                links: z
                    .object({
                        first: z.union([z.string(), z.null()]),
                        last: z.union([z.string(), z.null()]),
                        prev: z.union([z.string(), z.null()]),
                        next: z.union([z.string(), z.null()]),
                    })
                    .passthrough(),
                meta: z
                    .object({
                        current_page: z.number().int(),
                        from: z.union([z.number(), z.null()]),
                        last_page: z.number().int(),
                        links: z.array(
                            z
                                .object({
                                    url: z.union([z.string(), z.null()]),
                                    label: z.string(),
                                    active: z.boolean(),
                                })
                                .passthrough()
                        ),
                        path: z.union([z.string(), z.null()]),
                        per_page: z.number().int(),
                        to: z.union([z.number(), z.null()]),
                        total: z.number().int(),
                    })
                    .passthrough(),
            })
            .passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/projects',
        alias: 'createProject',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: createProject_Body,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: ProjectResource }).passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/projects/:project',
        alias: 'getProject',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'project',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: ProjectResource }).passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/projects/:project',
        alias: 'updateProject',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: updateProject_Body,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'project',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: ProjectResource }).passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/projects/:project',
        alias: 'deleteProject',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: z.object({}).partial().passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'project',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.null(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({
                        error: z.boolean(),
                        key: z.string(),
                        message: z.string(),
                    })
                    .passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/projects/:project/project-members',
        alias: 'getProjectMembers',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'project',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z
            .object({
                data: z.array(ProjectMemberResource),
                links: z
                    .object({
                        first: z.union([z.string(), z.null()]),
                        last: z.union([z.string(), z.null()]),
                        prev: z.union([z.string(), z.null()]),
                        next: z.union([z.string(), z.null()]),
                    })
                    .passthrough(),
                meta: z
                    .object({
                        current_page: z.number().int(),
                        from: z.union([z.number(), z.null()]),
                        last_page: z.number().int(),
                        links: z.array(
                            z
                                .object({
                                    url: z.union([z.string(), z.null()]),
                                    label: z.string(),
                                    active: z.boolean(),
                                })
                                .passthrough()
                        ),
                        path: z.union([z.string(), z.null()]),
                        per_page: z.number().int(),
                        to: z.union([z.number(), z.null()]),
                        total: z.number().int(),
                    })
                    .passthrough(),
            })
            .passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/projects/:project/project-members',
        alias: 'createProjectMember',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: createProjectMember_Body,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'project',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: ProjectMemberResource }).passthrough(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({
                        error: z.boolean(),
                        key: z.string(),
                        message: z.string(),
                    })
                    .passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/tags',
        alias: 'getTags',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: TagCollection }).passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/tags',
        alias: 'createTag',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: z.object({ name: z.string() }).passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: TagResource }).passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/tags/:tag',
        alias: 'updateTag',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: z.object({ name: z.string() }).passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'tag',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: TagResource }).passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/tags/:tag',
        alias: 'deleteTag',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: z.object({}).partial().passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'tag',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.null(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({
                        error: z.boolean(),
                        key: z.string(),
                        message: z.string(),
                    })
                    .passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/tasks',
        alias: 'getTasks',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'project_id',
                type: 'Query',
                schema: z.string().uuid().optional(),
            },
            {
                name: 'done',
                type: 'Query',
                schema: z.enum(['true', 'false', 'all']).optional(),
            },
        ],
        response: z
            .object({
                data: z.array(TaskResource),
                links: z
                    .object({
                        first: z.union([z.string(), z.null()]),
                        last: z.union([z.string(), z.null()]),
                        prev: z.union([z.string(), z.null()]),
                        next: z.union([z.string(), z.null()]),
                    })
                    .passthrough(),
                meta: z
                    .object({
                        current_page: z.number().int(),
                        from: z.union([z.number(), z.null()]),
                        last_page: z.number().int(),
                        links: z.array(
                            z
                                .object({
                                    url: z.union([z.string(), z.null()]),
                                    label: z.string(),
                                    active: z.boolean(),
                                })
                                .passthrough()
                        ),
                        path: z.union([z.string(), z.null()]),
                        per_page: z.number().int(),
                        to: z.union([z.number(), z.null()]),
                        total: z.number().int(),
                    })
                    .passthrough(),
            })
            .passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/tasks',
        alias: 'createTask',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: createTask_Body,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: TaskResource }).passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/tasks/:task',
        alias: 'updateTask',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: updateTask_Body,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'task',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: TaskResource }).passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/tasks/:task',
        alias: 'deleteTask',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: z.object({}).partial().passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'task',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.null(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({
                        error: z.boolean(),
                        key: z.string(),
                        message: z.string(),
                    })
                    .passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/time-entries',
        alias: 'getTimeEntries',
        description: `If you only need time entries for a specific user, you can filter by &#x60;user_id&#x60;.
Users with the permission &#x60;time-entries:view:own&#x60; can only use this endpoint with their own user ID in the user_id filter.`,
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'member_id',
                type: 'Query',
                schema: z.string().uuid().optional(),
            },
            {
                name: 'start',
                type: 'Query',
                schema: start,
            },
            {
                name: 'end',
                type: 'Query',
                schema: start,
            },
            {
                name: 'active',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
            {
                name: 'billable',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
            {
                name: 'limit',
                type: 'Query',
                schema: z.number().int().gte(1).lte(500).optional(),
            },
            {
                name: 'only_full_dates',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
            {
                name: 'member_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
            {
                name: 'project_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
            {
                name: 'tag_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
            {
                name: 'task_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
        ],
        response: z.object({ data: TimeEntryCollection }).passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'post',
        path: '/v1/organizations/:organization/time-entries',
        alias: 'createTimeEntry',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: createTimeEntry_Body,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: TimeEntryResource }).passthrough(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({
                        error: z.boolean(),
                        key: z.string(),
                        message: z.string(),
                    })
                    .passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'patch',
        path: '/v1/organizations/:organization/time-entries',
        alias: 'updateMultipleTimeEntries',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: updateMultipleTimeEntries_Body,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z
            .object({ success: z.string(), error: z.string() })
            .passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'put',
        path: '/v1/organizations/:organization/time-entries/:timeEntry',
        alias: 'updateTimeEntry',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: updateTimeEntry_Body,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'timeEntry',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.object({ data: TimeEntryResource }).passthrough(),
        errors: [
            {
                status: 400,
                description: `API exception`,
                schema: z
                    .object({
                        error: z.boolean(),
                        key: z.string(),
                        message: z.string(),
                    })
                    .passthrough(),
            },
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'delete',
        path: '/v1/organizations/:organization/time-entries/:timeEntry',
        alias: 'deleteTimeEntry',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: z.object({}).partial().passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'timeEntry',
                type: 'Path',
                schema: z.string(),
            },
        ],
        response: z.null(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/organizations/:organization/time-entries/aggregate',
        alias: 'getAggregatedTimeEntries',
        description: `This endpoint allows you to filter time entries and aggregate them by different criteria.
The parameters &#x60;group&#x60; and &#x60;sub_group&#x60; allow you to group the time entries by different criteria.
If the group parameters are all set to &#x60;null&#x60; or are all missing, the endpoint will aggregate all filtered time entries.`,
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string(),
            },
            {
                name: 'group',
                type: 'Query',
                schema: z
                    .enum([
                        'day',
                        'week',
                        'month',
                        'year',
                        'user',
                        'project',
                        'task',
                        'client',
                        'billable',
                    ])
                    .optional(),
            },
            {
                name: 'sub_group',
                type: 'Query',
                schema: z
                    .enum([
                        'day',
                        'week',
                        'month',
                        'year',
                        'user',
                        'project',
                        'task',
                        'client',
                        'billable',
                    ])
                    .optional(),
            },
            {
                name: 'member_id',
                type: 'Query',
                schema: z.string().uuid().optional(),
            },
            {
                name: 'user_id',
                type: 'Query',
                schema: z.string().uuid().optional(),
            },
            {
                name: 'start',
                type: 'Query',
                schema: start,
            },
            {
                name: 'end',
                type: 'Query',
                schema: start,
            },
            {
                name: 'active',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
            {
                name: 'billable',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
            {
                name: 'fill_gaps_in_time_groups',
                type: 'Query',
                schema: z.enum(['true', 'false']).optional(),
            },
            {
                name: 'member_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
            {
                name: 'project_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
            {
                name: 'client_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
            {
                name: 'tag_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
            {
                name: 'task_ids',
                type: 'Query',
                schema: z.array(z.string()).min(1).optional(),
            },
        ],
        response: z
            .object({
                data: z
                    .object({
                        grouped_type: z.union([z.string(), z.null()]),
                        grouped_data: z.union([
                            z.array(
                                z
                                    .object({
                                        key: z.union([z.string(), z.null()]),
                                        seconds: z.number().int(),
                                        cost: z.number().int(),
                                        grouped_type: z.union([
                                            z.string(),
                                            z.null(),
                                        ]),
                                        grouped_data: z.union([
                                            z.array(
                                                z
                                                    .object({
                                                        key: z.union([
                                                            z.string(),
                                                            z.null(),
                                                        ]),
                                                        seconds: z
                                                            .number()
                                                            .int(),
                                                        cost: z.number().int(),
                                                        grouped_type: z.null(),
                                                        grouped_data: z.null(),
                                                    })
                                                    .passthrough()
                                            ),
                                            z.null(),
                                        ]),
                                    })
                                    .passthrough()
                            ),
                            z.null(),
                        ]),
                        seconds: z.number().int(),
                        cost: z.number().int(),
                    })
                    .passthrough(),
            })
            .passthrough(),
        errors: [
            {
                status: 403,
                description: `Authorization error`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
            {
                status: 422,
                description: `Validation error`,
                schema: z
                    .object({
                        message: z.string(),
                        errors: z.record(z.array(z.string())),
                    })
                    .passthrough(),
            },
        ],
    },
    {
        method: 'get',
        path: '/v1/users/me/time-entries/active',
        alias: 'getMyActiveTimeEntry',
        description: `This endpoint is independent of organization.`,
        requestFormat: 'json',
        response: z.object({ data: TimeEntryResource }).passthrough(),
        errors: [
            {
                status: 404,
                description: `Not found`,
                schema: z.object({ message: z.string() }).passthrough(),
            },
        ],
    },
]);

export const api = new Zodios('/api', endpoints);

export function createApiClient(baseUrl: string, options?: ZodiosOptions) {
    return new Zodios(baseUrl, endpoints, options);
}
