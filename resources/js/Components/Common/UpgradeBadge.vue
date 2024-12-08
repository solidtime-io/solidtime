<script setup lang="ts">
import { LockClosedIcon } from '@heroicons/vue/20/solid';
import UpgradeModal from '@/Components/Common/UpgradeModal.vue';
import { ref } from 'vue';
const showUpgradeModal = ref(false);
</script>

<template>
    <UpgradeModal v-model:show="showUpgradeModal">
        <strong>Project and Task Estimates</strong> is only available in
        solidtime Professional.
    </UpgradeModal>
    <button
        @click.prevent="showUpgradeModal = true"
        class="inline-flex bg-secondary hover:bg-tertiary px-2 py-1 rounded border border-border-secondary hover:border-border-tertiary items-center space-x-1">
        <LockClosedIcon class="w-3 text-text-tertiary"></LockClosedIcon>
        <span class="text-xs text-text-secondary font-semibold"> Upgrade </span>
    </button>
</template>

<style scoped></style>
