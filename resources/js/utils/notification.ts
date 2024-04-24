import { defineStore } from 'pinia';
import { ref } from 'vue';
import axios from 'axios';

export type NotificationType = 'success' | 'error';

export const useNotificationsStore = defineStore('notifications', () => {
    const notifications = ref<
        {
            title: string;
            message?: string;
            uuid: string;
            type: NotificationType;
        }[]
    >([]);

    function addNotification(
        type: NotificationType,
        title: string,
        message?: string
    ) {
        const uuid = Math.random().toString(36).substring(7);
        notifications.value.push({ title, message, type, uuid });

        setTimeout(() => {
            removeNotification(uuid);
        }, 5000);
    }

    function removeNotification(uuid: string) {
        const index = notifications.value.findIndex(
            (notification) => notification.uuid === uuid
        );
        if (index !== -1) {
            notifications.value.splice(index, 1);
        }
    }

    async function handleApiRequestNotifications<T>(
        apiRequest: Promise<T>,
        successMessage?: string,
        errorMessage?: string
    ) {
        try {
            const response = await apiRequest;
            if (successMessage) {
                addNotification('success', successMessage);
            }
            return response;
        } catch (error) {
            if (axios.isAxiosError(error)) {
                if (
                    error?.response?.status === 403 ||
                    error?.response?.status === 400
                ) {
                    addNotification(
                        'error',
                        errorMessage ?? 'Request Error',
                        error.response?.data?.errorMessage ??
                            error?.response?.data?.message ??
                            'An request error occurred. Please try again later.'
                    );
                } else if (error?.response?.status === 422) {
                    const message = error.response.data.message;
                    addNotification('error', message);
                } else {
                    addNotification(
                        'error',
                        'The action failed. Please try again later.'
                    );
                }
            }
        }
        throw new Error('Failed to handle API request');
    }

    return { addNotification, notifications, handleApiRequestNotifications };
});
