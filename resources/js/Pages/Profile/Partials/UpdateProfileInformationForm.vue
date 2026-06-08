<script setup lang="ts">
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ActionMessage from '@/Components/ActionMessage.vue';
import FormSection from '@/Components/FormSection.vue';
import { Field, FieldError, FieldLabel } from '@/packages/ui/src/field';
import { Button } from '@/packages/ui/src/Buttons';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import TextInput from '@/packages/ui/src/Input/TextInput.vue';
import {
    useResendUserEmailVerificationMutation,
    useResetUserPendingEmailMutation,
    useUpdateUserMutation,
    useUserQuery,
} from '@/utils/useUserQuery';
import type { UpdateUserBody, User } from '@/packages/api/src';
import { getApiValidationFieldErrors } from '@/utils/apiValidation';

const { user } = useUserQuery();
const updateUser = useUpdateUserMutation();
const resendVerification = useResendUserEmailVerificationMutation();
const resetPendingEmail = useResetUserPendingEmailMutation();

const name = ref('');
const email = ref('');
const timezone = ref('');
const weekStart = ref('');

const photoBase64 = ref<string | null>(null);
const photoPreview = ref<string | null>(null);
const photoInput = ref<HTMLInputElement | null>(null);

const recentlySaved = ref(false);
const resendCooldown = ref(false);
let resendCooldownTimer: ReturnType<typeof setTimeout> | null = null;

function seedForm(u: User) {
    name.value = u.name;
    email.value = u.email;
    timezone.value = u.timezone;
    weekStart.value = u.week_start;
}

watch(
    user,
    (u, prev) => {
        if (u && prev === undefined) seedForm(u);
    },
    { immediate: true }
);

const isUserLoaded = computed(() => user.value !== undefined);
const isSaveDisabled = computed(() => !isUserLoaded.value || updateUser.isPending.value);
const pendingEmail = computed(() => user.value?.pending_email ?? null);
const hasUploadedPhoto = computed(() => {
    const url = user.value?.profile_photo_url;
    return !!url && !url.includes('ui-avatars.com');
});

const fieldErrors = computed<Record<string, string>>(() =>
    getApiValidationFieldErrors(updateUser.error.value)
);

function buildPayload(): UpdateUserBody {
    if (!user.value) return {};
    const body: UpdateUserBody = {};
    if (name.value !== user.value.name) body.name = name.value;

    const typedEmail = email.value.trim().toLowerCase();
    const currentEmail = user.value.email.toLowerCase();
    const currentPending = (user.value.pending_email ?? '').toLowerCase();
    if (typedEmail !== currentEmail && typedEmail !== currentPending) {
        body.email = email.value.trim();
    }

    if (timezone.value !== user.value.timezone) body.timezone = timezone.value;
    if (weekStart.value !== user.value.week_start) {
        body.week_start = weekStart.value as UpdateUserBody['week_start'];
    }
    if (photoBase64.value !== null) body.photo = photoBase64.value;
    return body;
}

function clearPhotoInput() {
    if (photoInput.value) photoInput.value.value = '';
    photoBase64.value = null;
    photoPreview.value = null;
}

function selectNewPhoto() {
    if (!isUserLoaded.value) return;
    photoInput.value?.click();
}

function readSelectedPhoto() {
    if (!isUserLoaded.value) return;
    const file = photoInput.value?.files?.[0];
    if (!file) return;
    const reader = new FileReader();
    reader.onload = (e) => {
        const dataUrl = e.target?.result as string;
        photoBase64.value = dataUrl;
        photoPreview.value = dataUrl;
    };
    reader.readAsDataURL(file);
}

async function save() {
    if (isSaveDisabled.value || !user.value) return;
    const body = buildPayload();
    if (Object.keys(body).length === 0) {
        flashSaved();
        return;
    }
    try {
        const updated = await updateUser.mutateAsync({ userId: user.value.id, body });
        seedForm(updated);
        clearPhotoInput();
        flashSaved();
    } catch {
        // 422: field errors render via fieldErrors. Other errors: toast handled in the mutation.
    }
}

async function removePhoto() {
    if (!isUserLoaded.value || updateUser.isPending.value || !user.value) return;
    try {
        await updateUser.mutateAsync({ userId: user.value.id, body: { photo: null } });
        clearPhotoInput();
    } catch {
        // notification handled by mutation
    }
}

async function clickResend() {
    if (!user.value || resendCooldown.value || resendVerification.isPending.value) return;
    try {
        await resendVerification.mutateAsync(user.value.id);
        resendCooldown.value = true;
        if (resendCooldownTimer) clearTimeout(resendCooldownTimer);
        resendCooldownTimer = setTimeout(() => {
            resendCooldown.value = false;
        }, 5000);
    } catch {
        // notification handled by mutation
    }
}

async function clickCancelEmailChange() {
    if (!user.value || resetPendingEmail.isPending.value) return;
    try {
        // Clears pending_email on the server; the pending banner hides once the
        // me query refetches. The email field already shows the current address.
        await resetPendingEmail.mutateAsync(user.value.id);
    } catch {
        // notification handled by mutation
    }
}

function flashSaved() {
    recentlySaved.value = true;
    setTimeout(() => (recentlySaved.value = false), 2000);
}

onBeforeUnmount(() => {
    if (resendCooldownTimer) clearTimeout(resendCooldownTimer);
});

