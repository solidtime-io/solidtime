<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import {
    CheckBadgeIcon,
    XMarkIcon,
    XCircleIcon,
} from '@heroicons/vue/16/solid';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    isBillingActivated,
    isBlocked,
    isFreePlan,
    isInTrial,
} from '@/utils/billing';
import { useSessionStorage } from '@vueuse/core';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { canManageBilling } from '@/utils/permissions';

const hideTrialBanner = useSessionStorage(
    'showTrialBanner-' + getCurrentOrganizationId(),
    false
);
const showTrialBanner = computed(() => isInTrial() && !hideTrialBanner.value);
const hideBlockedBanner = useSessionStorage(
    'showBlockedBanner-' + getCurrentOrganizationId(),
    false
);
const showBlockedBanner = computed(
    () => isBlocked() && !hideBlockedBanner.value
);
const hideFreeUpgradeBanner = useSessionStorage(
    'showFreeUpgradeBanner-' + getCurrentOrganizationId(),
    false
);
const showFreeUpgradeBanner = computed(
    () => isFreePlan() && !isBlocked() && !hideFreeUpgradeBanner.value
);
</script>

<template>
    <div
        v-if="showTrialBanner"
        class="bg-accent-600/50 text-sm py-0.5 border-b border-border-secondary">
        <MainContainer class="flex items-center justify-between">
            <div class="flex items-center space-x-1.5">
                <CheckBadgeIcon class="w-4 text-white/50"></CheckBadgeIcon>
                <span class="font-medium"> Your trial expires in X days. </span>
                <span>
                    To continue using all features & support the development of
                    solidtime, please upgrade your plan.
                </span>
            </div>
            <div class="flex items-center space-x-2">
                <Link v-if="canManageBilling()" href="/billing">
                    <div
                        class="text-white font-semibold uppercase text-xs flex space-x-1 items-center hover:bg-white/10 rounded-lg px-2 py-1.5">
                        <span>Upgrade now</span>
                    </div>
                </Link>
                <button @click="hideTrialBanner = true" class="p-1">
                    <XMarkIcon
                        class="w-4 opacity-50 hover:opacity-100"></XMarkIcon>
                </button>
            </div>
        </MainContainer>
    </div>
    <div
        v-if="showBlockedBanner"
        class="bg-red-600/50 text-sm py-0.5 border-b border-border-secondary">
        <MainContainer class="flex items-center justify-between">
            <div class="flex items-center space-x-1.5">
                <XCircleIcon class="w-4 text-white/50"></XCircleIcon>
                <span class="font-medium">
                    Your organization is currently blocked.
                </span>
                <span>
                    Please upgrade to a premium plan or remove all users except
                    the owner to unblock your organization.
                </span>
            </div>
            <div class="flex items-center space-x-2">
                <Link
                    v-if="isBillingActivated() && canManageBilling()"
                    href="/billing">
                    <div
                        class="text-white font-semibold uppercase text-xs flex space-x-1 items-center hover:bg-white/10 rounded-lg px-2 py-1.5">
                        <span>Upgrade now</span>
                    </div>
                </Link>
                <button @click="hideBlockedBanner = true" class="p-1">
                    <XMarkIcon
                        class="w-4 opacity-50 hover:opacity-100"></XMarkIcon>
                </button>
            </div>
        </MainContainer>
    </div>
    <div
        v-if="showFreeUpgradeBanner"
        class="bg-tertiary text-sm py-0.5 border-b border-border-secondary">
        <MainContainer class="flex items-center justify-between">
            <div class="flex items-center space-x-1.5">
                <XCircleIcon class="w-4 text-white/50"></XCircleIcon>
                <div class="flex items-center flex-col md:flex-row">
                    <span class="font-medium">
                        You are currently using the Free Plan.
                    </span>
                    <span
                        >To unlock all premium features & support the
                        development of solidtime, please upgrade your
                        plan.</span
                    >
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <Link
                    v-if="isBillingActivated() && canManageBilling()"
                    href="/billing">
                    <div
                        class="text-white font-semibold uppercase text-xs flex space-x-1 items-center hover:bg-white/10 rounded-lg px-2 py-1.5">
                        <span>Upgrade now</span>
                    </div>
                </Link>
                <button @click="hideFreeUpgradeBanner = true" class="p-1">
                    <XMarkIcon
                        class="w-4 opacity-50 hover:opacity-100"></XMarkIcon>
                </button>
            </div>
        </MainContainer>
    </div>
</template>

<style scoped></style>
