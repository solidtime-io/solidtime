<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import axios from 'axios';
import { router, usePage } from '@inertiajs/vue3';
import FormSection from '@/Components/FormSection.vue';
import { Field, FieldError, FieldLabel } from '@/packages/ui/src/field';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import type { User } from '@/types/models';
import { useOrganizationStore } from '@/utils/useOrganization';
import { useNotificationsStore } from '@/utils/notification';
import {
    getApiValidationFieldErrors,
    getApiValidationMessage,
    isApiValidationError,
} from '@/utils/apiValidation';

const name = ref('');
const processing = ref(false);
const createError = ref<unknown>(null);
const organizationStore = useOrganizationStore();
const notifications = useNotificationsStore();

const fieldErrors = computed<Record<string, string>>(() =>
    getApiValidationFieldErrors(createError.value)
);

watch(name, () => {
    createError.value = null;
});

const createTeam = async () => {
    processing.value = true;
    createError.value = null;
    try {
        const organization = await organizationStore.createOrganization(name.value);
        if (organization) {
            notifications.addNotification('success', 'Organization created successfully');
            // The backend already switched the current organization to the new one.
            // Flush Inertia's prefetch cache and do a full reload so the new
            // organization context is picked up everywhere.
            router.flushAll();
            router.visit(route('dashboard'));
        }
    } catch (error) {
        createError.value = error;
        if (isApiValidationError(error)) {
            notifications.addNotification(
                'error',
                getApiValidationMessage(error, 'Failed to create organization')
            );
        } else if (axios.isAxiosError(error)) {
            notifications.addNotification(
                'error',
                'Failed to create organization',
                error.response?.data?.message ?? 'Please try again later.'
            );
        } else {
            notifications.addNotification('error', 'Failed to create organization');
        }
    } finally {
        processing.value = false;
    }
};

const page = usePage<{
    auth: {
        user: User;
    };
}>();
</script>

<template>
    <FormSection @submitted="createTeam">
        <template #title> Organization Details</template>

        <template #description>
            Create a new organization to collaborate with others on projects.
        </template>

        <template #form>
            <div class="col-span-6">
                <FieldLabel>Organization Owner</FieldLabel>

                <div class="flex items-center mt-2">
                    <img
                        class="object-cover w-12 h-12 rounded-full"
                        :src="page.props.auth.user.profile_photo_url"
                        :alt="page.props.auth.user.name" />

                    <div class="ms-4 leading-tight">
                        <div class="text-text-primary">
                            {{ page.props.auth.user.name }}
                        </div>
                        <div class="text-sm text-text-secondary">
                            {{ page.props.auth.user.email }}
                        </div>
                    </div>
                </div>
            </div>

            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="name">Organization Name</FieldLabel>
                <TextInput
                    id="name"
                    v-model="name"
                    type="text"
                    class="block w-full"
                    autofocus
                    :aria-invalid="Boolean(fieldErrors.name)" />
                <FieldError v-if="fieldErrors.name">{{ fieldErrors.name }}</FieldError>
            </Field>
        </template>

        <template #actions>
            <PrimaryButton :class="{ 'opacity-25': processing }" :disabled="processing">
                Create
            </PrimaryButton>
        </template>
    </FormSection>
</template>
