import { defineStore } from 'pinia';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { api } from '../../../openapi.json.client';
import { reactive, ref } from 'vue';
import type { Task } from '@/utils/api';

export const useTasksStore = defineStore('tasks', () => {
    const tasks = ref<Task[]>(reactive([]));

    async function fetchTasks() {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            const tasksResponse = await api.getTasks({
                params: {
                    organization: organizationId,
                },
            });
            tasks.value = tasksResponse.data;
        }
    }

    async function updateTask(task: Task) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            await api.updateTask(task, {
                params: {
                    organization: organizationId,
                    task: task.id,
                },
            });
        }
    }

    async function createTask(task: Task) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            await api.createTask(task, {
                params: {
                    organization: organizationId,
                },
            });
            await fetchTasks();
        }
    }

    async function deleteTask(taskId: string) {
        const organizationId = getCurrentOrganizationId();
        if (organizationId) {
            await api.deleteTask(
                {},
                {
                    params: {
                        organization: organizationId,
                        task: taskId,
                    },
                }
            );
            await fetchTasks();
        }
    }

    return {
        tasks,
        fetchTasks,
        updateTask,
        createTask,
        deleteTask,
    };
});
