import { defineStore } from 'pinia';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { api } from '@/packages/api/src';
import type { CreateTaskBody, UpdateTaskBody } from '@/packages/api/src';
import { useNotificationsStore } from '@/utils/notification';
import { useQueryClient } from '@tanstack/vue-query';

export const useTasksStore = defineStore('tasks', () => {
    const { handleApiRequestNotifications } = useNotificationsStore();
    const queryClient = useQueryClient();

    async function updateTask(taskId: string, taskBody: UpdateTaskBody) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            await handleApiRequestNotifications(
                () =>
                    api.updateTask(taskBody, {
                        params: {
                            task: taskId,
                            organization: organizationId,
                        },
                    }),
                'Task updated successfully',
                'Failed to update task'
            );
            queryClient.invalidateQueries({ queryKey: ['tasks'] });
        }
    }

    async function createTask(task: CreateTaskBody) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            await handleApiRequestNotifications(
                () =>
                    api.createTask(task, {
                        params: {
                            organization: organizationId,
                        },
                    }),
                'Task created successfully',
                'Failed to create task'
            );
            queryClient.invalidateQueries({ queryKey: ['tasks'] });
        }
    }

    async function deleteTask(taskId: string) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            await handleApiRequestNotifications(
                () =>
                    api.deleteTask(undefined, {
                        params: {
                            organization: organizationId,
                            task: taskId,
                        },
                    }),
                'Task deleted successfully',
                'Failed to delete task'
            );
            queryClient.invalidateQueries({ queryKey: ['tasks'] });
        }
    }

    return {
        updateTask,
        createTask,
        deleteTask,
    };
});
