import { api } from '@/packages/api/src';
import type { Report } from '@/packages/api/src';
import { fetchAllPages } from '@/utils/fetchAllPages';

export async function fetchAllReports(organizationId: string): Promise<Report[]> {
    return fetchAllPages((page) =>
        api.getReports({
            params: { organization: organizationId },
            queries: { page },
        })
    );
}
