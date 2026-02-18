import type { QueryClient } from '@tanstack/vue-query';
import { api } from '@/packages/api/src';
import { getCurrentOrganizationId, getCurrentMembershipId } from '@/utils/useUser';
import { canViewClients, canViewMembers } from '@/utils/permissions';
import {
    getInitialWeekRange,
    getExpandedCalendarDateRange,
    createCalendarQueryKey,
    fetchAllCalendarEntries,
} from '@/utils/useTimeEntriesCalendarQuery';

/**
 * Route patterns mapped to their prefetch functions.
 * Each function receives the QueryClient and prefetches relevant data.
 */
const routePrefetchers: Record<string, (queryClient: QueryClient) => void> = {
    '/': (queryClient) => {
        prefetchDashboard(queryClient);
    },

    '/dashboard': (queryClient) => {
        prefetchDashboard(queryClient);
    },

    '/time': (queryClient) => {
        prefetchProjects(queryClient);
        prefetchTasks(queryClient);
        prefetchTags(queryClient);
        prefetchClients(queryClient);
        prefetchTimeEntries(queryClient);
    },

    '/calendar': (queryClient) => {
        prefetchProjects(queryClient);
        prefetchTasks(queryClient);
        prefetchTags(queryClient);
        prefetchClients(queryClient);
        prefetchCalendarTimeEntries(queryClient);
    },

    '/projects': (queryClient) => {
        prefetchProjects(queryClient);
        prefetchClients(queryClient);
    },

    '/clients': (queryClient) => {
        prefetchClients(queryClient);
    },

    '/tags': (queryClient) => {
        prefetchTags(queryClient);
    },

    '/members': (queryClient) => {
        prefetchMembers(queryClient);
    },

    '/reporting': (queryClient) => {
        prefetchProjects(queryClient);
        prefetchTags(queryClient);
        prefetchClients(queryClient);
        prefetchMembers(queryClient);
    },

    '/reporting/detailed': (queryClient) => {
        prefetchProjects(queryClient);
        prefetchTasks(queryClient);
        prefetchTags(queryClient);
        prefetchClients(queryClient);
        prefetchMembers(queryClient);
    },

    '/reporting/shared': (queryClient) => {
        prefetchReports(queryClient);
    },
};

function prefetchDashboard(queryClient: QueryClient) {
    const organizationId = getCurrentOrganizationId();
    if (!organizationId) return;

    // Prefetch projects and tasks for RecentlyTrackedTasksCard
    prefetchProjects(queryClient);
    prefetchTasks(queryClient);

    // Prefetch all dashboard card data
    queryClient.prefetchQuery({
        queryKey: ['timeEntries', organizationId],
        queryFn: () =>
            api.getTimeEntries({
                params: { organization: organizationId },
                queries: { limit: 10, offset: 0, only_full_dates: 'true' },
            }),
        staleTime: 30000,
    });

    queryClient.prefetchQuery({
        queryKey: ['lastSevenDays', organizationId],
        queryFn: () => api.lastSevenDays({ params: { organization: organizationId } }),
        staleTime: 30000,
    });

    queryClient.prefetchQuery({
        queryKey: ['dailyTrackedHours', organizationId],
        queryFn: () => api.dailyTrackedHours({ params: { organization: organizationId } }),
        staleTime: 30000,
    });

    queryClient.prefetchQuery({
        queryKey: ['weeklyProjectOverview', organizationId],
        queryFn: () => api.weeklyProjectOverview({ params: { organization: organizationId } }),
        staleTime: 30000,
    });

    queryClient.prefetchQuery({
        queryKey: ['totalWeeklyTime', organizationId],
        queryFn: () => api.totalWeeklyTime({ params: { organization: organizationId } }),
        staleTime: 30000,
    });

    queryClient.prefetchQuery({
        queryKey: ['totalWeeklyBillableTime', organizationId],
        queryFn: () => api.totalWeeklyBillableTime({ params: { organization: organizationId } }),
        staleTime: 30000,
    });

    queryClient.prefetchQuery({
        queryKey: ['totalWeeklyBillableAmount', organizationId],
        queryFn: () => api.totalWeeklyBillableAmount({ params: { organization: organizationId } }),
        staleTime: 30000,
    });

    queryClient.prefetchQuery({
        queryKey: ['weeklyHistory', organizationId],
        queryFn: () => api.weeklyHistory({ params: { organization: organizationId } }),
        staleTime: 30000,
    });

    // Prefetch team activity only if user has permission
    if (canViewMembers()) {
        queryClient.prefetchQuery({
            queryKey: ['latestTeamActivity', organizationId],
            queryFn: () => api.latestTeamActivity({ params: { organization: organizationId } }),
            staleTime: 30000,
        });
    }
}

function prefetchProjects(queryClient: QueryClient) {
    const organizationId = getCurrentOrganizationId();
    if (!organizationId) return;

    queryClient.prefetchQuery({
        queryKey: ['projects', organizationId],
        queryFn: () =>
            api.getProjects({
                params: { organization: organizationId },
                queries: { archived: 'all' },
            }),
        staleTime: 30000, // Consider fresh for 30 seconds
    });
}

