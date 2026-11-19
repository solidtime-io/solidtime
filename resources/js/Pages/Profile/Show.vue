<script setup lang="ts">
import ActionSection from '@/Components/ActionSection.vue';
import AppLayout from '@/Layouts/AppLayout.vue';
import DeleteUserForm from '@/Pages/Profile/Partials/DeleteUserForm.vue';
import LogoutOtherBrowserSessionsForm from '@/Pages/Profile/Partials/LogoutOtherBrowserSessionsForm.vue';
import SectionBorder from '@/Components/SectionBorder.vue';
import TwoFactorAuthenticationForm from '@/Pages/Profile/Partials/TwoFactorAuthenticationForm.vue';
import UpdatePasswordForm from '@/Pages/Profile/Partials/UpdatePasswordForm.vue';
import UpdateProfileInformationForm from '@/Pages/Profile/Partials/UpdateProfileInformationForm.vue';
import type { Session } from '@/types/jetstream';
import ApiTokensForm from '@/Pages/Profile/Partials/ApiTokensForm.vue';
import ThemeForm from '@/Pages/Profile/Partials/ThemeForm.vue';
import NotificationSettingsForm from '@/Pages/Profile/Partials/NotificationSettingsForm.vue';
import { useUserQuery } from '@/utils/useUserQuery';
import { computed } from 'vue';

defineProps<{
    confirmsTwoFactorAuthentication: boolean;
    sessions: Session[];
}>();

const { user } = useUserQuery();
const confirmsWithPassword = computed(() => user.value?.confirms_with_password ?? false);
</script>

<template>
    <AppLayout title="Profile">
        <template #header>
            <h2 class="font-semibold text-xl text-text-primary leading-tight">Profile</h2>
        </template>

        <div>
            <div class="max-w-7xl mx-auto py-10 sm:px-6 lg:px-8">
                <div>
                    <UpdateProfileInformationForm />

                    <SectionBorder />
                </div>

                <div>
                    <ThemeForm />

                    <SectionBorder />
                </div>

                <div>
                    <NotificationSettingsForm />

                    <SectionBorder />
                </div>

                <template v-if="confirmsWithPassword">
                    <div>
                        <UpdatePasswordForm class="mt-10 sm:mt-0" />

                        <SectionBorder />
                    </div>

                    <div>
                        <TwoFactorAuthenticationForm
                            :requires-confirmation="confirmsTwoFactorAuthentication"
                            class="mt-10 sm:mt-0" />

                        <SectionBorder />
                    </div>
                </template>
                <!-- Without a password the user may sign in with (e.g. SSO), neither form can be used. -->
                <div v-else-if="user">
                    <ActionSection class="mt-10 sm:mt-0">
                        <template #title> Password and Two Factor Authentication </template>
                        <template #description> How you sign in to your account. </template>
                        <template #content>
                            <div
                                class="max-w-xl text-sm text-text-secondary"
                                data-testid="sign_in_managed_externally">
                                You sign in through your identity provider. Your password and two
                                factor authentication are managed there.
                            </div>
                        </template>
                    </ActionSection>

                    <SectionBorder />
                </div>

                <LogoutOtherBrowserSessionsForm
                    :sessions="sessions"
                    :confirms-with-password="confirmsWithPassword"
                    class="mt-10 sm:mt-0" />
                <SectionBorder />

                <ApiTokensForm></ApiTokensForm>

                <SectionBorder />

                <DeleteUserForm
                    class="mt-10 sm:mt-0"
                    :confirms-with-password="confirmsWithPassword" />
            </div>
        </div>
    </AppLayout>
</template>
