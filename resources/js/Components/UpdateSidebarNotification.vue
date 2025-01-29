<script setup lang="ts">
import { BellAlertIcon, XMarkIcon } from '@heroicons/vue/20/solid';
import { SecondaryButton } from '@/packages/ui/src';
import { useStorage } from '@vueuse/core';
const showReleaseInfo = useStorage('showReleaseInfo-desktop', true);

function openDesktopGithubRepo() {
    window
        .open('https://github.com/solidtime-io/solidtime-desktop', '_blank')
        ?.focus();
}
</script>

<template>
    <div v-if="showReleaseInfo" class="py-4 hidden lg:block">
        <div
            class="rounded-lg px-2.5 py-2 bg-card-background border border-border-secondary">
            <div class="flex items-start justify-between">
                <div
                    class="text-xs pb-1.5 font-semibold text-text-tertiary flex items-center space-x-1">
                    <BellAlertIcon class="w-3.5"></BellAlertIcon>
                    <span> New Update </span>
                </div>
                <button>
                    <XMarkIcon
                        class="w-3.5 text-text-tertiary hover:text-text-secondary"
                        @click="showReleaseInfo = false"></XMarkIcon>
                </button>
            </div>

            <p class="text-xs">
                <span class="font-semibold">Solidtime Desktop Beta</span> is
                here! Test our brand new clients for Windows, macOS and Linux
                now.
            </p>
            <SecondaryButton
                size="small"
                class="w-full text-center justify-center mt-1.5"
                @click="openDesktopGithubRepo"
                >Download now</SecondaryButton
            >
        </div>
    </div>
</template>

<style scoped></style>
