<script setup lang="ts">
import DialogModal from '@/packages/ui/src/DialogModal.vue';
import PrimaryButton from '@/packages/ui/src/Buttons/PrimaryButton.vue';
import { Link } from '@inertiajs/vue3';
import { isBillingActivated } from '@/utils/billing';
import { CreditCardIcon, UserGroupIcon } from '@heroicons/vue/20/solid';
import { canManageBilling, canUpdateOrganization } from '@/utils/permissions';
import { SecondaryButton } from '@/packages/ui/src';

const show = defineModel('show', { default: false });
</script>

<template>
    <DialogModal closeable :show="show" @close="show = false">
        <template #title>
            <div class="flex space-x-2">
                <span> Upgrade Plan </span>
            </div>
        </template>

        <template #content>
            <div>
                <div
                    class="rounded-full flex items-center justify-center w-20 h-20 mx-auto border border-border-tertiary bg-secondary">
                    <UserGroupIcon class="w-12"></UserGroupIcon>
                </div>
                <div class="max-w-sm text-center mx-auto py-4 text-base">
                    <p class="py-1">
                        <slot></slot>
                    </p>
                    <p class="py-1 text-sm">
                        If you want to use this feature,
                        <strong class="font-semibold text-text-primary"
                            >please upgrade to a paid plan</strong
                        >
                        or
                        <strong class="font-semibold text-text-primary"
                            >request a free trial</strong
                        >
                        via
                        <a
                            class="text-accent-200/80 transition text-accent-300"
                            href="mailto:hello@solidtime.io"
                            >hello@solidtime.io</a
                        >
                        to try out this feature.
                    </p>

                    <Link v-if="isBillingActivated() && canManageBilling()" href="/billing">
                        <PrimaryButton
                            v-if="isBillingActivated() && canUpdateOrganization()"
                            type="button"
                            class="mt-6"
                            :icon="CreditCardIcon">
                            Go to Billing
                        </PrimaryButton>
                    </Link>
                </div>
            </div>
        </template>
        <template #footer>
            <SecondaryButton @click="show = false">Close</SecondaryButton>
        </template>
    </DialogModal>
</template>

<style scoped></style>
