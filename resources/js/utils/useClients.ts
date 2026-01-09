import { defineStore } from 'pinia';
import { api } from '@/packages/api/src';
import type { CreateClientBody, Client, UpdateClientBody } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';
import { useQueryClient } from '@tanstack/vue-query';

export const useClientsStore = defineStore('clients', () => {
    const { handleApiRequestNotifications } = useNotificationsStore();
    const queryClient = useQueryClient();

    async function createClient(clientBody: CreateClientBody): Promise<Client | undefined> {
        const organization = getCurrentOrganizationId();
        if (organization) {
            const response = await handleApiRequestNotifications(
                () =>
                    api.createClient(clientBody, {
                        params: {
                            organization: organization,
                        },
                    }),
                'Client created successfully',
                'Failed to create client'
            );
            queryClient.invalidateQueries({ queryKey: ['clients'] });
            return response?.data;
        }
    }

    async function updateClient(clientId: string, clientBody: UpdateClientBody) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            await handleApiRequestNotifications(
                () =>
                    api.updateClient(clientBody, {
                        params: {
                            organization: organization,
                            client: clientId,
                        },
                    }),
                'Client updated successfully',
                'Failed to update client'
            );
            queryClient.invalidateQueries({ queryKey: ['clients'] });
        }
    }

    async function deleteClient(clientId: string) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            await handleApiRequestNotifications(
                () =>
                    api.deleteClient(undefined, {
                        params: {
                            organization: organization,
                            client: clientId,
                        },
                    }),
                'Client deleted successfully',
                'Failed to delete client'
            );
            queryClient.invalidateQueries({ queryKey: ['clients'] });
        }
    }

    return { createClient, deleteClient, updateClient };
});
