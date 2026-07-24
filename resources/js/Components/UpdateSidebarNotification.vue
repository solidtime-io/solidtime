<script setup lang="ts">
import { BellAlertIcon, XMarkIcon } from '@heroicons/vue/20/solid';
import { SecondaryButton } from '@/packages/ui/src';
import { useStorage } from '@vueuse/core';
import { router } from '@inertiajs/vue3';
import { getCurrentOrganizationId } from '@/utils/useUser';
import { canUpdateOrganization } from '@/utils/permissions';
const showReleaseInfo = useStorage('showReleaseInfo-breaks', true);

function openOrganizationSettings() {
    router.visit(route('organizations.show', getCurrentOrganizationId()));
}

function openBreaksDocs() {
    window.open('https://docs.solidtime.io/user-guide/breaks', '_blank')?.focus();
}
</script>

<template>
    <div v-if="showReleaseInfo" class="py-4 hidden lg:block">
        <div class="rounded-lg px-2.5 py-2 bg-card-background border border-border-secondary">
            <div class="flex items-start justify-between">
                <div
                    class="text-xs pb-1.5 font-semibold text-text-tertiary flex items-center space-x-1">
                    <BellAlertIcon class="w-3.5"></BellAlertIcon>
                    <span> New Feature </span>
                </div>
                <button>
                    <XMarkIcon
                        class="w-3.5 text-text-tertiary hover:text-text-secondary"
                        @click="showReleaseInfo = false"></XMarkIcon>
                </button>
            </div>

            <p class="text-xs">
                <span class="font-semibold">Breaks</span> are here! Enable them in the organization
                settings to track break time in the time tracker and timesheet.
            </p>
            <SecondaryButton
                v-if="canUpdateOrganization()"
                size="small"
                class="w-full text-center justify-center mt-1.5"
                @click="openOrganizationSettings"
                >Enable now</SecondaryButton
            >
            <SecondaryButton
                v-else
                size="small"
                class="w-full text-center justify-center mt-1.5"
                @click="openBreaksDocs"
                >Learn more</SecondaryButton
            >
        </div>
    </div>
</template>

<style scoped></style>
