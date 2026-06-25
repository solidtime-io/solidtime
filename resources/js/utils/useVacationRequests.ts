import { useQuery, useMutation, useQueryClient } from '@tanstack/vue-query';
import axios from 'axios';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { computed, type Ref } from 'vue';
import { useNotificationsStore } from '@/utils/notification';

export type VacationRequestType =
    | 'regular_vacation'
    | 'sick_day'
    | 'work_outside'
    | 'special';

export type VacationRequestStatus =
    | 'pending'
    | 'approved'
    | 'rejected'
    | 'withdrawn';

export interface VacationRequest {
    id: string;
    organization_id: string;
    member_id: string;
    member_name: string | null;
    type: VacationRequestType;
    start_date: string;
    end_date: string;
    half_day: boolean;
    days_count: number;
    status: VacationRequestStatus;
    private_note: string | null;
    public_note: string | null;
    reviewed_by: string | null;
    reviewer_name: string | null;
    reviewed_at: string | null;
    created_at: string | null;
    updated_at: string | null;
}

export interface CreateVacationRequestBody {
    type: VacationRequestType;
    start_date: string;
    end_date: string;
    half_day?: boolean;
    private_note?: string | null;
    public_note?: string | null;
    member_id?: string | null;
}

const BASE = '/api/v1/organizations';

function orgUrl(organizationId: string, suffix = '') {
    return `${BASE}/${organizationId}/vacation-requests${suffix}`;
}

async function fetchVacationRequests(
    organizationId: string,
    memberId?: string | null,
    status?: string | null
): Promise<VacationRequest[]> {
    const params: Record<string, string> = {};
    if (memberId) params.member_id = memberId;
    if (status) params.status = status;

    const response = await axios.get(orgUrl(organizationId), { params });
    return response.data.data ?? [];
}

export function useVacationRequestsQuery(
    memberId?: Ref<string | null | undefined>,
    status?: Ref<string | null | undefined>
) {
    const organizationId = getCurrentOrganizationId();

    return useQuery({
        queryKey: computed(() => [
            'vacation-requests',
            organizationId,
            memberId?.value,
            status?.value,
        ]),
        queryFn: async () => {
            if (!organizationId) throw new Error('No organization');
            return fetchVacationRequests(organizationId, memberId?.value, status?.value);
        },
        enabled: !!organizationId,
        staleTime: 1000 * 30,
    });
}

export function useVacationRequestMutations() {
    const queryClient = useQueryClient();
    const { addNotification } = useNotificationsStore();
    const organizationId = getCurrentOrganizationId();

    function invalidate() {
        queryClient.invalidateQueries({ queryKey: ['vacation-requests'] });
    }

    const create = useMutation({
        mutationFn: async (body: CreateVacationRequestBody) => {
            if (!organizationId) throw new Error('No organization');
            const response = await axios.post(orgUrl(organizationId), body);
            return response.data.data as VacationRequest;
        },
        onSuccess: () => {
            invalidate();
            addNotification('success', 'Vacation request created');
        },
        onError: () => {
            addNotification('error', 'Failed to create vacation request');
        },
    });

    const updateStatus = useMutation({
        mutationFn: async ({
            id,
            status,
        }: {
            id: string;
            status: VacationRequestStatus;
        }) => {
            if (!organizationId) throw new Error('No organization');
            const response = await axios.put(orgUrl(organizationId, `/${id}`), { status });
            return response.data.data as VacationRequest;
        },
        onSuccess: () => {
            invalidate();
        },
        onError: () => {
            addNotification('error', 'Failed to update vacation request');
        },
    });

    const remove = useMutation({
        mutationFn: async (id: string) => {
            if (!organizationId) throw new Error('No organization');
            await axios.delete(orgUrl(organizationId, `/${id}`));
        },
        onSuccess: () => {
            invalidate();
            addNotification('success', 'Vacation request deleted');
        },
        onError: () => {
            addNotification('error', 'Failed to delete vacation request');
        },
    });

    return { create, updateStatus, remove };
}
