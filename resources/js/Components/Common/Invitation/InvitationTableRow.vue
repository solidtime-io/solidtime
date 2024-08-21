<script setup lang="ts">
import type { Invitation } from '@/packages/api/src';
import TableRow from '@/Components/TableRow.vue';
import { capitalizeFirstLetter } from '../../../utils/format';
import InvitationMoreOptionsDropdown from '@/Components/Common/Invitation/InvitationMoreOptionsDropdown.vue';
import { api } from '@/packages/api/src';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';
import { useInvitationsStore } from '@/utils/useInvitations';
const { handleApiRequestNotifications } = useNotificationsStore();

const props = defineProps<{
    invitation: Invitation;
}>();

async function deleteInvitation() {
    const organizationId = getCurrentOrganizationId();
    if (organizationId) {
        await handleApiRequestNotifications(
            () =>
                api.removeInvitation(undefined, {
                    params: {
                        invitation: props.invitation.id,
                        organization: organizationId,
                    },
                }),
            'Invitation removed successfully',
            'Error removing invitation',
            () => {
                useInvitationsStore().fetchInvitations();
            }
        );
    }
}

async function resendInvitation() {
    const organizationId = getCurrentOrganizationId();
    if (organizationId) {
        await handleApiRequestNotifications(
            () =>
                api.resendInvitationEmail(
                    {},
                    {
                        params: {
                            invitation: props.invitation.id,
                            organization: organizationId,
                        },
                    }
                ),
            'Invitation mail sent successfully',
            'Error sending invitation mail'
        );
    }
}
</script>

<template>
    <TableRow>
        <div
            class="whitespace-nowrap px-3 py-4 text-sm text-muted pl-4 sm:pl-6 lg:pl-8 3xl:pl-12">
            {{ invitation.email }}
        </div>
        <div class="whitespace-nowrap px-3 py-4 text-sm text-muted">
            {{ capitalizeFirstLetter(invitation.role) }}
        </div>
        <div
            class="relative whitespace-nowrap flex items-center pl-3 text-right text-sm font-medium pr-4 sm:pr-6 lg:pr-8 3xl:pr-12">
            <InvitationMoreOptionsDropdown
                @delete="deleteInvitation"
                @resend="resendInvitation" />
        </div>
    </TableRow>
</template>

<style scoped></style>
