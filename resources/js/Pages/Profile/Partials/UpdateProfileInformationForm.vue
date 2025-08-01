<script setup lang="ts">
import { ref } from 'vue';
import { Link, router, useForm, usePage } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import InputError from '@/packages/ui/src/Input/InputError.vue';
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import type { User } from '@/types/models';

const props = defineProps<{
    user: User;
}>();

const form = useForm({
    _method: 'PUT',
    name: props.user.name,
    email: props.user.email,
    photo: null as File | null,
    timezone: props.user.timezone,
    week_start: props.user.week_start,
});

const verificationLinkSent = ref<boolean | null>(null);
const photoPreview = ref<ArrayBuffer | undefined | string | null>(null);
const photoInput = ref<HTMLInputElement | null>(null);

const updateProfileInformation = () => {
    if (photoInput.value && photoInput.value.files && photoInput.value.files?.length > 0) {
        form.photo = photoInput.value?.files[0];
    }

    form.post(route('user-profile-information.update'), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
        onSuccess: () => clearPhotoFileInput(),
    });
};

const sendEmailVerification = () => {
    verificationLinkSent.value = true;
};

const selectNewPhoto = () => {
    photoInput.value?.click();
};

const updatePhotoPreview = () => {
    if (photoInput.value?.files) {
        const photo = photoInput.value?.files[0];
        if (!photo) return;

        const reader = new FileReader();

        reader.onload = (e) => {
            photoPreview.value = e.target?.result;
        };

        reader.readAsDataURL(photo);
    }
};

const deletePhoto = () => {
    router.delete(route('current-user-photo.destroy'), {
        preserveScroll: true,
        onSuccess: () => {
            photoPreview.value = null;
            clearPhotoFileInput();
        },
    });
};

const clearPhotoFileInput = () => {
    if (photoInput.value?.value) {
        photoInput.value.value = '';
    }
};

const page = usePage<{
    jetstream: {
        managesProfilePhotos: boolean;
        hasEmailVerification: boolean;
    };
}>();
</script>

<template>
    <FormSection @submitted="updateProfileInformation">
        <template #title> Profile Information</template>

        <template #description>
            Update your account's profile information and email address.
        </template>

        <template #form>
            <!-- Profile Photo -->
            <div v-if="page.props.jetstream.managesProfilePhotos" class="col-span-6 sm:col-span-4">
                <!-- Profile Photo File Input -->
                <input
                    id="photo"
                    ref="photoInput"
                    type="file"
                    class="hidden"
                    @change="updatePhotoPreview" />

                <InputLabel for="photo" value="Photo" />

                <!-- Current Profile Photo -->
                <div v-show="!photoPreview" class="mt-2">
                    <img
                        :src="user.profile_photo_url"
                        :alt="user.name"
                        class="rounded-full h-20 w-20 object-cover" />
                </div>

                <!-- New Profile Photo Preview -->
                <div v-show="photoPreview" class="mt-2">
                    <span
                        class="block rounded-full w-20 h-20 bg-cover bg-no-repeat bg-center"
                        :style="'background-image: url(\'' + photoPreview + '\');'" />
                </div>

                <SecondaryButton class="mt-2 me-2" type="button" @click.prevent="selectNewPhoto">
                    Select A New Photo
                </SecondaryButton>

                <SecondaryButton
                    v-if="user.profile_photo_path"
                    type="button"
                    class="mt-2"
                    @click.prevent="deletePhoto">
                    Remove Photo
                </SecondaryButton>

                <InputError :message="form.errors.photo" class="mt-2" />
            </div>

            <!-- Name -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="name" value="Name" />
                <TextInput
                    id="name"
                    v-model="form.name"
                    type="text"
                    class="mt-1 block w-full"
                    required
                    autocomplete="name" />
                <InputError :message="form.errors.name" class="mt-2" />
            </div>

            <!-- Email -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="email" value="Email" />
                <TextInput
                    id="email"
                    v-model="form.email"
                    type="email"
                    class="mt-1 block w-full"
                    required
                    autocomplete="username" />
                <InputError :message="form.errors.email" class="mt-2" />

                <div
                    v-if="
                        page.props.jetstream.hasEmailVerification && user.email_verified_at === null
                    ">
                    <p class="text-sm mt-2 text-text-primary">
                        Your email address is unverified.

                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="underline text-sm text-text-secondary hover:text-text-secondary rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 dark:focus:ring-offset-gray-800"
                            @click.prevent="sendEmailVerification">
                            Click here to re-send the verification email.
                        </Link>
                    </p>

                    <div
                        v-show="verificationLinkSent"
                        class="mt-2 font-medium text-sm text-green-400">
                        A new verification link has been sent to your email address.
                    </div>
                </div>
            </div>

            <!-- Timezone -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="timezone" value="Timezone" />
                <select
                    id="timezone"
                    v-model="form.timezone"
                    name="timezone"
                    required
                    class="mt-1 block w-full border-input-border bg-input-background text-text-primary focus:border-input-border-active rounded-md shadow-sm">
                    <option value="" disabled>Select a Timezone</option>
                    <option
                        v-for="(timezoneTranslated, timezoneKey) in $page.props.timezones"
                        :key="timezoneKey"
                        :value="timezoneKey">
                        {{ timezoneTranslated }}
                    </option>
                </select>
                <InputError :message="form.errors.timezone" class="mt-2" />
            </div>

            <!-- Week start -->
            <div class="col-span-6 sm:col-span-4">
                <InputLabel for="week_start" value="Start of the week" />
                <select
                    id="week_start"
                    v-model="form.week_start"
                    name="week_start"
                    required
                    class="mt-1 block w-full border-input-border bg-input-background text-text-primary focus:border-input-border-active rounded-md shadow-sm">
                    <option value="" disabled>Select a week day</option>
                    <option
                        v-for="(weekdayTranslated, weekdayKey) in $page.props.weekdays"
                        :key="weekdayKey"
                        :value="weekdayKey">
                        {{ weekdayTranslated }}
                    </option>
                </select>
                <InputError :message="form.errors.week_start" class="mt-2" />
            </div>
        </template>

        <template #actions>
            <ActionMessage :on="form.recentlySuccessful" class="me-3"> Saved. </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': form.processing }" :disabled="form.processing">
                Save
            </PrimaryButton>
        </template>
    </FormSection>
</template>
