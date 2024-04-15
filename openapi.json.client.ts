import { makeApi, Zodios, type ZodiosOptions } from '@zodios/core';
import { z } from 'zod';

const ClientResource = z
    .object({
        id: z.string(),
        name: z.string(),
        created_at: z.string(),
        updated_at: z.string(),
    })
    .passthrough();
const ClientCollection = z.array(ClientResource);
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
        billable_rate: z.union([z.number(), z.null()]).optional(),
        role: Role,
    })
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
        is_personal: z.string(),
        billable_rate: z.union([z.number(), z.null()]),
    })
    .passthrough();
const v1_organizations_update_Body = z
    .object({
        name: z.string(),
        billable_rate: z.union([z.number(), z.null()]).optional(),
    })
    .passthrough();
const ProjectResource = z
    .object({
        id: z.string(),
        name: z.string(),
        color: z.string(),
        client_id: z.union([z.string(), z.null()]),
        billable_rate: z.union([z.number(), z.null()]),
    })
    .passthrough();
const createProject_Body = z
    .object({
        name: z.string(),
        color: z.string(),
        billable_rate: z.union([z.number(), z.null()]).optional(),
        client_id: z.union([z.string(), z.null()]).optional(),
    })
    .passthrough();
const ProjectMemberResource = z
    .object({
        id: z.string(),
        billable_rate: z.union([z.number(), z.null()]),
        user_id: z.string(),
        project_id: z.string(),
    })
    .passthrough();
const createProjectMember_Body = z
    .object({
        user_id: z.string().uuid(),
        billable_rate: z.union([z.number(), z.null()]).optional(),
    })
    .passthrough();
const updateProjectMember_Body = z
    .object({ billable_rate: z.union([z.number(), z.null()]) })
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
        project_id: z.string(),
        created_at: z.string(),
        updated_at: z.string(),
    })
    .passthrough();
const createTask_Body = z
    .object({ name: z.string(), project_id: z.string() })
    .passthrough();
const before = z.union([z.string(), z.null()]).optional();
const TimeEntryResource = z
    .object({
        id: z.string(),
        start: z.string(),
        end: z.union([z.string(), z.null()]),
        duration: z.union([z.number(), z.null()]),
        description: z.union([z.string(), z.null()]),
        task_id: z.union([z.string(), z.null()]),
        project_id: z.union([z.string(), z.null()]),
        user_id: z.string(),
        tags: z.array(z.string()),
        billable: z.boolean(),
    })
    .passthrough();
const TimeEntryCollection = z.array(TimeEntryResource);
const createTimeEntry_Body = z
    .object({
        user_id: z.string().uuid(),
        project_id: z.union([z.string(), z.null()]).optional(),
        task_id: z.union([z.string(), z.null()]).optional(),
        start: z.string(),
        end: z.union([z.string(), z.null()]).optional(),
        billable: z.boolean(),
        description: z.union([z.string(), z.null()]).optional(),
        tags: z.union([z.array(z.string()), z.null()]).optional(),
    })
    .passthrough();
const updateTimeEntry_Body = z
    .object({
        project_id: z.union([z.string(), z.null()]).optional(),
        task_id: z.union([z.string(), z.null()]).optional(),
        start: z.string(),
        end: z.union([z.string(), z.null()]).optional(),
        billable: z.boolean().optional(),
        description: z.union([z.string(), z.null()]).optional(),
        tags: z.union([z.array(z.string()), z.null()]).optional(),
    })
    .passthrough();

export const schemas = {
    ClientResource,
    ClientCollection,
    importData_Body,
    InvitationResource,
    Role,
    invite_Body,
    MemberPivotResource,
    updateMember_Body,
    MemberResource,
    OrganizationResource,
    v1_organizations_update_Body,
    ProjectResource,
    createProject_Body,
    ProjectMemberResource,
    createProjectMember_Body,
    updateProjectMember_Body,
    TagResource,
    TagCollection,
    TaskResource,
    createTask_Body,
    before,
    TimeEntryResource,
    TimeEntryCollection,
    createTimeEntry_Body,
    updateTimeEntry_Body,
};

