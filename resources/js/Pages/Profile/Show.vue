<script setup lang="ts">
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

defineProps<{
    confirmsTwoFactorAuthentication: boolean;
    sessions: Session[];
}>();
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
                    <UpdatePasswordForm class="mt-10 sm:mt-0" />

                    <SectionBorder />
                </div>

                <div>
                    <TwoFactorAuthenticationForm
                        :requires-confirmation="confirmsTwoFactorAuthentication"
                        class="mt-10 sm:mt-0" />

                    <SectionBorder />
                </div>

                <LogoutOtherBrowserSessionsForm :sessions="sessions" class="mt-10 sm:mt-0" />
                <SectionBorder />

                <ApiTokensForm></ApiTokensForm>

                <SectionBorder />

                <DeleteUserForm class="mt-10 sm:mt-0" />
            </div>
        </div>
    </AppLayout>
</template>
