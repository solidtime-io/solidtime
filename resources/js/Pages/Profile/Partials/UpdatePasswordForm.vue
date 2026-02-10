<script setup lang="ts">
import { ref } from 'vue';
import { useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import { Field, FieldLabel, FieldError } from '@/packages/ui/src/field';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';

const passwordInput = ref<HTMLElement | null>(null);
const currentPasswordInput = ref<HTMLElement | null>(null);

const form = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    form.put(route('user-password.update'), {
        errorBag: 'updatePassword',
        preserveScroll: true,
        onSuccess: () => form.reset(),
        onError: () => {
            if (form.errors.password) {
                form.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }

            if (form.errors.current_password) {
                form.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};
</script>

<template>
    <FormSection @submitted="updatePassword">
        <template #title> Update Password </template>

        <template #description>
            Ensure your account is using a long, random password to stay secure.
        </template>

        <template #form>
            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="current_password">Current Password</FieldLabel>
                <TextInput
                    id="current_password"
                    ref="currentPasswordInput"
                    v-model="form.current_password"
                    type="password"
                    class="block w-full"
                    autocomplete="current-password" />
                <FieldError v-if="form.errors.current_password">{{
                    form.errors.current_password
                }}</FieldError>
            </Field>

            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="password">New Password</FieldLabel>
                <TextInput
                    id="password"
                    ref="passwordInput"
                    v-model="form.password"
                    type="password"
                    class="block w-full"
                    autocomplete="new-password" />
                <FieldError v-if="form.errors.password">{{ form.errors.password }}</FieldError>
            </Field>

            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="password_confirmation">Confirm Password</FieldLabel>
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="block w-full"
                    autocomplete="new-password" />
                <FieldError v-if="form.errors.password_confirmation">{{
                    form.errors.password_confirmation
                }}</FieldError>
            </Field>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3"> Saved. </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save
            </PrimaryButton>
        </template>
    </FormSection>
</template>
