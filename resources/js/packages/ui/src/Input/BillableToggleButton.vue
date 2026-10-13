<script setup lang="ts">
import { computed } from 'vue';
import { twMerge } from 'tailwind-merge';
import BillableIcon from '@/packages/ui/src/Icons/BillableIcon.vue';
import { useBillableRatesLock } from '@/packages/ui/src/utils/useBillableRatesLock';
import {
    Tooltip,
    TooltipContent,
    TooltipProvider,
    TooltipTrigger,
} from '@/packages/ui/src/tooltip';
const active = defineModel<boolean>({ default: false });
const emit = defineEmits(['changed']);
const { locked: billableRatesLocked, requestUpgrade } = useBillableRatesLock();
function toggleBillable() {
    // Organizations with locked billable rates can remove the billable flag, but not set it
    if (billableRatesLocked.value && !active.value) {
        requestUpgrade();
        return;
    }
    active.value = !active.value;
    emit('changed', active.value);
}

const props = withDefaults(
    defineProps<{
        size?: 'small' | 'base';
        faded?: boolean;
    }>(),
    {
        size: 'base',
        faded: false,
    }
);

const tooltipLabel = computed(() => {
    if (billableRatesLocked.value && !active.value) {
        return 'Billable rates require an upgrade';
    }
    return active.value ? 'Billable' : 'Non Billable';
});

const iconColorClasses = computed(() => {
    if (active.value) {
        return 'text-input-select-active focus:text-input-select-active-hover hover:text-input-select-active-hover';
    } else {
        return 'text-icon-default focus:text-icon-active hover:text-icon-active';
    }
});

const iconSizeClasses = computed(() => {
    if (props.size === 'small') {
        return 'w-5 h-5';
    } else {
        return 'w-5 lg:w-6 h-5 lg:h-6';
    }
});

const iconSizeWrapperClasses = props.size === 'small' ? 'w-6 sm:w-8 h-6 sm:h-8' : 'w-10 h-10';
</script>

<template>
    <TooltipProvider>
        <Tooltip disable-closing-trigger>
            <TooltipTrigger as-child>
                <button
                    :aria-label="tooltipLabel"
                    :class="
                        twMerge(
                            iconColorClasses,
                            iconSizeWrapperClasses,
                            'flex-shrink-0 ring-0 focus:outline-none focus-visible:ring-2 focus-visible:ring-ring transition focus:bg-card-background-separator hover:bg-card-background-separator rounded-full flex items-center justify-center',
                            faded
                                ? 'opacity-50 group-hover:opacity-100 focus-visible:opacity-100'
                                : ''
                        )
                    "
                    @click="toggleBillable">
                    <BillableIcon :class="iconSizeClasses"></BillableIcon>
                </button>
            </TooltipTrigger>
            <TooltipContent>
                {{ tooltipLabel }}
            </TooltipContent>
        </Tooltip>
    </TooltipProvider>
</template>