function prefetchTasks(queryClient: QueryClient) {
    const organizationId = getCurrentOrganizationId();
    if (!organizationId) return;

    queryClient.prefetchQuery({
        queryKey: ['tasks', organizationId],
        queryFn: () =>
            api.getTasks({
                params: { organization: organizationId },
                queries: { done: 'all' },
            }),
        staleTime: 30000,
    });
}

function prefetchTags(queryClient: QueryClient) {
    const organizationId = getCurrentOrganizationId();
    if (!organizationId) return;

    queryClient.prefetchQuery({
        queryKey: ['tags', organizationId],
        queryFn: () =>
            api.getTags({
                params: { organization: organizationId },
            }),
        staleTime: 30000,
    });
}

function prefetchClients(queryClient: QueryClient) {
    const organizationId = getCurrentOrganizationId();
    if (!organizationId || !canViewClients()) return;

    queryClient.prefetchQuery({
        queryKey: ['clients', organizationId],
        queryFn: () =>
            api.getClients({
                params: { organization: organizationId },
                queries: { archived: 'all' },
            }),
        staleTime: 30000,
    });
}

function prefetchMembers(queryClient: QueryClient) {
    const organizationId = getCurrentOrganizationId();
    if (!organizationId || !canViewMembers()) return;

    queryClient.prefetchQuery({
        queryKey: ['members', organizationId],
        queryFn: () =>
            api.getMembers({
                params: { organization: organizationId },
            }),
        staleTime: 30000,
    });
}

function prefetchReports(queryClient: QueryClient) {
    const organizationId = getCurrentOrganizationId();
    if (!organizationId) return;

    queryClient.prefetchQuery({
        queryKey: ['reports', organizationId],
        queryFn: () =>
            api.getReports({
                params: { organization: organizationId },
            }),
        staleTime: 30000,
    });
}

function prefetchTimeEntries(queryClient: QueryClient) {
    const organizationId = getCurrentOrganizationId();
    const memberId = getCurrentMembershipId();
    if (!organizationId) return;

    queryClient.prefetchInfiniteQuery({
        queryKey: ['timeEntries', 'infinite', { organizationId, memberId }],
        queryFn: async () => {
            const response = await api.getTimeEntries({
                params: { organization: organizationId },
                queries: {
                    only_full_dates: 'true',
                    member_id: memberId,
                },
            });
            return response;
        },
        initialPageParam: undefined,
        staleTime: 30000,
    });
}

function prefetchCalendarTimeEntries(queryClient: QueryClient) {
    const organizationId = getCurrentOrganizationId();
    const memberId = getCurrentMembershipId();
    if (!organizationId) return;

    const { start, end } = getInitialWeekRange();
    const { start: formattedStart, end: formattedEnd } = getExpandedCalendarDateRange(start, end);

    queryClient.prefetchQuery({
        queryKey: createCalendarQueryKey(formattedStart, formattedEnd, organizationId),
        queryFn: () =>
            fetchAllCalendarEntries(organizationId, memberId, formattedStart, formattedEnd),
        staleTime: 30000,
    });
}

function prefetchProjectMembers(queryClient: QueryClient, projectId: string) {
    const organizationId = getCurrentOrganizationId();
    if (!organizationId || !canViewMembers()) return;

    queryClient.prefetchQuery({
        queryKey: ['projectMembers', organizationId, projectId],
        queryFn: () =>
            api.getProjectMembers({
                params: { organization: organizationId, project: projectId },
            }),
        staleTime: 30000,
    });
}

/**
 * Matches a URL to find the appropriate prefetcher.
 * Handles both exact matches and pattern matching for dynamic routes.
 */
function findPrefetcher(url: string): ((queryClient: QueryClient) => void) | undefined {
    // Extract pathname from URL
    const pathname = url.startsWith('http') ? new URL(url).pathname : url.split('?')[0]!;

    // Try exact match first
    if (pathname && routePrefetchers[pathname]) {
        return routePrefetchers[pathname];
    }

    // Try pattern matching for dynamic routes like /projects/{id}
    const projectMatch = pathname?.match(/^\/projects\/([^/]+)$/);
    if (projectMatch) {
        const projectId = projectMatch[1]!;
        return (queryClient) => {
            prefetchProjects(queryClient);
            prefetchTasks(queryClient);
            prefetchProjectMembers(queryClient, projectId);
        };
    }

    return undefined;
}

/**
 * Sets up Inertia prefetch event listener to warm TanStack Query cache.
 * Call this once during app initialization.
 */
export function setupPrefetching(queryClient: QueryClient) {
    // Listen for the 'prefetching' event which fires when Inertia starts prefetching a page
    // The event detail contains the visit object with the URL being prefetched
    document.addEventListener('inertia:prefetching', ((event: CustomEvent) => {
        const visit = event.detail?.visit;
        if (!visit?.url) return;

        const url = visit.url.href || visit.url.toString();
        const prefetcher = findPrefetcher(url);

        if (prefetcher) {
            prefetcher(queryClient);
        }
    }) as EventListener);
}
