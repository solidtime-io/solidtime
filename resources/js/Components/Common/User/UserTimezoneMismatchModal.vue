<script setup lang="ts">
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { onMounted, ref } from 'vue';
import { getUserTimezone } from '@/packages/ui/src/utils/settings';
import { getDayJsInstance } from '@/packages/ui/src/utils/time';
import { useForm, usePage } from '@inertiajs/vue3';
import type { User } from '@/types/models';
import { useSessionStorage } from '@vueuse/core';

const show = defineModel('show', { default: false });
const saving = defineModel('saving', { default: false });

const timezone = ref('');
const userTimezone = ref('');

const page = usePage<{
    auth: {
        user: User;
    };
}>();

const hideTimezoneMismatchModal = useSessionStorage<boolean>('hide-timezone-mismatch-modal', false);

onMounted(() => {
    timezone.value = Intl.DateTimeFormat().resolvedOptions().timeZone;
    userTimezone.value = getUserTimezone();

    if (
        getDayJsInstance()().tz(timezone.value).format() !==
            getDayJsInstance()().tz(userTimezone.value).format() &&
        !hideTimezoneMismatchModal.value
    ) {
        show.value = true;
    }
});

function submit() {
    saving.value = true;
    const form = useForm({
        _method: 'PUT',
        timezone: timezone.value,
        name: page.props.auth.user.name,
        email: page.props.auth.user.email,
        week_start: page.props.auth.user.week_start,
    });

    form.post(route('user-profile-information.update'), {
        errorBag: 'updateProfileInformation',
        preserveScroll: true,
        onSuccess: () => {
            saving.value = false;
            show.value = false;
            location.reload();
        },
    });
}

function cancel() {
    show.value = false;
    hideTimezoneMismatchModal.value = true;
}
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex justify-center">
                <span> Timezone mismatch detected </span>
            </div>
        </template>
        <template #content>
            <div class="flex items-center space-x-4">
                <div class="col-span-6 sm:col-span-4 flex-1 space-y-2">
                    <p>
                        The timezone of your device does not match the timezone in your user
                        settings. <br />
                        <strong
                            >We highly recommend that you update your timezone settings to your
                            current timezone.</strong
                        >
                    </p>

                    <p>
                        Want to change your timezone setting from
                        <strong>{{ userTimezone }}</strong> to <strong>{{ timezone }}</strong
                        >.
                    </p>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="cancel"> Cancel</SecondaryButton>
            <PrimaryButton
                class="ms-3"
                :class="{ 'opacity-25': saving }"
                :disabled="saving"
                @click="submit()">
                Update timezone
            </PrimaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
