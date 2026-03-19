<script setup lang="ts">
import { cn } from '@/lib/utils';
import { ChevronDown } from 'lucide-vue-next';
import { SelectIcon, SelectTrigger, type SelectTriggerProps, useForwardProps } from 'reka-ui';
import { computed, type HTMLAttributes } from 'vue';

const props = withDefaults(
    defineProps<
        SelectTriggerProps & {
            size?: 'default' | 'sm' | 'lg';
            class?: HTMLAttributes['class'];
            showChevron?: boolean;
            variant?: 'default' | 'outline';
            active?: boolean;
        }
    >(),
    {
        showChevron: true,
        variant: 'default',
        active: false,
        size: 'default',
    }
);

const delegatedProps = computed(() => {
    const {
        class: _,
        showChevron: __,
        variant: ___,
        active: ____,
        size: _____,
        ...delegated
    } = props;

    return delegated;
});

const forwardedProps = useForwardProps(delegatedProps);

const sizeClasses = computed(() => {
    switch (props.size) {
        case 'sm':
            return 'h-8 px-3 text-xs';
        case 'lg':
            return 'h-10 px-4 text-sm';
        default:
            return 'h-9 px-3 text-sm';
    }
});

const variantClasses = computed(() => {
    if (props.variant === 'outline') {
        if (props.active) {
            return 'border border-accent-300/50 bg-accent-50 hover:bg-accent-100 dark:border-accent-300/50 dark:bg-accent-300/5 dark:hover:bg-accent-300/10';
        }
        return 'border shadow-xs hover:text-text-primary bg-card-background dark:bg-transparent border-input dark:border-input hover:bg-white/5';
    }
    return 'border border-input-border bg-input-background shadow-sm';
});
</script>

<template>
    <SelectTrigger
        v-bind="forwardedProps"
        :class="
            cn(
                'flex items-center justify-between gap-3 whitespace-nowrap rounded-md data-[placeholder]:text-muted-foreground focus:outline-none focus:ring-2 focus:ring-ring disabled:cursor-not-allowed disabled:opacity-50 [&>span]:truncate text-start font-medium transition-colors',
                sizeClasses,
                variantClasses,
                props.class
            )
        ">
        <slot />
        <SelectIcon v-if="showChevron" as-child>
            <ChevronDown class="w-4 h-4 text-icon-default shrink-0" />
        </SelectIcon>
    </SelectTrigger>
</template>
