<script setup lang="ts">
import { LockClosedIcon } from '@heroicons/vue/20/solid';
import { useBillableRatesLock } from './utils/useBillableRatesLock';

const { requestUpgrade } = useBillableRatesLock();
</script>

<template>
    <button
        type="button"
        data-testid="billable_rates_locked_badge"
        class="inline-flex items-center space-x-1 rounded border border-border-secondary bg-secondary px-2 py-1 hover:border-border-tertiary hover:bg-tertiary"
        @click.prevent.stop="requestUpgrade">
        <LockClosedIcon class="w-3 text-text-tertiary"></LockClosedIcon>
        <span class="text-xs font-semibold text-text-secondary"><slot>Upgrade</slot></span>
    </button>
</template>
