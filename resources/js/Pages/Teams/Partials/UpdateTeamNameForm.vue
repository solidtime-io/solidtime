<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import TextInput from '@/Components/TextInput.vue';
import type { Organization } from '@/types/models';
import type { Permissions } from '@/types/jetstream';
import { CreditCardIcon } from '@heroicons/vue/20/solid';
import { isBillingActivated } from '@/utils/billing';
import { canUpdateOrganization } from '@/utils/permissions';

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

        <template #description>
            The organization's name and owner information.
        </template>

        <template #form>
            <!-- Organization Owner Information -->
            <div class="col-span-6 flex items-center justify-between">
                <div class="">
                    <InputLabel value="Organization Owner" />

                    <div class="flex items-center mt-2">
                        <img
                            class="w-12 h-12 rounded-full object-cover"
                            :src="team.owner.profile_photo_url"
                            :alt="team.owner.name" />

                        <div class="ms-4 leading-tight">
                            <div class="text-white">
                                {{ team.owner.name }}
                            </div>
                            <div class="text-muted text-sm">
                                {{ team.owner.email }}
                            </div>
                        </div>
                    </div>
                </div>
                <div>
                    <Link href="/billing">
                        <PrimaryButton
                            type="button"
                            v-if="
                                isBillingActivated() && canUpdateOrganization()
                            ">
                            <CreditCardIcon class="w-5 h-5 me-2" />
                            Go to Billing
                        </PrimaryButton>
                    </Link>
                </div>
            </div>

            <!-- Organization Name -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="name" value="Organization Name" />

                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    :disabled="!permissions.canUpdateTeam" />

                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <!-- Currency -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="currency" value="Currency" />
                <select
                    name="currency"
                    id="currency"
                    v-model="form.currency"
                    :disabled="!permissions.canUpdateTeam"
                    class="mt-1 block w-full border-input-border bg-input-background text-white focus:border-input-border-active rounded-md shadow-sm">
                    <option value="" disabled>Select a currency</option>
                    <option
                        v-for="(currencyTranslated, currencyKey) in $page.props
                            .currencies"
                        :key="currencyKey"
                        :value="currencyKey">
                        {{ currencyKey }} - {{ currencyTranslated }}
                    </option>
                </select>
                <InputError :message="form.errors.currency" class="mt-2" />
            </div>
        </template>

        <template v-if="permissions.canUpdateTeam" #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3">
                Saved.
            </ActionMessage>

            <PrimaryButton
                :class="{ 'opacity-25': form.processing }"
                :disabled="form.processing">
                Save
            </PrimaryButton>
        </template>
    </FormSection>
</template>
