<script setup lang="ts">
import { computed } from 'vue';
import { Dialog, DialogContent, DialogTitle, DialogDescription } from '@/packages/ui/src/dialog';
import { Button } from '@/packages/ui/src/Buttons';
import { SparklesIcon } from '@heroicons/vue/16/solid';
import { featureIcons } from '@/packages/ui/src/UpgradeModal/featureIcons';
import { professionalFeatures, useUpgradeCta } from '@/packages/ui/src/UpgradeModal/useUpgradeCta';
import { daysLeftInTrial } from '@/utils/billing';

const show = defineModel<boolean>('show', { default: false });

const emit = defineEmits<{ cancelTrial: [] }>();

const { goToBilling } = useUpgradeCta();

const daysLeftLabel = computed(() => {
    const days = daysLeftInTrial();
    return days === 1 ? '1 day' : `${days} days`;
});

function cancelTrial() {
    show.value = false;
    emit('cancelTrial');
}
</script>

<template>
    <Dialog v-model:open="show">
        <DialogContent class="sm:max-w-xl p-0 overflow-hidden">
            <div class="px-8 pt-8 text-center">
                <div
                    class="mx-auto inline-flex items-center gap-1.5 rounded-full border border-border-secondary bg-card-background px-3 py-1 text-xs font-medium text-text-primary">
                    <SparklesIcon class="h-3.5 w-3.5 text-accent-500 dark:text-accent-300" />
                    {{ daysLeftLabel }} left in your trial
                </div>
                <DialogTitle class="mt-4 text-xl font-semibold tracking-tight text-text-primary">
                    Keep solidtime Professional
                </DialogTitle>
                <DialogDescription class="mt-1.5 text-sm text-text-secondary">
                    Upgrade now to keep these features after your trial ends.
                </DialogDescription>
            </div>

            <div class="grid grid-cols-2 gap-x-6 gap-y-4 px-8 pt-8">
                <div v-for="item in professionalFeatures" :key="item.key" class="flex gap-3">
                    <div
                        class="flex h-7 w-7 shrink-0 items-center justify-center rounded-md border border-border-tertiary bg-card-background">
                        <component
                            :is="featureIcons[item.key]"
                            class="h-3.5 w-3.5 text-text-secondary" />
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-medium text-text-primary">{{ item.title }}</p>
                        <p class="mt-0.5 text-xs leading-relaxed text-text-tertiary">
                            {{ item.description }}
                        </p>
                    </div>
                </div>
            </div>

            <div class="mx-auto flex max-w-xs flex-col items-center gap-3 px-8 pt-8 pb-6">
                <Button
                    data-testid="trial_upgrade_modal_cta"
                    class="w-full bg-accent-500 px-4 font-semibold text-white shadow-[0_0_24px_-6px] shadow-accent-500/60 hover:bg-accent-400"
                    @click="goToBilling">
                    Upgrade to Professional
                </Button>
                <Button
                    variant="ghost"
                    size="xs"
                    data-testid="trial_upgrade_modal_cancel_trial"
                    class="font-normal text-text-tertiary hover:text-text-primary"
                    @click="cancelTrial">
                    Cancel trial
                </Button>
            </div>
        </DialogContent>
    </Dialog>
</template>
