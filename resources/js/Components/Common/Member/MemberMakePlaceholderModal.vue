<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { ref } from 'vue';
import { api, type Member } from '@/packages/api/src';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { useMutation, useQueryClient } from '@tanstack/vue-query';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { useNotificationsStore } from '@/utils/notification';

const { handleApiRequestNotifications } = useNotificationsStore();
const queryClient = useQueryClient();

const show = defineModel('show', { default: false });
const saving = ref(false);

const props = defineProps<{
    member: Member;
}>();

const turnToPlaceholderMutation = useMutation({
    mutationFn: async () => {
        const organizationId = getCurrentOrganizationId();
        if (organizationId === null) {
            throw new Error('No current organization id - create report');
        }
        return await api.makePlaceholder(undefined, {
            params: {
                organization: organizationId,
                member: props.member.id,
            },
        });
    },
});

async function submit() {
    saving.value = true;
    await handleApiRequestNotifications(
        () => turnToPlaceholderMutation.mutateAsync(),
        'Deactivating the member was successful!',
        'There was an error deactivating the user.',
        () => {
            show.value = false;
            queryClient.invalidateQueries({ queryKey: ['members'] });
        }
    );
}
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Deactivate User </span>
            </div>
        </template>

        <template #content>
            <p>
                Deactivating the user <strong>{{ member.name }} </strong> will remove the user's
                access to the organization. You will not be billed for inactive users and all time
                entries will be preserved.
            </p>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false"> Cancel</SecondaryButton>

            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="submit()">
                Deactivate
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
