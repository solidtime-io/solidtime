<script setup lang="ts">
import { ref } from 'vue';
import axios from 'axios';
import ActionSection from '@/Components/ActionSection.vue';
import DangerButton from '@/packages/ui/src/Buttons/DangerButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { Field, FieldError } from '@/packages/ui/src/field';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import { useDeleteUserMutation, useUserQuery } from '@/utils/useUserQuery';

const { user } = useUserQuery();
const deleteUserMutation = useDeleteUserMutation();

const confirmingUserDeletion = ref(false);
const passwordInput = ref<HTMLInputElement | null>(null);
const password = ref('');
const passwordError = ref('');
const processing = ref(false);

function confirmUserDeletion() {
    confirmingUserDeletion.value = true;
    setTimeout(() => passwordInput.value?.focus(), 250);
}

async function deleteUser() {
    if (!user.value || processing.value) return;
    processing.value = true;
    passwordError.value = '';
    try {
        await axios.post(route('password.confirm'), { password: password.value });
    } catch (error) {
        processing.value = false;
        if (axios.isAxiosError(error) && error.response?.status === 422) {
            passwordError.value = error.response.data?.errors?.password?.[0] ?? 'Invalid password.';
        } else {
            passwordError.value = 'Could not confirm password. Please try again.';
        }
        passwordInput.value?.focus();
        return;
    }
    try {
        await deleteUserMutation.mutateAsync(user.value.id);
        window.location.href = '/';
    } catch {
        processing.value = false;
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
                    of its resources and data will be permanently deleted. Please enter your
                    password to confirm you would like to permanently delete your account.

                    <Field class="mt-4">
                        <TextInput
                            ref="passwordInput"
                            v-model="password"
                            type="password"
                            class="block w-3/4"
                            placeholder="Password"
                            autocomplete="current-password"
                            @keyup.enter="deleteUser" />

                        <FieldError v-if="passwordError">{{ passwordError }}</FieldError>
                    </Field>
                </template>

                <template #footer>
                    <SecondaryButton @click="closeModal"> Cancel </SecondaryButton>

                    <DangerButton
                        class="ms-3"
                        :class="{ 'opacity-25': processing }"
                        :disabled="processing"
                        @click="deleteUser">
                        Delete Account
                    </DangerButton>
                </template>
            </DialogModal>
        </template>
    </ActionSection>
</template>
