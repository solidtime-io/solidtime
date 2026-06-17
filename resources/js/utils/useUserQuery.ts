import { useMutation, useQuery, useQueryClient } from '@tanstack/vue-query';
import { computed } from 'vue';
import axios from 'axios';
import { api, type DeleteUserBody, type UpdateUserBody, type User } from '@/packages/api/src';
import { useNotificationsStore } from '@/utils/notification';

const ME_QUERY_KEY = ['me'] as const;

export function useUserQuery() {
    const query = useQuery({
        queryKey: ME_QUERY_KEY,
        queryFn: async () => {
            const response = await api.getMe();
            return response.data;
        },
        staleTime: 1000 * 30,
    });

    const user = computed<User | undefined>(() => query.data.value);

    return { ...query, user };
}

export function useUpdateUserMutation() {
    const queryClient = useQueryClient();
    const { addNotification } = useNotificationsStore();

    return useMutation({
        mutationFn: async ({
            userId,
            body,
        }: {
            userId: string;
            body: UpdateUserBody;
        }): Promise<User> => {
            try {
                const response = await api.updateUser(body, { params: { user: userId } });
                return response.data;
            } catch (error) {
                // 422 field errors are rendered inline by the form; suppress the toast for those.
                // Re-throw the AxiosError so consumers can read response.data.errors.
                if (!axios.isAxiosError(error) || error.response?.status !== 422) {
                    addNotification(
                        'error',
                        'Failed to update profile',
                        axios.isAxiosError(error)
                            ? (error.response?.data?.message ?? 'Please try again later.')
                            : 'Please try again later.'
                    );
                }
                throw error;
            }
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ME_QUERY_KEY });
        },
    });
}

export function useDeleteUserMutation() {
    const { addNotification } = useNotificationsStore();

    return useMutation({
        mutationFn: async ({ userId, body }: { userId: string; body: DeleteUserBody }) => {
            try {
                await api.deleteUser(body, { params: { user: userId } });
            } catch (error) {
                if (!axios.isAxiosError(error) || error.response?.status !== 422) {
                    addNotification(
                        'error',
                        'Failed to delete account',
                        axios.isAxiosError(error)
                            ? (error.response?.data?.message ?? 'Please try again later.')
                            : 'Please try again later.'
                    );
                }
                throw error;
            }
        },
    });
}

export function useResendUserEmailVerificationMutation() {
    const { handleApiRequestNotifications } = useNotificationsStore();

    return useMutation({
        mutationFn: async (userId: string) => {
            return handleApiRequestNotifications(
                () => api.resendUserEmailVerification(undefined, { params: { user: userId } }),
                'Verification email sent',
                'Failed to resend verification email'
            );
        },
    });
}

export function useResetUserPendingEmailMutation() {
    const queryClient = useQueryClient();
    const { handleApiRequestNotifications } = useNotificationsStore();

    return useMutation({
        mutationFn: async (userId: string) => {
            return handleApiRequestNotifications(
                () => api.resetUserPendingEmail(undefined, { params: { user: userId } }),
                'Email change canceled',
                'Failed to cancel email change'
            );
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ME_QUERY_KEY });
        },
    });
}
