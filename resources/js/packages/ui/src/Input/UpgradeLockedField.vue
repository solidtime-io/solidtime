<script setup lang="ts">
import { LockClosedIcon } from '@heroicons/vue/20/solid';
import { useBillableRatesLock } from '../utils/useBillableRatesLock';

const { requestUpgrade } = useBillableRatesLock();
</script>

<template>
    <button
        type="button"
        data-testid="billable_rates_locked"
        class="flex h-9 w-full items-center justify-between gap-2 rounded-md border border-input-border bg-input-background px-3 text-left text-sm text-text-tertiary shadow-sm transition hover:border-border-tertiary focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring"
        @click.prevent.stop="requestUpgrade">
        <span class="flex min-w-0 items-center gap-2">
            <LockClosedIcon class="w-3.5 shrink-0 text-icon-default" />
            <span class="truncate"><slot>Billable rates</slot></span>
        </span>
        <span
            class="shrink-0 rounded border border-border-secondary bg-secondary px-2 py-0.5 text-xs font-semibold text-text-secondary">
            Upgrade
        </span>
    </button>
</template>
