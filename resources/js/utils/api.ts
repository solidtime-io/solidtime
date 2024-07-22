import type {
    ApiOf,
    ZodiosResponseByAlias,
    ZodiosBodyByAlias,
    ZodiosQueryParamsByAlias,
} from '@zodios/core';
import { api } from '../../../openapi.json.client';
import { router } from '@inertiajs/vue3';

export type SolidTimeApi = ApiOf<typeof api>;

export type InvitationsIndexResponse = ZodiosResponseByAlias<
    SolidTimeApi,
    'getInvitations'
>;

export type CreateInvitationBody = ZodiosBodyByAlias<SolidTimeApi, 'invite'>;

export type Invitation = InvitationsIndexResponse['data'][0];

export type TimeEntryResponse = ZodiosResponseByAlias<
    SolidTimeApi,
    'getTimeEntries'
>;
export type TimeEntry = TimeEntryResponse['data'][0];

export type CreateTimeEntryBody = ZodiosBodyByAlias<
    SolidTimeApi,
    'createTimeEntry'
>;

export type ProjectResponse = ZodiosResponseByAlias<
    SolidTimeApi,
    'getProjects'
>;
export type Project = ProjectResponse['data'][0];

export type CreateProjectBody = ZodiosBodyByAlias<
    SolidTimeApi,
    'createProject'
>;

export type UpdateProjectBody = ZodiosBodyByAlias<
    SolidTimeApi,
    'updateProject'
>;

export type ProjectMemberResponse = ZodiosResponseByAlias<
    SolidTimeApi,
    'getProjectMembers'
>;

export type CreateProjectMemberBody = ZodiosBodyByAlias<
    SolidTimeApi,
    'createProjectMember'
>;

export type UpdateProjectMemberBody = ZodiosBodyByAlias<
    SolidTimeApi,
    'updateProjectMember'
>;

export type ProjectMember = ProjectMemberResponse['data'][0];

export type CreateTaskBody = ZodiosBodyByAlias<SolidTimeApi, 'createTask'>;

export type CreateClientBody = ZodiosBodyByAlias<SolidTimeApi, 'createClient'>;
export type UpdateClientBody = ZodiosBodyByAlias<SolidTimeApi, 'updateClient'>;

export type TagIndexResponse = ZodiosResponseByAlias<SolidTimeApi, 'getTags'>;
export type Tag = TagIndexResponse['data'][0];

export type TaskIndexResponse = ZodiosResponseByAlias<SolidTimeApi, 'getTasks'>;
export type Task = TaskIndexResponse['data'][0];

export type UpdateTaskBody = ZodiosBodyByAlias<SolidTimeApi, 'updateTask'>;

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

export type UpdateMemberBody = ZodiosBodyByAlias<SolidTimeApi, 'updateMember'>;

export type InviteMemberBody = ZodiosBodyByAlias<SolidTimeApi, 'invite'>;
export type MemberRole = InviteMemberBody['role'];

export type CreateTagBody = ZodiosBodyByAlias<SolidTimeApi, 'createTag'>;

export type ImportType = ZodiosResponseByAlias<
    SolidTimeApi,
    'getImporters'
>['data'][0];
export type ImportReport = ZodiosResponseByAlias<SolidTimeApi, 'importData'>;

export type ReportingResponse = ZodiosResponseByAlias<
    SolidTimeApi,
    'getAggregatedTimeEntries'
>;

export type AggregatedTimeEntries = ReportingResponse['data'];
export type GroupedDataEntries = ReportingResponse['data']['grouped_data'];

export type AggregatedTimeEntriesQueryParams = ZodiosQueryParamsByAlias<
    SolidTimeApi,
    'getAggregatedTimeEntries'
>;

export type OrganizationResponse = ZodiosResponseByAlias<
    SolidTimeApi,
    'getOrganization'
>;

export type Organization = ZodiosResponseByAlias<
    SolidTimeApi,
    'getOrganization'
>['data'];

export type UpdateOrganizationBody = ZodiosBodyByAlias<
    SolidTimeApi,
    'updateOrganization'
>;

export type MyMemberships = ZodiosResponseByAlias<
    SolidTimeApi,
    'getMyMemberships'
>['data'];

export type MyMembership = MyMemberships[0];

export async function fetchToken() {
    return new Promise((resolve) => {
        router.reload({
            onFinish: () => {
                resolve(null);
            },
        });
    });
}
export function isTokenValid() {
    return window.document.cookie.includes('solidtime_session');
}
