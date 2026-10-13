<script setup lang="ts">
import MainContainer from '@/packages/ui/src/MainContainer.vue';
import { XMarkIcon, XCircleIcon } from '@heroicons/vue/16/solid';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { isBillingActivated, isBlocked } from '@/utils/billing';
import { useSessionStorage } from '@vueuse/core';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { canManageBilling } from '@/utils/permissions';

const hideBlockedBanner = useSessionStorage(
    'showBlockedBanner-' + getCurrentOrganizationId(),
    false
);
const showBlockedBanner = computed(() => isBlocked() && !hideBlockedBanner.value);
</script>

<template>
    <div
        v-if="showBlockedBanner"
        class="bg-red-600/50 text-xs lg:text-sm py-0.5 border-b border-border-secondary">
        <MainContainer class="flex items-center justify-between">
            <div class="flex items-center space-x-1.5">
                <XCircleIcon class="w-4 text-text-primary/50"></XCircleIcon>
                <div class="flex-1 space-x-1">
                    <span class="font-medium"> Your organization is currently blocked. </span>
                    <span class="hidden md:inline">
                        Please upgrade to a premium plan or remove all users except the owner to
                        unblock your organization.
                    </span>
                </div>
            </div>
            <div class="flex items-center space-x-2">
                <Link v-if="isBillingActivated() && canManageBilling()" href="/billing">
                    <div
                        class="text-text-primary font-semibold uppercase text-xs flex space-x-1 items-center hover:bg-white/10 rounded-lg px-2 py-1.5">
                        <span>Upgrade now</span>
                    </div>
                </Link>
                <button class="p-1" @click="hideBlockedBanner = true">
                    <XMarkIcon class="w-4 opacity-50 hover:opacity-100"></XMarkIcon>
                </button>
            </div>
        </MainContainer>
    </div>
</template>

<style scoped></style>
