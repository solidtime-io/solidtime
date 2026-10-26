<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import IdentityConfirmationInput from '@/Components/Common/IdentityConfirmation/IdentityConfirmationInput.vue';
import DangerButton from '@/packages/ui/src/Buttons/DangerButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { useOrganizationStore } from '@/utils/useOrganization';

const props = defineProps<{
    team: { id: string };
    confirmsWithPassword: boolean;
}>();

const confirmingTeamDeletion = ref(false);
const confirmationInput = ref<InstanceType<typeof IdentityConfirmationInput> | null>(null);
const password = ref('');
const passwordError = ref('');
const processing = ref(false);
const organizationStore = useOrganizationStore();

const confirmTeamDeletion = () => {
    confirmingTeamDeletion.value = true;
    setTimeout(() => confirmationInput.value?.focus(), 250);
};

const deleteTeam = async () => {
    if (processing.value || !props.confirmsWithPassword) return;
    processing.value = true;
    passwordError.value = '';
    try {
        await organizationStore.deleteOrganization(props.team.id, { password: password.value });
        // The backend reassigns the user's current organization after deletion,
        // so flush the prefetch cache and reload into the dashboard.
        router.flushAll();
        router.visit(route('dashboard'));
    } catch (error) {
        if (error && typeof error === 'object' && 'response' in error) {
            const response = error.response as
                { status?: number; data?: { errors?: { password?: string[] } } } | undefined;
            if (response?.status === 422) {
                passwordError.value = response.data?.errors?.password?.[0] ?? 'Invalid password.';
            }
        }
        processing.value = false;
        confirmationInput.value?.focus();
    }
};

const closeModal = () => {
    confirmingTeamDeletion.value = false;
    password.value = '';
    passwordError.value = '';
};
</script>

<template>
    <ActionSection>
        <template #title> Delete Organization </template>

        <template #description> Permanently delete this organization. </template>

        <template #content>
            <div class="max-w-xl text-sm text-text-secondary">
                Once a organization is deleted, all of its resources and data will be permanently
                deleted. Before deleting this organization, please download any data or information
                regarding this organization that you wish to retain.
            </div>

            <div class="mt-5">
                <DangerButton @click="confirmTeamDeletion"> Delete Organization </DangerButton>
            </div>

            <!-- Delete Organization Confirmation Modal -->
            <DialogModal :show="confirmingTeamDeletion" @close="closeModal">
                <template #title> Delete Organization </template>

                <template #content>
                    Are you sure you want to delete this organization? Once a organization is
                    deleted, all of its resources and data will be permanently deleted.
                    <template v-if="confirmsWithPassword">
                        Please enter your password to confirm you would like to permanently delete
                        this organization.
                    </template>

                    <IdentityConfirmationInput
                        ref="confirmationInput"
                        v-model:password="password"
                        :confirms-with-password="confirmsWithPassword"
                        :error="passwordError"
                        @submit="deleteTeam" />
                </template>

                <template #footer>
                    <SecondaryButton @click="closeModal"> Cancel </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': processing || !confirmsWithPassword }"
                        :disabled="processing || !confirmsWithPassword"
                        @click="deleteTeam">
                        Delete Organization
                    </DangerButton>
                </template>
            </DialogModal>
        </template>
    </ActionSection>
</template>