const endpoints = makeApi([
    {
        method: 'get',
        path: '/v1/organizations/:organization',
        alias: 'v1.organizations.show',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string().uuid(),
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
        alias: 'v1.organizations.update',
        requestFormat: 'json',
        parameters: [
            {
                name: 'body',
                type: 'Body',
                schema: v1_organizations_update_Body,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string().uuid(),
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
                schema: z.string().uuid(),
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
                schema: z.string().uuid(),
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
                schema: z.object({ name: z.string() }).passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string().uuid(),
            },
            {
                name: 'client',
                type: 'Path',
                schema: z.string().uuid(),
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
                schema: z.string().uuid(),
            },
            {
                name: 'client',
                type: 'Path',
                schema: z.string().uuid(),
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
                schema: z.string().uuid(),
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
                        'time-entries': z
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
        path: '/v1/organizations/:organization/invitations',
        alias: 'getInvitations',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string().uuid(),
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
                schema: z.string().uuid(),
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
        method: 'get',
        path: '/v1/organizations/:organization/members',
        alias: 'getMembers',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string().uuid(),
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
        path: '/v1/organizations/:organization/members/:membership',
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
                schema: z.string().uuid(),
            },
            {
                name: 'membership',
                type: 'Path',
                schema: z.string().uuid(),
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
        path: '/v1/organizations/:organization/members/:membership',
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
                schema: z.string().uuid(),
            },
            {
                name: 'membership',
                type: 'Path',
                schema: z.string().uuid(),
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
        path: '/v1/organizations/:organization/members/:membership/invite-placeholder',
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
                schema: z.string().uuid(),
            },
            {
                name: 'membership',
                type: 'Path',
                schema: z.string().uuid(),
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
                schema: z.string().uuid(),
            },
            {
                name: 'projectMember',
                type: 'Path',
                schema: z.string().uuid(),
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
                schema: z.string().uuid(),
            },
            {
                name: 'projectMember',
                type: 'Path',
                schema: z.string().uuid(),
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
                schema: z.string().uuid(),
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
                schema: z.string().uuid(),
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
                schema: z.string().uuid(),
            },
            {
                name: 'project',
                type: 'Path',
                schema: z.string().uuid(),
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
                schema: createProject_Body,
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string().uuid(),
            },
            {
                name: 'project',
                type: 'Path',
                schema: z.string().uuid(),
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
                schema: z.string().uuid(),
            },
            {
                name: 'project',
                type: 'Path',
                schema: z.string().uuid(),
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
        path: '/v1/organizations/:organization/projects/:project/project-members',
        alias: 'getProjectMembers',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string().uuid(),
            },
            {
                name: 'project',
                type: 'Path',
                schema: z.string().uuid(),
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
                schema: z.string().uuid(),
            },
            {
                name: 'project',
                type: 'Path',
                schema: z.string().uuid(),
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
                schema: z.string().uuid(),
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
                schema: z.string().uuid(),
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
                schema: z.string().uuid(),
            },
            {
                name: 'tag',
                type: 'Path',
                schema: z.string().uuid(),
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
                schema: z.string().uuid(),
            },
            {
                name: 'tag',
                type: 'Path',
                schema: z.string().uuid(),
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
        path: '/v1/organizations/:organization/tasks',
        alias: 'getTasks',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string().uuid(),
            },
            {
                name: 'project_id',
                type: 'Query',
                schema: z.string().uuid().optional(),
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
                schema: z.string().uuid(),
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
                schema: z.object({ name: z.string() }).passthrough(),
            },
            {
                name: 'organization',
                type: 'Path',
                schema: z.string().uuid(),
            },
            {
                name: 'task',
                type: 'Path',
                schema: z.string().uuid(),
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
                schema: z.string().uuid(),
            },
            {
                name: 'task',
                type: 'Path',
                schema: z.string().uuid(),
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
        path: '/v1/organizations/:organization/time-entries',
        alias: 'getTimeEntries',
        requestFormat: 'json',
        parameters: [
            {
                name: 'organization',
                type: 'Path',
                schema: z.string().uuid(),
            },
            {
                name: 'user_id',
                type: 'Query',
                schema: z.string().uuid().optional(),
            },
            {
                name: 'before',
                type: 'Query',
                schema: before,
            },
            {
                name: 'after',
                type: 'Query',
                schema: before,
            },
            {
                name: 'active',
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
                schema: z.string().uuid(),
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
                schema: z.string().uuid(),
            },
            {
                name: 'timeEntry',
                type: 'Path',
                schema: z.string().uuid(),
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
                schema: z.string().uuid(),
            },
            {
                name: 'timeEntry',
                type: 'Path',
                schema: z.string().uuid(),
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
