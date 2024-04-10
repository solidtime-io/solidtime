import type {
    ApiOf,
    ZodiosResponseByAlias,
    ZodiosBodyByAlias,
} from '@zodios/core';
import { api } from '../../../openapi.json.client';

export type SolidTimeApi = ApiOf<typeof api>;

export type TimeEntryResponse = ZodiosResponseByAlias<
    SolidTimeApi,
    'getTimeEntries'
>;
export type TimeEntry = TimeEntryResponse['data'][0];

export type ProjectResponse = ZodiosResponseByAlias<
    SolidTimeApi,
    'getProjects'
>;
export type Project = ProjectResponse['data'][0];

export type CreateProjectBody = ZodiosBodyByAlias<
    SolidTimeApi,
    'createProject'
>;

export type ProjectMemberResponse = ZodiosResponseByAlias<
    SolidTimeApi,
    'getProjectMembers'
>;

export type CreateProjectMemberBody = ZodiosBodyByAlias<
    SolidTimeApi,
    'createProjectMember'
>;

export type ProjectMember = ProjectMemberResponse['data'][0];

export type CreateTaskBody = ZodiosBodyByAlias<SolidTimeApi, 'createTask'>;

export type CreateClientBody = ZodiosBodyByAlias<SolidTimeApi, 'createClient'>;

export type TagIndexResponse = ZodiosResponseByAlias<SolidTimeApi, 'getTags'>;
export type Tag = TagIndexResponse['data'][0];

export type TaskIndexResponse = ZodiosResponseByAlias<SolidTimeApi, 'getTasks'>;
export type Task = TaskIndexResponse['data'][0];

export type ClientIndexResponse = ZodiosResponseByAlias<
    SolidTimeApi,
    'getClients'
>;
export type Client = ClientIndexResponse['data'][0];

export type MemberIndexResponse = ZodiosResponseByAlias<
    SolidTimeApi,
    'getMembers'
>;
export type Member = MemberIndexResponse['data'][0];

export type CreateTagBody = ZodiosBodyByAlias<SolidTimeApi, 'createTag'>;
