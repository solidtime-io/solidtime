import { useMutation, useQueryClient } from '@tanstack/vue-query';
import {
    api,
    type CreateTimeEntryBody,
    type TimeEntry,
    type UpdateMultipleTimeEntriesChangeset,
} from '@/packages/api/src';
import { getCurrentMembershipId, getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';

export function useTimeEntriesMutations() {
    const queryClient = useQueryClient();
    const { handleApiRequestNotifications } = useNotificationsStore();

    const { mutateAsync: createTimeEntry } = useMutation({
        mutationFn: async (timeEntry: Omit<CreateTimeEntryBody, 'member_id'>) => {
            const organizationId = getCurrentOrganizationId();
            const memberId = getCurrentMembershipId();
            if (organizationId && memberId !== undefined) {
                const newTimeEntry = {
                    ...timeEntry,
                    member_id: memberId,
                } as CreateTimeEntryBody;

                return await handleApiRequestNotifications(
                    () =>
                        api.createTimeEntry(newTimeEntry, {
                            params: {
                                organization: organizationId,
                            },
                        }),
                    'Time entry created successfully',
                    'Failed to create time entry'
                );
            }
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['timeEntries'] });
        },
    });

    const { mutateAsync: updateTimeEntry } = useMutation({
        mutationFn: async (timeEntry: TimeEntry) => {
            const organizationId = getCurrentOrganizationId();
            if (organizationId) {
                return await handleApiRequestNotifications(
                    () =>
                        api.updateTimeEntry(timeEntry, {
                            params: {
                                organization: organizationId,
                                timeEntry: timeEntry.id,
                            },
                        }),
                    'Time entry updated successfully',
                    'Failed to update time entry'
                );
            }
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['timeEntries'] });
        },
    });

    const { mutateAsync: updateTimeEntries } = useMutation({
        mutationFn: async ({
            ids,
            changes,
        }: {
            ids: string[];
            changes: UpdateMultipleTimeEntriesChangeset;
        }) => {
            const organizationId = getCurrentOrganizationId();
            if (organizationId) {
                return await handleApiRequestNotifications(
                    () =>
                        api.updateMultipleTimeEntries(
                            {
                                ids: ids,
                                changes: changes,
                            },
                            {
                                params: {
                                    organization: organizationId,
                                },
                            }
                        ),
                    'Time entries updated successfully',
                    'Failed to update time entries'
                );
            }
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['timeEntries'] });
        },
    });

    const { mutateAsync: deleteTimeEntry } = useMutation({
        mutationFn: async (timeEntryId: string) => {
            const organizationId = getCurrentOrganizationId();
            if (organizationId) {
                return await handleApiRequestNotifications(
                    () =>
                        api.deleteTimeEntry(undefined, {
                            params: {
                                organization: organizationId,
                                timeEntry: timeEntryId,
                            },
                        }),
                    'Time entry deleted successfully',
                    'Failed to delete time entry'
                );
            }
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['timeEntries'] });
        },
    });

    const { mutateAsync: deleteTimeEntries } = useMutation({
        mutationFn: async (timeEntries: TimeEntry[]) => {
            const organizationId = getCurrentOrganizationId();
            const timeEntryIds = timeEntries.map((entry) => entry.id);
            if (organizationId) {
                return await handleApiRequestNotifications(
                    () =>
                        api.deleteTimeEntries(undefined, {
                            queries: {
                                ids: timeEntryIds,
                            },
                            params: {
                                organization: organizationId,
                            },
                        }),
                    'Time entries deleted successfully',
                    'Failed to delete time entries'
                );
            }
        },
        onSuccess: () => {
            queryClient.invalidateQueries({ queryKey: ['timeEntries'] });
        },
    });

    return {
        createTimeEntry,
        updateTimeEntry,
        updateTimeEntries,
        deleteTimeEntry,
        deleteTimeEntries,
    };
}