const page = usePage<{
    timezones: Record<string, string>;
    weekdays: Record<string, string>;
}>();
</script>

<template>
    <FormSection @submitted="save">
        <template #title>Profile Information</template>

        <template #description>
            Update your account's profile information and email address.
        </template>

        <template #form>
            <!-- Profile Photo -->
            <div class="col-span-6 sm:col-span-4">
                <input
                    id="photo"
                    ref="photoInput"
                    type="file"
                    accept="image/jpeg,image/png"
                    class="hidden"
                    :disabled="!isUserLoaded"
                    @change="readSelectedPhoto" />

                <FieldLabel for="photo">Photo</FieldLabel>

                <div v-show="!photoPreview" class="mt-2">
                    <img
                        v-if="user"
                        :src="user.profile_photo_url"
                        :alt="user.name"
                        class="rounded-full h-20 w-20 object-cover" />
                </div>

                <div v-show="photoPreview" class="mt-2">
                    <span
                        class="block rounded-full w-20 h-20 bg-cover bg-no-repeat bg-center"
                        :style="'background-image: url(\'' + photoPreview + '\');'" />
                </div>

                <SecondaryButton
                    class="mt-2 me-2"
                    type="button"
                    :disabled="!isUserLoaded"
                    @click.prevent="selectNewPhoto">
                    Select A New Photo
                </SecondaryButton>

                <SecondaryButton
                    v-if="hasUploadedPhoto"
                    type="button"
                    class="mt-2"
                    :disabled="!isUserLoaded || updateUser.isPending.value"
                    @click.prevent="removePhoto">
                    Remove Photo
                </SecondaryButton>

                <FieldError v-if="fieldErrors.photo" class="mt-2">
                    {{ fieldErrors.photo }}
                </FieldError>
            </div>

            <!-- Name -->
            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="name">Name</FieldLabel>
                <TextInput
                    id="name"
                    v-model="name"
                    type="text"
                    class="block w-full"
                    required
                    :disabled="!isUserLoaded"
                    autocomplete="name" />
                <FieldError v-if="fieldErrors.name">{{ fieldErrors.name }}</FieldError>
            </Field>

            <!-- Email -->
            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="email">Email</FieldLabel>
                <TextInput
                    id="email"
                    v-model="email"
                    type="email"
                    class="block w-full"
                    required
                    :disabled="!isUserLoaded"
                    autocomplete="username" />
                <FieldError v-if="fieldErrors.email">{{ fieldErrors.email }}</FieldError>

                <div v-if="pendingEmail" class="mt-2 text-sm">
                    <p class="text-text-primary">
                        A verification link was sent to
                        <span class="font-medium">{{ pendingEmail }}</span
                        >. Click the link in the email to confirm the change.
                    </p>
                    <div class="mt-2 -ms-3 flex flex-wrap items-center gap-x-1 gap-y-1">
                        <Button
                            v-if="!resendCooldown"
                            variant="ghost"
                            size="sm"
                            type="button"
                            :disabled="!isUserLoaded || resendVerification.isPending.value"
                            @click="clickResend">
                            Resend verification email
                        </Button>
                        <p v-else class="ms-3 font-medium text-green-400">
                            Verification email sent.
                        </p>
                        <Button
                            variant="ghost"
                            size="sm"
                            type="button"
                            :disabled="!isUserLoaded || resetPendingEmail.isPending.value"
                            @click="clickCancelEmailChange">
                            Cancel email change
                        </Button>
                    </div>
                </div>
            </Field>

            <!-- Timezone -->
            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="timezone">Timezone</FieldLabel>
                <select
                    id="timezone"
                    v-model="timezone"
                    name="timezone"
                    required
                    :disabled="!isUserLoaded"
                    class="block w-full border-input-border bg-input-background text-text-primary focus:border-input-border-active rounded-md shadow-sm">
                    <option value="" disabled>Select a Timezone</option>
                    <option
                        v-for="(timezoneTranslated, timezoneValue) in page.props.timezones"
                        :key="timezoneValue"
                        :value="timezoneValue">
                        {{ timezoneTranslated }}
                    </option>
                </select>
                <FieldError v-if="fieldErrors.timezone">{{ fieldErrors.timezone }}</FieldError>
            </Field>

            <!-- Week start -->
            <Field class="col-span-6 sm:col-span-4">
                <FieldLabel for="week_start">Start of the week</FieldLabel>
                <select
                    id="week_start"
                    v-model="weekStart"
                    name="week_start"
                    required
                    :disabled="!isUserLoaded"
                    class="block w-full border-input-border bg-input-background text-text-primary focus:border-input-border-active rounded-md shadow-sm">
                    <option value="" disabled>Select a week day</option>
                    <option
                        v-for="(weekdayTranslated, weekdayValue) in page.props.weekdays"
                        :key="weekdayValue"
                        :value="weekdayValue">
                        {{ weekdayTranslated }}
                    </option>
                </select>
                <FieldError v-if="fieldErrors.week_start">{{ fieldErrors.week_start }}</FieldError>
            </Field>
        </template>

        <template #actions>
            <ActionMessage :on="recentlySaved" class="me-3"> Saved. </ActionMessage>

            <PrimaryButton :class="{ 'opacity-25': isSaveDisabled }" :disabled="isSaveDisabled">
                Save
            </PrimaryButton>
        </template>
    </FormSection>
</template>
