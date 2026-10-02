import { defineStore } from 'pinia';
import { api } from '@/packages/api/src';
import type { CreateGoalBody, Goal, UpdateGoalBody } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';
import { useQueryClient } from '@tanstack/vue-query';
import { isApiValidationError } from '@/utils/apiValidation';

export const useGoalsStore = defineStore('goals', () => {
    const { addNotification, handleApiRequestNotifications } = useNotificationsStore();
    const queryClient = useQueryClient();

    /**
     * Like handleApiRequestNotifications, but a 422 response is rethrown as the AxiosError
     * without a toast, so that the goal form can show the errors next to its fields.
     */
    async function handleGoalFormRequest<T>(
        apiRequest: () => Promise<T>,
        successMessage: string,
        errorMessage: string
    ): Promise<T | undefined> {
        let validationError: unknown = null;
        const response = await handleApiRequestNotifications(
            async () => {
                try {
                    return await apiRequest();
                } catch (error) {
                    if (isApiValidationError(error)) {
                        validationError = error;
                        return undefined;
                    }
                    throw error;
                }
            },
            undefined,
            errorMessage
        );
        if (validationError !== null) {
            throw validationError;
        }
        addNotification('success', successMessage);
        return response;
    }

    async function createGoal(goalBody: CreateGoalBody): Promise<Goal | undefined> {
        const organization = getCurrentOrganizationId();
        if (organization) {
            const response = await handleGoalFormRequest(
                () =>
                    api.createGoal(goalBody, {
                        params: {
                            organization: organization,
                        },
                    }),
                'Goal created successfully',
                'Failed to create goal'
            );
            queryClient.invalidateQueries({ queryKey: ['goals'] });
            return response?.data;
        }
    }

    async function updateGoal(goalId: string, goalBody: UpdateGoalBody): Promise<Goal | undefined> {
        const organization = getCurrentOrganizationId();
        if (organization) {
            const response = await handleGoalFormRequest(
                () =>
                    api.updateGoal(goalBody, {
                        params: {
                            organization: organization,
                            goal: goalId,
                        },
                    }),
                'Goal updated successfully',
                'Failed to update goal'
            );
            queryClient.invalidateQueries({ queryKey: ['goals'] });
            return response?.data;
        }
    }

    /**
     * Archived goals are hidden from the goal list by default, archiving is not deleting.
     */
    async function setGoalArchived(goalId: string, isArchived: boolean): Promise<Goal | undefined> {
        const organization = getCurrentOrganizationId();
        if (organization) {
            const response = await handleApiRequestNotifications(
                () =>
                    api.updateGoal(
                        { is_archived: isArchived },
                        {
                            params: {
                                organization: organization,
                                goal: goalId,
                            },
                        }
                    ),
                isArchived ? 'Goal archived successfully' : 'Goal unarchived successfully',
                isArchived ? 'Failed to archive goal' : 'Failed to unarchive goal'
            );
            queryClient.invalidateQueries({ queryKey: ['goals'] });
            return response?.data;
        }
    }

    async function deleteGoal(goalId: string) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            await handleApiRequestNotifications(
                () =>
                    api.deleteGoal(undefined, {
                        params: {
                            organization: organization,
                            goal: goalId,
                        },
                    }),
                'Goal deleted successfully',
                'Failed to delete goal'
            );
            queryClient.invalidateQueries({ queryKey: ['goals'] });
        }
    }

    return { createGoal, updateGoal, setGoalArchived, deleteGoal };
});
