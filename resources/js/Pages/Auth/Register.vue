<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import AuthenticationCard from '@/Components/AuthenticationCard.vue';
import AuthenticationCardLogo from '@/Components/AuthenticationCardLogo.vue';
import Checkbox from '@/packages/ui/src/Input/Checkbox.vue';
import InputError from '@/packages/ui/src/Input/InputError.vue';
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';

const form = useForm({
    name: '',
    email: '',
    password: '',
    password_confirmation: '',
    terms: false,
    timezone: Intl.DateTimeFormat().resolvedOptions().timeZone ?? null,
    newsletter_consent: false,
});

const submit = () => {
    form.post(route('register'), {
        onSuccess: () => {
            form.reset('password', 'password_confirmation');
        },
    });
};

const page = usePage<{
    terms_url: string | null;
    privacy_policy_url: string | null;
    newsletter_consent: boolean;
    jetstream: {
        hasTermsAndPrivacyPolicyFeature: boolean;
    };
    flash: {
        message: string;
    };
}>();
</script>

<template>
    <Head title="Register" />

    <AuthenticationCard>
        <template #logo>
            <AuthenticationCardLogo />
        </template>

        <template #actions>
            <Link
                class="py-8 text-text-secondary text-sm font-medium opacity-90 hover:opacity-100 transition"
                :href="route('login')">
                Already have an account?
                <span class="text-text-primary">Login here!</span>
            </Link>
        </template>

        <div
            v-if="page.props.flash?.message"
            class="bg-red-400 text-black text-center w-full px-3 py-1 mb-4 rounded-lg">
            {{ page.props.flash?.message }}
        </div>

        <form @submit.prevent="submit">
            <div>
                <InputLabel for="name" value="Name" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autofocus
                    autocomplete="name" />
                <InputError class="mt-2" :message="form.errors.name" />
            </div>

            <div class="mt-4">
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autocomplete="username" />
                <InputError class="mt-2" :message="form.errors.email" />
            </div>

            <div class="mt-4">
                <InputLabel for="password" value="Password" />
                <TextInput
                    id="password"
                    v-model="form.password"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password" />
                <InputError class="mt-2" :message="form.errors.password" />
            </div>

            <div class="mt-4">
                <InputLabel for="password_confirmation" value="Confirm Password" />
                <TextInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    type="password"
                    class="mt-1 block w-full"
                    required
                    autocomplete="new-password" />
                <InputError class="mt-2" :message="form.errors.password_confirmation" />
            </div>

            <div
                v-if="
                    page.props.jetstream.hasTermsAndPrivacyPolicyFeature &&
                    page.props.terms_url !== null &&
                    page.props.privacy_policy_url !== null
                "
                class="mt-4">
                <InputLabel for="terms">
                    <div class="flex items-center">
                        <Checkbox id="terms" v-model:checked="form.terms" name="terms" />

                        <div class="ms-2">
                            I agree to the
                            <a
                                target="_blank"
                                :href="page.props.terms_url"
                                class="underline text-sm text-text-secondary hover:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >Terms of Service</a
                            >
                            and
                            <a
                                target="_blank"
                                :href="page.props.privacy_policy_url"
                                class="underline text-sm text-text-secondary hover:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500"
                                >Privacy Policy</a
                            >
                        </div>
                    </div>
                    <InputError class="mt-2" :message="form.errors.terms" />
                </InputLabel>
            </div>

            <div v-if="page.props.newsletter_consent" class="mt-4">
                <InputLabel for="newsletter_consent">
                    <div class="flex items-center">
                        <Checkbox
                            id="newsletter_consent"
                            v-model:checked="form.newsletter_consent"
                            name="newsletter_consent" />

                        <div class="ms-2">
                            I agree to receive emails about product related updates
                        </div>
                    </div>
                    <InputError class="mt-2" :message="form.errors.newsletter_consent" />
                </InputLabel>
            </div>

            <div class="flex items-center justify-end mt-4">
                <Link
                    :href="route('login')"
                    class="underline text-sm text-text-secondary hover:text-white rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500">
                    Already registered?
                </Link>

                <PrimaryButton
                    class="ms-4"
                    :class="{ 'opacity-25': form.processing }"
                    :disabled="form.processing">
                    Register
                </PrimaryButton>
            </div>
        </form>
    </AuthenticationCard>
</template>
