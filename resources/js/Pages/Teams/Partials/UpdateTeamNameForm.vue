<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import axios from 'axios';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import { Field, FieldLabel, FieldError } from '@/packages/ui/src/field';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import type { Organization } from '@/types/models';
import type { Permissions } from '@/types/jetstream';
import { CreditCardIcon } from '@heroicons/vue/20/solid';
import { isBillingActivated } from '@/utils/billing';
import { canManageBilling } from '@/utils/permissions';
import { api } from '@/packages/api/src';
import { useNotificationsStore } from '@/utils/notification';
import { getApiValidationFieldErrors, isApiValidationError } from '@/utils/apiValidation';

const props = defineProps<{
    team: Organization;
    permissions: Permissions;
}>();

const form = reactive({
    name: props.team.name,
    currency: props.team.currency,
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);
const recentlySuccessful = ref(false);
const notifications = useNotificationsStore();
let recentlySuccessfulTimeout: ReturnType<typeof setTimeout> | undefined;

const updateTeamName = async () => {
    processing.value = true;
    recentlySuccessful.value = false;
    errors.value = {};
    try {
        await api.updateOrganization(
            {
                name: form.name,
                currency: form.currency,
            },
            {
                params: {
                    organization: props.team.id,
                },
            }
        );
        notifications.addNotification('success', 'Organization updated successfully');
        recentlySuccessful.value = true;
        if (recentlySuccessfulTimeout) {
            clearTimeout(recentlySuccessfulTimeout);
        }
        recentlySuccessfulTimeout = setTimeout(() => {
            recentlySuccessful.value = false;
        }, 2000);
        router.reload({ only: ['auth', 'team'] });
    } catch (error) {
        if (isApiValidationError(error)) {
            errors.value = getApiValidationFieldErrors(error);
        } else if (axios.isAxiosError(error)) {
            notifications.addNotification(
                'error',
                'Failed to update organization',
                error.response?.data?.message ?? 'Please try again later.'
            );
        } else {
            notifications.addNotification('error', 'Failed to update organization');
        }
    } finally {
        processing.value = false;
    }
};
</script>

<template>
    <FormSection @submitted="updateTeamName">
        <template #title> Organization Name</template>

        <template #description> The organization's name and owner information. </template>

        <template #form>
            <!-- Organization Owner Information -->
            <div class="col-span-6 flex items-center justify-between">
                <div class="">
                    <FieldLabel>Organization Owner</FieldLabel>

                    <div class="flex items-center mt-2">
                        <img
                            class="w-12 h-12 rounded-full object-cover"
                            :src="team.owner.profile_photo_url"
                            :alt="team.owner.name" />

                        <div class="ms-4 leading-tight">
                            <div class="text-text-primary">
                                {{ team.owner.name }}
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <Link v-if="isBillingActivated() && canManageBilling()" href="/billing">
                        <PrimaryButton :icon="CreditCardIcon" type="button">
                            Go to Billing
                        </PrimaryButton>
                    </Link>
                </div>
            </div>

            <!-- Organization Name -->
            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="name">Organization Name</FieldLabel>

                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="block w-full"
                    :disabled="!permissions.canUpdateTeam" />

                <FieldError v-if="errors.name">{{ errors.name }}</FieldError>
            </Field>

            <!-- Currency -->
            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="currency">Currency</FieldLabel>
                <select
                    id="currency"
                    v-model="form.currency"
                    name="currency"
                    :disabled="!permissions.canUpdateTeam"
                    class="block w-full border-input-border bg-input-background text-text-primary focus:border-input-border-active rounded-md shadow-sm">
                    <option value="" disabled>Select a currency</option>
                    <option
                        v-for="(currencyTranslated, currencyKey) in $page.props.currencies"
                        :key="currencyKey"
                        :value="currencyKey">
                        {{ currencyKey }} - {{ currencyTranslated }}
                    </option>
                </select>
                <FieldError v-if="errors.currency">{{ errors.currency }}</FieldError>
            </Field>
        </template>

        <template v-if="permissions.canUpdateTeam" #actions>
            <ActionMessage :on="recentlySuccessful" class="me-3"> Saved. </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': processing }" :disabled="processing">
                Save
            </PrimaryButton>
        </template>
    </FormSection>
</template>
