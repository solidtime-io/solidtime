<script setup lang="ts">
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import DangerButton from '@/packages/ui/src/Buttons/DangerButton.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import { XMarkIcon } from '@heroicons/vue/16/solid';
import { useNotificationsStore } from '@/utils/notification';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { api } from '@/packages/api/src';

const show = defineModel<boolean>('show', { default: false });

const processing = ref(false);
const { handleApiRequestNotifications } = useNotificationsStore();

const lostFeatures = [
    'Billable hours & rates',
    'Invoicing',
    'Project & task estimates',
    'Rounding of time entries',
    'PDF reports & shareable reports',
    'Team support & access management',
];

async function cancelTrial() {
    // Note: The organization of this page, the current organization on the server can differ if the user switched organizations in another tab
    const organizationId = getCurrentOrganizationId();
    if (processing.value || organizationId === null) {
        return;
    }
    processing.value = true;
    try {
        await handleApiRequestNotifications(
            () => api.cancelTrial(undefined, { params: { organization: organizationId } }),
            'Your trial has been canceled',
            'Your trial could not be canceled, please try again',
            () => {
                show.value = false;
                // Reloads the shared billing props of the page, they still contain the trial otherwise
                router.reload();
            }
        );
    } finally {
        processing.value = false;
    }
}
</script>

<template>
    <DialogModal :show="show" closeable @close="show = false">
        <template #title>Cancel trial</template>

        <template #content>
            <p class="text-sm text-text-secondary">
                Your organization switches to the Free plan right away. You will lose access to
                these Professional features:
            </p>
            <ul class="mt-4 grid gap-2 sm:grid-cols-2">
                <li
                    v-for="feature in lostFeatures"
                    :key="feature"
                    class="flex items-center gap-2 text-sm text-text-primary">
                    <XMarkIcon class="h-4 w-4 shrink-0 text-red-500/80" />
                    {{ feature }}
                </li>
            </ul>
            <p class="mt-5 text-sm text-text-secondary">
                Organizations with more than one member are blocked until you upgrade or remove
                everyone except the owner.
            </p>
        </template>

        <template #footer>
            <SecondaryButton @click="show = false">Keep trial</SecondaryButton>
            <DangerButton
                class="ms-3"
                data-testid="confirm_cancel_trial"
                :class="{ 'opacity-25': processing }"
                :disabled="processing"
                @click="cancelTrial">
                Cancel trial
            </DangerButton>
        </template>
    </DialogModal>
</template>
