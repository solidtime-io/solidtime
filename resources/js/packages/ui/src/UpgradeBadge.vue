<script setup lang="ts">
import { LockClosedIcon } from '@heroicons/vue/20/solid';
import UpgradeModal from './UpgradeModal.vue';
import type { UpgradeFeature } from './UpgradeModal/useUpgradeCta';
import { ref } from 'vue';

defineProps<{
    // The Professional feature behind the badge, picks the variant of the upgrade modal
    feature: UpgradeFeature;
}>();

const showUpgradeModal = ref(false);
</script>

<template>
    <UpgradeModal v-model:show="showUpgradeModal" :feature="feature" />
    <button
        data-testid="upgrade_badge"
        class="inline-flex bg-secondary hover:bg-tertiary px-2 py-1 rounded border border-border-secondary hover:border-border-tertiary items-center space-x-1"
        @click.prevent="showUpgradeModal = true">
        <LockClosedIcon class="w-3 text-text-tertiary"></LockClosedIcon>
        <span class="text-xs text-text-secondary font-semibold"> Upgrade </span>
    </button>
</template>

<style scoped></style>
