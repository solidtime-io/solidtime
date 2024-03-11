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
const OrganizationResource = z
    .object({ id: z.string(), name: z.string(), is_personal: z.string() })
    .passthrough();
const ProjectResource = z
    .object({
        id: z.string(),
        name: z.string(),
        color: z.string(),
        client_id: z.union([z.string(), z.null()]),
    })
    .passthrough();
const ProjectCollection = z.array(ProjectResource);
const createProject_Body = z
    .object({
        name: z.string(),
        color: z.string(),
        client_id: z.union([z.string(), z.null()]).optional(),
    })
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
    })
    .passthrough();
const TimeEntryCollection = z.array(TimeEntryResource);
const createTimeEntry_Body = z
    .object({
        user_id: z.string().uuid(),
        task_id: z.union([z.string(), z.null()]).optional(),
        start: z.string(),
        end: z.union([z.string(), z.null()]).optional(),
        description: z.union([z.string(), z.null()]).optional(),
        tags: z.union([z.array(z.string()), z.null()]).optional(),
    })
    .passthrough();
const updateTimeEntry_Body = z
    .object({
        task_id: z.union([z.string(), z.null()]).optional(),
        start: z.string(),
        end: z.union([z.string(), z.null()]).optional(),
        description: z.union([z.string(), z.null()]).optional(),
        tags: z.union([z.array(z.string()), z.null()]).optional(),
    })
    .passthrough();

export const schemas = {
    ClientResource,
    ClientCollection,
    OrganizationResource,
    ProjectResource,
    ProjectCollection,
    createProject_Body,
    TagResource,
    TagCollection,
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
                schema: z.object({ name: z.string() }).passthrough(),
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
        alias: 'v1.clients.index',
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
        alias: 'v1.clients.store',
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
        alias: 'v1.clients.update',
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
        alias: 'v1.clients.destroy',
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
        response: z.object({ data: ProjectCollection }).passthrough(),
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
                schema: z.string().optional(),
            },
            {
                name: 'limit',
                type: 'Query',
                schema: z.number().int().gte(1).lte(500).optional(),
            },
            {
                name: 'only_full_dates',
                type: 'Query',
                schema: z.boolean().optional(),
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
]);

export const api = new Zodios('http://solidtime.test/api', endpoints);

export function createApiClient(baseUrl: string, options?: ZodiosOptions) {
    return new Zodios(baseUrl, endpoints, options);
}
