<script setup lang="ts">
import { ArrowTopRightOnSquareIcon } from '@heroicons/vue/16/solid';
import { pricingUrl, useUpgradeCta } from './useUpgradeCta';

const props = withDefaults(
    defineProps<{
        label?: string;
        align?: 'between' | 'end' | 'stack';
        showCompare?: boolean;
    }>(),
    { label: 'Upgrade to Professional', align: 'between', showCompare: true }
);

const emit = defineEmits<{ close: [] }>();
const { canUpgrade, goToBilling } = useUpgradeCta();
</script>

<template>
    <div
        :class="
            props.align === 'stack'
                ? 'flex flex-col items-center gap-3'
                : 'flex flex-wrap items-center gap-2'
        ">
        <a
            v-if="props.align !== 'stack' && props.showCompare"
            :href="pricingUrl"
            target="_blank"
            rel="noopener"
            class="mr-auto inline-flex items-center gap-1 text-xs text-text-tertiary transition-colors hover:text-text-primary">
            Compare plans
            <ArrowTopRightOnSquareIcon class="h-3 w-3" />
        </a>
        <template v-if="canUpgrade">
            <button
                v-if="props.align !== 'stack'"
                type="button"
                class="h-9 whitespace-nowrap rounded-md px-3 text-sm font-medium text-text-secondary transition-colors hover:bg-white/5 hover:text-text-primary"
                @click="emit('close')">
                Not now
            </button>
            <button
                type="button"
                data-testid="upgrade_modal_cta"
                class="h-9 whitespace-nowrap rounded-md bg-accent-500 px-4 text-sm font-semibold text-white shadow-[0_0_24px_-6px] shadow-accent-500/60 transition-colors hover:bg-accent-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                :class="props.align === 'stack' ? 'w-full' : ''"
                @click="goToBilling">
                {{ props.label }}
            </button>
        </template>
        <template v-else>
            <span class="text-xs text-text-tertiary">Only the organization owner can upgrade.</span>
            <button
                type="button"
                class="h-9 rounded-md border border-border-secondary px-3 text-sm font-medium text-text-primary transition-colors hover:bg-white/5"
                @click="emit('close')">
                Got it
            </button>
        </template>
        <div v-if="props.align === 'stack'" class="flex items-center gap-4 text-xs">
            <button
                v-if="canUpgrade"
                type="button"
                class="text-text-tertiary transition-colors hover:text-text-primary"
                @click="emit('close')">
                Not now
            </button>
            <a
                :href="pricingUrl"
                target="_blank"
                rel="noopener"
                class="inline-flex items-center gap-1 text-text-tertiary transition-colors hover:text-text-primary">
                Compare plans
                <ArrowTopRightOnSquareIcon class="h-3 w-3" />
            </a>
        </div>
    </div>
</template>
