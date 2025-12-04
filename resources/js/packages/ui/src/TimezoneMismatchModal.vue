<script setup lang="ts">
import SecondaryButton from './Buttons/SecondaryButton.vue';
import DialogModal from './DialogModal.vue';
import PrimaryButton from './Buttons/PrimaryButton.vue';
import { onMounted, ref } from 'vue';
import { getUserTimezone } from './utils/settings';
import { getDayJsInstance } from './utils/time';
import { useSessionStorage } from '@vueuse/core';

const show = defineModel('show', { default: false });

const emit = defineEmits<{
    update: [timezone: string];
    cancel: [];
}>();

defineProps<{
    saving?: boolean;
}>();

const timezone = ref('');
const userTimezone = ref('');
const shouldShow = ref(false);

const hideTimezoneMismatchModal = useSessionStorage<boolean>('hide-timezone-mismatch-modal', false);

/**
 * Check if timezone mismatch exists and should be shown
 */
function checkTimezoneMismatch(): boolean {
    timezone.value = Intl.DateTimeFormat().resolvedOptions().timeZone;
    userTimezone.value = getUserTimezone();

    const now = getDayJsInstance()();

    const hasMismatch =
        now.tz(timezone.value).format() !== now.tz(userTimezone.value).format() &&
        !hideTimezoneMismatchModal.value;

    shouldShow.value = hasMismatch;
    return hasMismatch;
}

onMounted(() => {
    checkTimezoneMismatch();
    if (shouldShow.value) {
        show.value = true;
    }
});

function submit() {
    emit('update', timezone.value);
}

function cancel() {
    show.value = false;
    hideTimezoneMismatchModal.value = true;
    emit('cancel');
}

// Expose methods for parent component
defineExpose({
    checkTimezoneMismatch,
    currentTimezone: timezone,
    userTimezone,
});
</script>

<template>
    <DialogModal closeable :show="show && shouldShow" @close="cancel">
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
