<script setup lang="ts">
import { Switch } from '@/Components/ui/switch';
import { Popover, PopoverContent, PopoverTrigger } from '@/Components/ui/popover';
import { Button } from '@/packages/ui/src';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/Components/ui/select';
import InputLabel from '@/packages/ui/src/Input/InputLabel.vue';
import {
    NumberField,
    NumberFieldInput,
    NumberFieldContent,
    NumberFieldIncrement,
    NumberFieldDecrement,
} from '@/Components/ui/number-field';
import { ArrowsUpDownIcon } from '@heroicons/vue/20/solid';
import { computed, ref, watch } from 'vue';
import { twMerge } from 'tailwind-merge';
import { isAllowedToPerformPremiumAction } from '@/utils/billing';
import { Link } from '@inertiajs/vue3';
import { CreditCardIcon } from '@heroicons/vue/20/solid';
// TimeEntryRoundingType definition
const TimeEntryRoundingType = {
    Up: 'up' as const,
    Down: 'down' as const,
    Nearest: 'nearest' as const,
} as const;

type TimeEntryRoundingType = (typeof TimeEntryRoundingType)[keyof typeof TimeEntryRoundingType];

interface Props {
    enabled: boolean;
    type: TimeEntryRoundingType;
    minutes: number;
}

const props = defineProps<Props>();

const emit = defineEmits<{
    'update:enabled': [value: boolean];
    'update:type': [value: TimeEntryRoundingType];
    'update:minutes': [value: number];
    'change': [];
}>();

function updateEnabled(value: boolean) {
    emit('update:enabled', value);
    emit('change');
}

function updateType(value: TimeEntryRoundingType) {
    emit('update:type', value);
    emit('change');
}

function updateMinutes(value: number) {
    emit('update:minutes', value);
    emit('change');
}

// Predefined intervals
const predefinedIntervals = [
    { value: '5', label: '5 minutes' },
    { value: '6', label: '6 minutes' },
    { value: '10', label: '10 minutes' },
    { value: '15', label: '15 minutes' },
    { value: '30', label: '30 minutes' },
    { value: '60', label: '1 hour' },
    { value: 'custom', label: 'Custom' },
];

const showCustomInput = ref(false);
const customMinutes = ref(props.minutes);
const selectedInterval = ref('');

// Compute the current interval value based on props
const currentInterval = computed(() => {
    const predefined = predefinedIntervals.find(
        (interval) => interval.value !== 'custom' && parseInt(interval.value) === props.minutes
    );
    return predefined ? predefined.value : 'custom';
});

// Initialize selectedInterval
const initializeSelectedInterval = () => {
    selectedInterval.value = currentInterval.value;
    showCustomInput.value = selectedInterval.value === 'custom';
    if (showCustomInput.value) {
        customMinutes.value = props.minutes;
    }
};

function handleIntervalChange(value: string) {
    selectedInterval.value = value;
    if (value === 'custom') {
        showCustomInput.value = true;
        // Update minutes to current custom value to ensure "custom" shows as selected
        updateMinutes(customMinutes.value);
    } else {
        showCustomInput.value = false;
        const minutes = parseInt(value);
        updateMinutes(minutes);
    }
}

function handleCustomMinutesChange(value: string | number) {
    const numValue = typeof value === 'string' ? parseInt(value) : value;
    if (!isNaN(numValue) && numValue > 0) {
        customMinutes.value = numValue;
        updateMinutes(numValue);
    }
}

// Watch for changes in props.minutes
watch(
    () => props.minutes,
    (newMinutes) => {
        customMinutes.value = newMinutes;
        initializeSelectedInterval();
    },
    { immediate: true }
);

watch(currentInterval, () => {
    initializeSelectedInterval();
});

// Active styling similar to ReportingFilterBadge
const activeClass = computed(() => {
    if (props.enabled) {
        return 'border-accent-300/50 bg-accent-50 hover:bg-accent-100 dark:border-accent-300/50 dark:bg-accent-300/5 dark:hover:bg-accent-300/10';
    }
    return '';
});

const iconClass = computed(() => {
    return twMerge(
        'w-4 h-4',
        props.enabled
            ? 'dark:text-accent-300/80 text-accent-400/80'
            : 'text-muted-foreground opacity-50'
    );
});
</script>

<template>
    <Popover>
        <PopoverTrigger as-child>
            <Button variant="outline" size="sm" :class="twMerge(activeClass)">
                <ArrowsUpDownIcon :class="iconClass" />
                Rounding {{ enabled ? 'on' : 'off' }}
            </Button>
        </PopoverTrigger>
        <PopoverContent class="w-72 p-4">
            <div v-if="!isAllowedToPerformPremiumAction()" class="flex flex-col space-y-2">
                <span class="font-semibold text-xs">Premium</span>
                <span class="text-xs text-text-secondary flex-1"
                    >Rounding is a premium feature. Upgrade to unlock this feature.</span
                >
                <Link href="/billing">
                    <Button size="sm" variant="input" class="items-center space-x-1">
                        <CreditCardIcon class="w-3.5 h-3.5 text-text-tertiary mr-1" />
                        Go to Billing
                    </Button>
                </Link>
            </div>
            <div v-else class="space-y-4">
                <div>
                    <div class="flex items-center justify-between">
                        <InputLabel for="enable-rounding" value="Enable Rounding" />
                        <Switch
                            id="enable-rounding"
                            :model-value="enabled"
                            class="data-[state=checked]:bg-accent-500"
                            @update:model-value="updateEnabled" />
                    </div>
                    <div
                        class="mb-3 pb-2 pt-1 text-xs text-muted-foreground border-b border-border-secondary text-text-tertiary">
                        Rounding is applied to each individual time entry, not to the accumulated
                        total.
                    </div>
                </div>

                <div>
                    <InputLabel for="rounding-type" value="Rounding Type" class="mb-2" />
                    <Select
                        :model-value="type"
                        :disabled="!enabled"
                        @update:model-value="(value) => updateType(value as TimeEntryRoundingType)">
                        <SelectTrigger
                            id="rounding-type"
                            size="small"
                            class="w-full"
                            :disabled="!enabled">
                            <SelectValue placeholder="Select rounding type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="up">Round Up</SelectItem>
                            <SelectItem value="down">Round Down</SelectItem>
                            <SelectItem value="nearest">Round Nearest</SelectItem>
                        </SelectContent>
                    </Select>
                </div>
                <div>
                    <InputLabel for="minutes-interval" value="Minutes Interval" class="mb-2" />
                    <Select
                        :model-value="selectedInterval"
                        :disabled="!enabled"
                        @update:model-value="(value) => handleIntervalChange(value as string)">
                        <SelectTrigger
                            id="minutes-interval"
                            size="small"
                            class="w-full"
                            :disabled="!enabled">
                            <SelectValue placeholder="Select interval" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="interval in predefinedIntervals"
                                :key="interval.value"
                                :value="interval.value">
                                {{ interval.label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>

                    <div v-if="showCustomInput" class="mt-2">
                        <NumberField
                            id="custom-minutes"
                            :model-value="customMinutes"
                            size="small"
                            :min="1"
                            :max="1440"
                            :disabled="!enabled"
                            class="text-sm"
                            @update:model-value="handleCustomMinutesChange">
                            <NumberFieldContent>
                                <NumberFieldDecrement :disabled="!enabled" />
                                <NumberFieldInput
                                    placeholder="Enter custom minutes"
                                    :disabled="!enabled" />
                                <NumberFieldIncrement :disabled="!enabled" />
                            </NumberFieldContent>
                        </NumberField>
                    </div>
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>
