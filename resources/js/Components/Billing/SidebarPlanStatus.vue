<script setup lang="ts">
import { computed, ref } from 'vue';
import { router } from '@inertiajs/vue3';
import { SparklesIcon } from '@heroicons/vue/16/solid';
import { Button } from '@/packages/ui/src/Buttons';
import TrialUpgradeModal from '@/Components/Billing/TrialUpgradeModal.vue';
import CancelTrialModal from '@/Components/Billing/CancelTrialModal.vue';
import { daysLeftInTrial, isBlocked, isFreePlan, isInTrial } from '@/utils/billing';

const showTrialUpgradeModal = ref(false);
const showCancelTrialModal = ref(false);

const daysLeftLabel = computed(() => {
    const days = daysLeftInTrial();
    return days === 1 ? '1 day left' : `${days} days left`;
});

function openBilling() {
    router.visit('/billing');
}
</script>

<template>
    <div v-if="isInTrial() || (isFreePlan() && !isBlocked())" class="pb-3">
        <Button
            v-if="isInTrial()"
            variant="outline"
            size="sm"
            data-testid="sidebar_trial_days_left"
            class="w-full justify-between rounded-lg px-2.5 text-xs font-normal [&_svg]:size-3.5"
            @click="showTrialUpgradeModal = true">
            <span class="flex items-center gap-1.5 font-medium text-text-primary">
                <SparklesIcon class="h-3.5 w-3.5 text-accent-500 dark:text-accent-300" />
                Professional trial
            </span>
            <span class="text-text-secondary">{{ daysLeftLabel }}</span>
        </Button>
        <div
            v-else-if="isFreePlan() && !isBlocked()"
            class="flex items-center justify-between gap-2 rounded-lg border border-border-secondary bg-card-background py-1.5 pl-2.5 pr-1.5 text-xs">
            <span class="font-medium text-text-primary">Free plan</span>
            <Button
                size="xs"
                data-testid="sidebar_upgrade_button"
                class="h-6 bg-accent-500 px-2.5 font-semibold text-white hover:bg-accent-400"
                @click="openBilling">
                Upgrade
            </Button>
        </div>

        <TrialUpgradeModal
            v-model:show="showTrialUpgradeModal"
            @cancel-trial="showCancelTrialModal = true" />
        <CancelTrialModal v-model:show="showCancelTrialModal" />
    </div>
</template>
