import type { ApiOf } from '@zodios/core';
import { api } from '../../../openapi.json.client';

export type SolidTimeApi = ApiOf<typeof api>;
