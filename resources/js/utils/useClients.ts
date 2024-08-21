import { defineStore } from 'pinia';
import { api } from '@/packages/api/src';
import { computed, ref } from 'vue';
import type {
    CreateClientBody,
    ClientIndexResponse,
    Client,
    UpdateClientBody,
} from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';

export const useClientsStore = defineStore('clients', () => {
    const clientResponse = ref<ClientIndexResponse | null>(null);
    const { handleApiRequestNotifications } = useNotificationsStore();

    async function fetchClients() {
        const organization = getCurrentOrganizationId();
        if (organization) {
            clientResponse.value = await handleApiRequestNotifications(
                () =>
                    api.getClients({
                        params: {
                            organization: organization,
                        },
                    }),
                undefined,
                'Failed to fetch clients'
            );
        }
    }

    async function createClient(
        clientBody: CreateClientBody
    ): Promise<Client | undefined> {
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
            await fetchClients();
            return response?.data;
        }
    }

    async function updateClient(
        clientId: string,
        clientBody: UpdateClientBody
    ) {
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
            await fetchClients();
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
            await fetchClients();
        }
    }

    const clients = computed<Client[]>(() => {
        return clientResponse.value?.data || [];
    });

    return { clients, fetchClients, createClient, deleteClient, updateClient };
});
