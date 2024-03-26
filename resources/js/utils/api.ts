import type { ApiOf, ZodiosResponseByAlias } from '@zodios/core';
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

export type TagIndexResponse = ZodiosResponseByAlias<SolidTimeApi, 'getTags'>;
export type Tag = TagIndexResponse['data'][0];
