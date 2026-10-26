<script setup lang="ts">
import { ref } from 'vue';
import ActionSection from '@/Components/ActionSection.vue';
import IdentityConfirmationInput from '@/Components/Common/IdentityConfirmation/IdentityConfirmationInput.vue';
import DangerButton from '@/packages/ui/src/Buttons/DangerButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { useDeleteUserMutation } from '@/utils/useUserQuery';
import { getCurrentUserId } from '@/utils/useUser';

const props = defineProps<{
    confirmsWithPassword: boolean;
}>();

const deleteUserMutation = useDeleteUserMutation();

const confirmingUserDeletion = ref(false);
const confirmationInput = ref<InstanceType<typeof IdentityConfirmationInput> | null>(null);
const password = ref('');
const passwordError = ref('');
const processing = ref(false);

function confirmUserDeletion() {
    confirmingUserDeletion.value = true;
    setTimeout(() => confirmationInput.value?.focus(), 250);
}

async function deleteUser() {
    if (processing.value || !props.confirmsWithPassword) return;
    processing.value = true;
    passwordError.value = '';
    try {
        await deleteUserMutation.mutateAsync({
            userId: getCurrentUserId(),
            body: { password: password.value },
        });
        window.location.href = '/';
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
}

function closeModal() {
    confirmingUserDeletion.value = false;
    password.value = '';
    passwordError.value = '';
}
</script>

<template>
    <ActionSection>
        <template #title> Delete Account </template>

        <template #description> Permanently delete your account. </template>

        <template #content>
            <div class="max-w-xl text-sm text-text-secondary">
                Once your account is deleted, all of its resources and data will be permanently
                deleted. Before deleting your account, please download any data or information that
                you wish to retain.
            </div>

            <div class="mt-5">
                <DangerButton @click="confirmUserDeletion"> Delete Account </DangerButton>
            </div>

            <!-- Delete Account Confirmation Modal -->
            <DialogModal :show="confirmingUserDeletion" @close="closeModal">
                <template #title> Delete Account </template>

                <template #content>
                    Are you sure you want to delete your account? Once your account is deleted, all
                    of its resources and data will be permanently deleted.
                    <template v-if="confirmsWithPassword">
                        Please enter your password to confirm you would like to permanently delete
                        your account.
                    </template>

                    <IdentityConfirmationInput
                        ref="confirmationInput"
                        v-model:password="password"
                        :confirms-with-password="confirmsWithPassword"
                        :error="passwordError"
                        @submit="deleteUser" />
                </template>

                <template #footer>
                    <SecondaryButton @click="closeModal"> Cancel </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': processing || !confirmsWithPassword }"
                        :disabled="processing || !confirmsWithPassword"
                        @click="deleteUser">
                        Delete Account
                    </DangerButton>
                </template>
            </DialogModal>
        </template>
    </ActionSection>
</template>
