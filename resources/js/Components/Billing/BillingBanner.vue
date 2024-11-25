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
    daysLeftInTrial,
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
    () =>
        isFreePlan() &&
        !isBlocked() &&
        !hideFreeUpgradeBanner.value &&
        !showBlackFridayBanner.value
);
const hideBlackFridayBanner = useSessionStorage(
    'hideBlackFridayBanner-' + getCurrentOrganizationId(),
    false
);

const showBlackFridayBanner = computed(() => {
    if (hideBlackFridayBanner.value) {
        return false;
    }
    const today = new Date();
    const blackFriday = new Date(2024, 10, 30);
    return today < blackFriday;
});
</script>

<template>
    <div
        v-if="showBlackFridayBanner"
        class="bg-tertiary text-xs lg:text-sm pb-1 pt-2 border-b border-border-secondary">
        <MainContainer class="flex items-center justify-between">
            <div class="flex items-center space-x-1.5">
                <svg
                    class="w-4 mr-1"
                    viewBox="0 0 256 256"
                    xmlns="http://www.w3.org/2000/svg">
                    <path
                        fill="#FF37AD"
                        d="M22.498 68.97a11.845 11.845 0 1 0 0-23.687c-6.471.098-11.666 5.372-11.666 11.844s5.195 11.746 11.666 11.844m181.393-10.04a11.845 11.845 0 1 0-.003-23.688c-6.471.098-11.665 5.373-11.665 11.845c.001 6.472 5.197 11.745 11.668 11.842" />
                    <path
                        fill="#FCC954"
                        d="M213.503 211.097a11.845 11.845 0 1 0-.003-23.687c-6.471.098-11.665 5.373-11.664 11.845s5.196 11.745 11.667 11.842M70.872 23.689a11.845 11.845 0 1 0 0-23.688C64.4.1 59.206 5.373 59.206 11.845S64.4 23.591 70.872 23.689" />
                    <path
                        fill="#2890E9"
                        d="M140.945 105.94a9.25 9.25 0 0 1-8.974-11.484c.37-1.482.672-2.97.899-4.455a25.4 25.4 0 0 1-8.732 1.904c-5.379.205-10.195-.702-14.3-2.69a22.23 22.23 0 0 1-9.614-8.877c-4.415-7.652-4.034-17.718.964-25.645c4.765-7.568 12.836-11.664 21.586-10.995c6.74.527 12.647 3.051 17.378 7.382q1.293-3.647 2.473-7.803c4.833-17.058 6.429-34.187 6.442-34.36a9.24 9.24 0 0 1 10.041-8.37a9.25 9.25 0 0 1 8.37 10.044c-.067.767-1.768 19.03-7.068 37.735c-2.676 9.445-5.838 17.426-9.42 23.798q.396 2.13.631 4.372c.746 7.211.152 14.974-1.714 22.445a9.256 9.256 0 0 1-8.962 6.998m-20.123-43.827c-.956 0-2.64.28-3.996 2.43c-1.298 2.06-1.552 4.873-.588 6.544c1.282 2.223 5.054 2.417 7.19 2.336c2.424-.092 4.908-1.612 7.338-4.382a16 16 0 0 0-1.43-2.422c-2.007-2.787-4.547-4.212-7.998-4.482c-.13-.008-.305-.024-.516-.024" />
                    <path
                        fill="#F0A420"
                        d="M114.361 131.268c-38.343-30.224-78.42-43.319-89.514-29.246a12.8 12.8 0 0 0-2.257 4.509a4 4 0 0 0-.156.61v.024q-.223.947-.333 1.917L.393 236.18c-3.477 20.412 16.73 36.755 35.967 29.093l117.721-46.908c2.076-.826 7.185-3.982 8.583-5.724q.556-.544 1.037-1.153c11.092-14.075-11-49.988-49.34-80.223z" />
                    <path
                        fill="#FCC954"
                        d="M163.688 211.494c11.1-14.08-10.984-50-49.327-80.226c-38.343-30.227-78.425-43.316-89.524-29.236s10.983 50 49.326 80.226c38.343 30.227 78.425 43.316 89.525 29.236" />
                    <path
                        fill="#F0A420"
                        d="M156.994 203.294c9.108-11.556-10.956-42.563-44.817-69.256c-33.861-26.695-68.697-38.966-77.804-27.413c-9.11 11.556 10.954 42.563 44.815 69.256c33.86 26.695 68.697 38.969 77.806 27.413" />
                    <path
                        fill="#2E6AC9"
                        d="M76.059 249.456c-14.327.07-26.004-7.101-40.158-18.257C19.431 218.21 8.493 202.665 7.63 193.81l-4.668 27.327c2.16 7.798 9.523 17.683 20.202 26.101c8.883 7.004 17.844 11.813 27.135 12.48l25.76-10.266zm-14.332-49.6c-27.443-21.637-45.271-46.467-44.77-60.669l-4.549 26.63c.351 12.685 15.175 33.184 36.262 49.808c18.894 14.896 38.583 25.38 53.66 23.363l25.593-10.2c-20.62 1.425-42.376-10.147-66.196-28.931" />
                    <path
                        fill="#2890E9"
                        d="M118.535 145.052a11.845 11.845 0 1 0 0-23.688c-6.471.098-11.666 5.372-11.666 11.844s5.195 11.746 11.666 11.844" />
                    <path
                        fill="#FF37AD"
                        d="m182.412 122.007l.087-.097c.108-.116.308-.33.596-.621a45 45 0 0 1 2.8-2.56c3.56-2.98 7.45-5.54 11.594-7.63c10.128-5.125 25.208-9.307 44.985-4.747c5.943 1.37 11.87-2.336 13.241-8.278c1.37-5.942-2.336-11.87-8.278-13.24c-25.602-5.903-45.957-.506-59.922 6.566a82.5 82.5 0 0 0-15.857 10.449a66 66 0 0 0-4.215 3.866a45 45 0 0 0-1.53 1.615l-.12.135l-.042.048l-.02.022l-.007.008c-.003.005-.009.01 8.361 7.21l-8.37-7.2c-3.877 4.622-3.328 11.5 1.233 15.448s11.446 3.506 15.464-.994M73.03 43.248a11.75 11.75 0 0 0-16.23-3.664a11.76 11.76 0 0 0-3.665 16.227c.427.683 9.178 14.86 10.976 34.276c1.83 19.727-3.966 37.86-17.253 54.12c4.474 5.686 9.858 11.596 16.008 17.507c8.51-9.834 14.913-20.402 19.12-31.583c5.175-13.756 7.006-28.342 5.445-43.348c-2.487-23.874-12.874-41.11-14.402-43.535" />
                    <path
                        fill="#2890E9"
                        d="M220.242 156.578c6.002 1.553 10.244 3.246 12.077 4.034a11.86 11.86 0 0 0 13.94-1.12a11.87 11.87 0 0 0 4.107-8.765a11.85 11.85 0 0 0-8.06-11.426c-5.618-2.495-26.905-10.92-55.044-9.423c-18.941 1.007-37.155 6.253-54.133 15.608c-16.076 8.86-31.004 21.412-44.556 37.425a199 199 0 0 0 20.17 12.607c22.882-26.08 49.283-40.217 78.7-42.085a105.9 105.9 0 0 1 32.8 3.145" />
                </svg>
                <div class="flex-1 space-x-1">
                    <span class="font-medium">
                        <strong>BLACK FRIDAY SALE!</strong> Use the code
                        <strong>BLACKFRIDAY</strong> at checkout and get
                        <strong>30% off</strong> the solidtime yearly plan.
                    </span>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <Link v-if="canManageBilling()" href="/billing">
                    <div
                        class="text-white font-semibold uppercase text-xs flex space-x-1 items-center hover:bg-white/10 rounded-lg px-2 py-1.5">
                        <span>Upgrade now</span>
                    </div>
                </Link>
                <button @click="hideBlackFridayBanner = true" class="p-1">
                    <XMarkIcon
                        class="w-4 opacity-50 hover:opacity-100"></XMarkIcon>
                </button>
            </div>
        </MainContainer>
    </div>
    <div
        v-if="showTrialBanner"
        class="bg-accent-600/50 text-xs lg:text-sm py-0.5 border-b border-border-secondary">
        <MainContainer class="flex items-center justify-between">
            <div class="flex items-center space-x-1.5">
                <CheckBadgeIcon class="w-4 text-white/50"></CheckBadgeIcon>
                <div class="flex-1 space-x-1">
                    <span class="font-medium">
                        Your trial expires in {{ daysLeftInTrial() }} days.
                    </span>
                    <span class="hidden md:inline">
                        To continue using all features & support the development
                        of solidtime, please upgrade your plan.
                    </span>
                </div>
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
        class="bg-red-600/50 text-xs lg:text-sm py-0.5 border-b border-border-secondary">
        <MainContainer class="flex items-center justify-between">
            <div class="flex items-center space-x-1.5">
                <XCircleIcon class="w-4 text-white/50"></XCircleIcon>
                <div class="flex-1 space-x-1">
                    <span class="font-medium">
                        Your organization is currently blocked.
                    </span>
                    <span class="hidden md:inline">
                        Please upgrade to a premium plan or remove all users
                        except the owner to unblock your organization.
                    </span>
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
                <button @click="hideBlockedBanner = true" class="p-1">
                    <XMarkIcon
                        class="w-4 opacity-50 hover:opacity-100"></XMarkIcon>
                </button>
            </div>
        </MainContainer>
    </div>
    <div
        v-if="showFreeUpgradeBanner"
        class="bg-tertiary text-xs lg:text-sm py-0.5 border-b border-border-secondary">
        <MainContainer class="flex items-center justify-between">
            <div class="flex items-center space-x-1.5">
                <XCircleIcon class="w-4 text-white/50"></XCircleIcon>
                <div class="flex-1 space-x-1">
                    <span class="font-medium">
                        You are currently using the Free Plan.
                    </span>
                    <span class="hidden md:inline">
                        To unlock all premium features & support the development
                        of solidtime, please upgrade your plan.</span
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
