<template>
    <div
        aria-live="assertive"
        class="pointer-events-none fixed inset-0 flex items-end px-4 py-6 sm:items-end sm:p-6 sm:pb-24 z-[70]">
        <div class="flex w-full flex-col items-center space-y-4 sm:items-end">
            <Notification
                v-for="notification in notifications"
                :key="notification.uuid"
                :type="notification.type"
                :title="notification.title"
                :message="notification.message"></Notification>
        </div>
    </div>
    <DialogModal :show="showActionBlockedModal">
        <template #title>
            <div class="flex space-x-2">
                <span> Action blocked </span>
            </div>
        </template>

        <template #content>
            <div
                class="rounded-full flex items-center justify-center w-16 h-16 mx-auto border border-border-tertiary bg-secondary">
                <XCircleIcon class="w-10"></XCircleIcon>
            </div>
            <div class="max-w-sm text-center mx-auto py-4 text-base">
                <p class="py-1">
                    Your organization is currently
                    <strong class="font-semibold">blocked from performing this action</strong>
                </p>
                <p class="py-1">
                    To unblock your organization, please
                    <strong class="font-semibold"> upgrade to a premium plan</strong>
                    or remove all users except the owner.
                </p>

                <Link v-if="isBillingActivated() && canManageBilling()" href="/billing">
                    <PrimaryButton :icon="CreditCardIcon" type="button" class="mt-6">
                        Go to Billing
                    </PrimaryButton>
                </Link>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="showActionBlockedModal = false"> Cancel</SecondaryButton>
        </template>
    </DialogModal>
</template>

<script setup lang="ts">
import Notification from '@/Components/Common/Notification/Notification.vue';
import { storeToRefs } from 'pinia';
import { useNotificationsStore } from '@/utils/notification';
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import { isBillingActivated } from '@/utils/billing';
import { canManageBilling } from '@/utils/permissions';
import { CreditCardIcon, XCircleIcon } from '@heroicons/vue/20/solid';
import { Link } from '@inertiajs/vue3';
import PrimaryButton from '../packages/ui/src/Buttons/PrimaryButton.vue';
import SecondaryButton from '../packages/ui/src/Buttons/SecondaryButton.vue';

const { notifications, showActionBlockedModal } = storeToRefs(useNotificationsStore());
</script>
