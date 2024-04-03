import { defineStore } from 'pinia';
import { api } from '../../../openapi.json.client';
import { computed, ref } from 'vue';
import type {
    CreateClientBody,
    ClientIndexResponse,
    Client,
} from '@/utils/api';
import { getCurrentOrganizationId } from '@/utils/useUser';

export const useClientsStore = defineStore('clients', () => {
    const clientResponse = ref<ClientIndexResponse | null>(null);

    async function fetchClients() {
        const organization = getCurrentOrganizationId();
        if (organization) {
            clientResponse.value = await api.getClients({
                params: {
                    organization: organization,
                },
            });
        }
    }

    async function createClient(
        clientBody: CreateClientBody
    ): Promise<Client | undefined> {
        const organization = getCurrentOrganizationId();
        if (organization) {
            const response = await api.createClient(clientBody, {
                params: {
                    organization: organization,
                },
            });
            await fetchClients();
            return response.data;
        }
    }

    async function deleteClient(clientId: string) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            await api.deleteClient(
                {},
                {
                    params: {
                        organization: organization,
                        client: clientId,
                    },
                }
            );
            await fetchClients();
        }
    }

    const clients = computed<Client[]>(() => {
        return clientResponse.value?.data || [];
    });

    return { clients, fetchClients, createClient, deleteClient };
});
