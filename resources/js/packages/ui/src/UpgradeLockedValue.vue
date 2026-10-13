<script setup lang="ts">
import { LockClosedIcon } from '@heroicons/vue/20/solid';
import { Button } from './Buttons';
import { useBillableRatesLock } from './utils/useBillableRatesLock';

const { requestUpgrade } = useBillableRatesLock();
</script>

<template>
    <span class="inline-flex items-center gap-1">
        --
        <Button
            variant="ghost"
            size="icon"
            class="h-6 w-6 text-text-tertiary hover:text-text-primary"
            aria-label="Upgrade to unlock billable rates"
            data-testid="billable_rates_locked_button"
            @click.prevent.stop="requestUpgrade">
            <LockClosedIcon />
        </Button>
    </span>
</template>
