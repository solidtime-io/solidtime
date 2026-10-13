<script setup lang="ts">
import { computed, inject, ref, type ComputedRef } from 'vue';
import { usePage } from '@inertiajs/vue3';
import ActionSection from '@/Components/ActionSection.vue';
import SecondaryButton from '@/packages/ui/src/Buttons/SecondaryButton.vue';
import CancelTrialModal from '@/Components/Billing/CancelTrialModal.vue';
import { daysLeftInTrial } from '@/utils/billing';
import { formatDateLocalized } from '@/packages/ui/src/utils/time';
import type { Organization } from '@/packages/api/src';

const showCancelTrialModal = ref(false);

const organization = inject<ComputedRef<Organization | undefined>>('organization');
const page = usePage<{ billing: { trial_until: string | null } | null }>();
const trialUntil = computed(() =>
    page.props.billing?.trial_until
        ? formatDateLocalized(page.props.billing.trial_until, organization?.value?.date_format)
        : null
);
const daysLeftLabel = computed(() => {
    const days = daysLeftInTrial();
    return days === 1 ? '1 day' : `${days} days`;
});
</script>

<template>
    <ActionSection>
        <template #title>Trial</template>

        <template #description>Your organization is trying out solidtime Professional.</template>

        <template #content>
            <div class="max-w-xl text-sm text-text-secondary">
                Your trial ends in
                <span class="font-medium text-text-primary">{{ daysLeftLabel }}</span>
                <template v-if="trialUntil"> ({{ trialUntil }})</template>. If you don't need the
                Professional features, you can switch to the Free plan right away.
            </div>

            <div class="mt-5">
                <SecondaryButton
                    data-testid="cancel_trial_button"
                    @click="showCancelTrialModal = true">
                    Cancel trial
                </SecondaryButton>
            </div>

            <CancelTrialModal v-model:show="showCancelTrialModal" />
        </template>
    </ActionSection>
</template>
