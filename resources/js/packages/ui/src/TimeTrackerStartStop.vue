<script setup lang="ts">
import { cva, type VariantProps } from 'class-variance-authority';
import { cn } from './utils/cn';

const timeTrackerVariants = cva(
    'flex items-center justify-center transition focus:outline-0 rounded-full',
    {
        variants: {
            variant: {
                primary:
                    'text-white ring-accent-200/10 focus-visible:ring-ring focus-visible:ring-2 ring-4 sm:ring-[6px]',
                secondary:
                    'bg-quaternary text-text-tertiary hover:text-text-primary focus:ring-2 focus:ring-border-tertiary',
            },
            size: {
                small: 'w-6 h-6',
                base: 'w-8 h-8',
                large: 'w-11 h-11 hover:scale-110',
            },
            active: {
                true: '',
                false: '',
            },
        },
        compoundVariants: [
            {
                variant: 'primary',
                active: true,
                class: 'bg-red-400/80 hover:bg-red-500/80 focus:bg-red-500/80',
            },
            {
                variant: 'primary',
                active: false,
                class: 'bg-accent-300/70 hover:bg-accent-400/70 focus:bg-accent-700',
            },
        ],
        defaultVariants: {
            variant: 'primary',
            size: 'base',
            active: false,
        },
    }
);

type TimeTrackerVariants = VariantProps<typeof timeTrackerVariants>;

const emit = defineEmits(['changed']);

const props = withDefaults(
    defineProps<{
        variant?: TimeTrackerVariants['variant'];
        size?: TimeTrackerVariants['size'];
        active?: boolean;
    }>(),
    {
        variant: 'primary',
        size: 'base',
        active: false,
    }
);

const iconClass = {
    small: 'w-2.5 h-2.5',
    base: 'w-3 h-3',
    large: 'w-4 h-4',
};

function toggleState() {
    emit('changed', !props.active);
}
</script>

<template>
    <button
        data-testid="timer_button"
        :class="cn(timeTrackerVariants({ variant, size, active }))"
        @click="toggleState">
        <Transition name="fade" mode="out-in">
            <svg
                v-if="props.active"
                :class="iconClass[size ?? 'base']"
                viewBox="0 0 14 14"
                fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M0.461426 2.74913C0.461426 1.48677 1.48666 0.461538 2.75076 0.461538H11.249C12.5131 0.461538 13.5383 1.48677 13.5383 2.75087V11.2491C13.5383 12.5132 12.5131 13.5385 11.249 13.5385H2.7525C2.4518 13.5387 2.154 13.4796 1.87614 13.3647C1.59828 13.2497 1.34582 13.0811 1.13319 12.8684C0.920559 12.6558 0.751936 12.4033 0.636968 12.1255C0.521999 11.8476 0.462941 11.5498 0.46317 11.2491V2.75262L0.461426 2.74913Z"
                    fill="currentColor" />
            </svg>
            <svg
                v-else
                :class="iconClass[size ?? 'base']"
                viewBox="0 0 7 8"
                fill="none"
                xmlns="http://www.w3.org/2000/svg">
                <path
                    fill-rule="evenodd"
                    clip-rule="evenodd"
                    d="M6.56167 3.18089C6.70764 3.26214 6.82926 3.38092 6.91393 3.52494C6.99859 3.66896 7.04324 3.83299 7.04324 4.00005C7.04324 4.16712 6.99859 4.33115 6.91393 4.47517C6.82926 4.61919 6.70764 4.73797 6.56167 4.81922L1.8925 7.41339C1.74982 7.49259 1.58895 7.53317 1.42578 7.53113C1.26261 7.52909 1.1028 7.48449 0.962147 7.40175C0.821497 7.31901 0.704879 7.20099 0.623826 7.05937C0.542772 6.91774 0.50009 6.7574 0.5 6.59422V1.40589C0.5 0.691721 1.2675 0.239221 1.8925 0.586721L6.56167 3.18089Z"
                    fill="currentColor" />
            </svg>
        </Transition>
    </button>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 0.2s ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}
</style>
