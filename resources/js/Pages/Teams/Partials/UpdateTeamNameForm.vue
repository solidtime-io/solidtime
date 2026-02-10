<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
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

const props = defineProps<{
    team: Organization;
    permissions: Permissions;
}>();

const form = useForm({
    name: props.team.name,
    currency: props.team.currency,
});

const updateTeamName = () => {
    form.put(route('teams.update', props.team.id), {
        errorBag: 'updateTeamName',
        preserveScroll: true,
    });
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
                            <div class="text-text-secondary text-sm">
                                {{ team.owner.email }}
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

                <FieldError v-if="form.errors.name">{{ form.errors.name }}</FieldError>
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
                <FieldError v-if="form.errors.currency">{{ form.errors.currency }}</FieldError>
            </Field>
        </template>

        <template v-if="permissions.canUpdateTeam" #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3"> Saved. </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save
            </PrimaryButton>
        </template>
    </FormSection>
</template>
