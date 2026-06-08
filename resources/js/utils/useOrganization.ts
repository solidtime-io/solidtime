import { router } from '@inertiajs/vue3';
import { initializeStores } from '@/utils/init';
import { defineStore } from 'pinia';
import { computed, ref } from 'vue';
import type {
    Organization,
    OrganizationResponse,
    UpdateOrganizationBody,
} from '@/packages/api/src';
import { useNotificationsStore } from '@/utils/notification';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { api } from '@/packages/api/src';

export async function switchOrganization(organizationId: string) {
    const { handleApiRequestNotifications } = useNotificationsStore();
    try {
        await handleApiRequestNotifications(
            () => api.updateMyCurrentOrganization({ organization_id: organizationId }),
            undefined,
            'Failed to switch organization'
        );
    } catch {
        // The error notification is surfaced by the request handler.
        return;
    }

    // The current organization changed server-side. Clear Inertia's prefetch
    // cache and reload into the dashboard so the new organization context
    // (auth.user.current_team) is picked up everywhere.
    router.flushAll();
    router.visit(route('dashboard'), {
        preserveState: false,
        onSuccess: () => {
            initializeStores();
        },
    });
}

export const useOrganizationStore = defineStore('organization', () => {
    const organizationResponse = ref<OrganizationResponse | null>(null);
    const { handleApiRequestNotifications } = useNotificationsStore();

    async function fetchOrganization() {
        const organization = getCurrentOrganizationId();
        if (organization) {
            organizationResponse.value = await handleApiRequestNotifications(
                () =>
                    api.getOrganization({
                        params: {
                            organization: organization,
                        },
                    }),
                undefined,
                'Failed to fetch organization'
            );
        }
    }

    async function updateOrganization(organizationBody: UpdateOrganizationBody) {
        const organization = getCurrentOrganizationId();
        if (organization) {
            await handleApiRequestNotifications(
                () =>
                    api.updateOrganization(organizationBody, {
                        params: {
                            organization: organization,
                        },
                    }),
                'Organization updated successfully',
                'Failed to update organization'
            );
            await fetchOrganization();
        }
    }

    async function createOrganization(name: string): Promise<Organization | null> {
        const response = await api.createOrganization({ name });
        return response?.data ?? null;
    }

    async function deleteOrganization(organizationId: string) {
        await handleApiRequestNotifications(
            () =>
                api.deleteOrganization(undefined, {
                    params: {
                        organization: organizationId,
                    },
                }),
            'Organization deleted successfully',
            'Failed to delete organization'
        );
    }

    const organization = computed<Organization | null>(() => {
        return organizationResponse.value?.data || null;
    });

    return {
        organization,
        fetchOrganization,
        updateOrganization,
        createOrganization,
        deleteOrganization,
    };
});
